<?php
class Service_Manufacturing_Bom extends Service_Base
{
    private function getBomOrFail(int $bomId): Models_ManufacturingBom
    {
        $bom = new Models_ManufacturingBom($bomId);
        if ($bom->isEmpty) {
            throw new Service_Exception("The requested BOM was not found", 404);
        }
        if ($bom->company_id != $this->context->companyId) {
            throw new Service_Exception("You do not have permission to access this BOM", 403);
        }
        return $bom;
    }

    private function validatePayload(array $payload): void
    {
        $companyId  = $this->context->companyId;
        $productId = (int) ($payload['product_id'] ?? 0);
        $name = trim($payload['name'] ?? '');
        $outputQty = (float) ($payload['output_qty'] ?? 0);
        $items = $payload['bom_items'] ?? [];

        $validProductId = 0;
        if (!$productId) {
            $this->addError(validationErrMsg("required", "Finished product"), "product_id");
        } else {
            $product = new Models_Product($productId);
            if ($product->isEmpty || $product->company_id != $companyId || $product->status != 'active') {
                $this->addError(validationErrMsg("missing_or_invalid", "Finished product"), "product_id");
            } else {
                $validProductId = $productId;
            }
        }

        if (empty($name)) {
            $this->addError(validationErrMsg("required", "BOM name"), "name");
        }

        if (!isPositiveNumeric($outputQty)) {
            $this->addError("Output quantity must be greater than zero", "output_qty");
        } elseif ($validProductId) {
            $uomRow = $this->db->fetchOne(
                "SELECT u.allow_decimal, u.name FROM products p JOIN uoms u ON u.id = p.base_uom_id WHERE p.id = ?",
                [$validProductId]
            );
            if ($uomRow && !(bool)(int)$uomRow->allow_decimal && !isWholeNumber($outputQty)) {
                $this->addError("Output quantity must be a whole number for {$uomRow->name}", "output_qty");
            }
        }

        $this->validateItems($items, $validProductId, $companyId);
    }

    private function validateItems(array $items, int $finishedProductId, int $companyId): void
    {
        if (empty($items) || !is_array($items)) {
            $this->addError(validationErrMsg("one_item_required", "component"), "bom_items");
            return;
        }

        // Batch-fetch all products and UOMs referenced by the items
        $inputProductIds = array_values(array_filter(array_map(fn($i) => (int)($i['product_id'] ?? 0), $items)));
        $productMap = [];
        if ($inputProductIds) {
            $ph = implode(',', array_fill(0, count($inputProductIds), '?'));
            $rows = $this->db->fetchAll("SELECT id, company_id, status FROM products WHERE id IN ($ph)", $inputProductIds);
            foreach ($rows as $r) {
                $productMap[(int)$r->id] = $r;
            }
        }

        $inputUomIds = array_values(array_filter(array_map(fn($i) => (int)($i['uom_id'] ?? 0), $items)));
        $uomMap = [];
        if ($inputUomIds) {
            $ph = implode(',', array_fill(0, count($inputUomIds), '?'));
            $rows = $this->db->fetchAll(
                "SELECT pu.id, pu.product_id, pu.company_id, pu.name AS uom_name, u.allow_decimal FROM product_uoms pu JOIN uoms u ON u.id = pu.base_uom_id WHERE pu.id IN ($ph)",
                $inputUomIds
            );
            foreach ($rows as $r) {
                $uomMap[(int)$r->id] = $r;
            }
        }

        $hasMissingProduct     = false;
        $hasInvalidQty         = false;
        $hasInvalidUom         = false;
        $productIds            = [];
        $itemLevelErrors       = [];
        $invalidProductIndices = [];
        $index = 0;

        foreach ($items as $item) {
            $row = $index + 1;
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);
            $uomId = (int) ($item['uom_id'] ?? 0);

            if (!$productId) {
                $hasMissingProduct = true;
                $invalidProductIndices[] = $index;
            } else {
                $product = $productMap[$productId] ?? null;
                if (!$product || $product->company_id != $companyId || $product->status !== 'active') {
                    $itemLevelErrors["bom_items.{$index}.invalid_prod"] = validationErrMsg("invalid", "Component product at row {$row}");
                    $invalidProductIndices[] = $index;
                } elseif ($productId === $finishedProductId) {
                    $itemLevelErrors["bom_items.{$index}.self_ref"] = "Component at row {$row} cannot be the same as the finished product";
                    $invalidProductIndices[] = $index;
                } elseif (in_array($productId, $productIds)) {
                    $itemLevelErrors["bom_items.{$index}.duplicate_prod"] = "Duplicate component product at row {$row}";
                } else {
                    $productIds[] = $productId;
                }
            }

            if (!isPositiveNumeric($qty)) {
                $hasInvalidQty = true;
            } elseif ($uomId && isset($uomMap[$uomId]) && !(bool)(int)$uomMap[$uomId]->allow_decimal && !isWholeNumber($qty)) {
                $itemLevelErrors["bom_items.{$index}.qty"] = "Quantity must be a whole number for {$uomMap[$uomId]->uom_name} at row {$row}";
            }

            if ($uomId && !in_array($index, $invalidProductIndices)) {
                $productUom = $uomMap[$uomId] ?? null;
                if (!$productUom || $productUom->product_id != $productId || $productUom->company_id != $companyId) {
                    $hasInvalidUom = true;
                }
            }

            $index++;
        }

        if ($hasMissingProduct) {
            $this->addError(validationErrMsg("required", "Each component must have a product selected"), "bom_items.product_id");
        }
        if ($hasInvalidQty) {
            $this->addError("Quantity must be greater than zero for all components", "bom_items.qty");
        }
        if ($hasInvalidUom) {
            $this->addError("One or more component UOMs are invalid", "bom_items.uom_id");
        }
        foreach ($itemLevelErrors as $key => $msg) {
            $this->addError($msg, $key);
        }
    }

    private function saveItems(Models_ManufacturingBom $bom, array $items): void
    {
        // Index existing items by product_id for O(1) diff lookup
        $existingByProdId = [];
        foreach ($bom->items as $existing) {
            $existingByProdId[(int) $existing->product_id] = $existing;
        }

        // Batch-fetch UOM codes before the save loop
        $uomIds = array_values(array_filter(array_map(fn($i) => (int)($i['uom_id'] ?? 0), $items)));
        $uomCodeMap = [];
        if ($uomIds) {
            $ph = implode(',', array_fill(0, count($uomIds), '?'));
            $rows = $this->db->fetchAll(
                "SELECT b.id, c.code AS uom_code FROM product_uoms b JOIN uoms c ON c.id = b.base_uom_id WHERE b.id IN ($ph)",
                $uomIds
            );
            foreach ($rows as $r) {
                $uomCodeMap[(int)$r->id] = $r->uom_code;
            }
        }

        $handledProdIds = [];
        $sort = 0;

        foreach ($items as $item) {

            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);
            $uomId = (int) ($item['uom_id'] ?? 0);
            $notes = trim($item['notes'] ?? '') ?: null;
            $uomCode = $uomId ? ($uomCodeMap[$uomId] ?? null) : null;

            if ($productId && isset($existingByProdId[$productId])) {
                // Update existing row
                $bomItem = new Models_ManufacturingBomItem($existingByProdId[$productId]->id);
                $bomItem->qty = $qty;
                $bomItem->product_uom_id = $uomId ?: null;
                $bomItem->uom_code = $uomCode;
                $bomItem->notes = $notes;
                $bomItem->sort_order = $sort;

                if (!$bomItem->update()) {
                    throw new Service_Exception("Failed to update BOM component");
                }

                $handledProdIds[] = $productId;
            } else {
                // Insert new row
                $bomItem = new Models_ManufacturingBomItem();
                $bomItem->company_id     = $bom->company_id;
                $bomItem->bom_id         = $bom->id;
                $bomItem->product_id     = $productId;
                $bomItem->qty            = $qty;
                $bomItem->product_uom_id = $uomId ?: null;
                $bomItem->uom_code       = $uomCode;
                $bomItem->notes          = $notes;
                $bomItem->sort_order     = $sort;

                if (!$bomItem->create()) {
                    throw new Service_Exception("Failed to save BOM component");
                }
            }

            $sort++;
        }

        // Delete rows whose product was removed from the BOM
        foreach ($existingByProdId as $prodId => $existing) {
            if (!in_array($prodId, $handledProdIds)) {
                $bomItem = new Models_ManufacturingBomItem($existing->id);
                $bomItem->delete();
                if (!$bomItem->deletedRows) {
                    throw new Service_Exception("Failed to delete BOM component");
                }
            }
        }
    }

    private function unsetDefaultForProduct(int $companyId, int $productId, int $excludeBomId = 0): void
    {
        $where = "company_id = {$companyId} AND product_id = {$productId} AND is_default = 1";
        if ($excludeBomId) {
            $where .= " AND id != {$excludeBomId}";
        }
        $this->db->update("manufacturing_boms", ['is_default' => 0], $where);
    }

    // ----------------------------------------------------------------
    // Public API
    // ----------------------------------------------------------------

    public function getFormContext(int $bomId): array
    {
        if (!$this->context->canDo('manufacturing_boms', 'read')) {
            throw new Service_Exception("You do not have permission to view BOMs", 403);
        }

        $companyId = $this->context->companyId;

        $bomDetails = [];
        if ($bomId) {
            $bom = $this->getBomOrFail($bomId);
            $bomDetails = array_merge(['id' => $bomId, 'items' => $bom->items], $bom->toArray());
        }

        $sql = "SELECT
                    a.id, a.name, a.sku,
                    b.id   AS uom_id,
                    b.name AS uom_name,
                    c.code AS uom_code,
                    b.is_base AS base_uom
                FROM products AS a
                LEFT JOIN product_uoms AS b ON b.product_id = a.id AND b.status = 'active'
                LEFT JOIN uoms AS c ON c.id = b.base_uom_id
                WHERE a.company_id = ? AND a.status = ?
                ORDER BY a.name ASC";
        $results = $this->db->fetchAll($sql, [$companyId, 'active']);

        $products = [];
        foreach ($results as $row) {
            $id = $row->id;
            if (!isset($products[$id])) {
                $products[$id] = [
                    'id'   => $id,
                    'name' => $row->name,
                    'sku'  => $row->sku,
                    'uoms' => [],
                ];
            }
            if ($row->uom_id) {
                $products[$id]['uoms'][] = [
                    'uom_id'     => $row->uom_id,
                    'name'       => $row->uom_name,
                    'code'       => $row->uom_code,
                    'is_base_uom' => $row->base_uom,
                ];
            }
        }

        return [
            'bom_details' => $bomDetails,
            'products'    => array_values($products),
        ];
    }

    public function getDetails(int $bomId): array
    {
        if (!$this->context->canDo('manufacturing_boms', 'read')) {
            throw new Service_Exception("You do not have permission to view BOMs", 403);
        }

        $bom = $this->getBomOrFail($bomId);
        $details = array_merge(
            ['id' => $bomId, 'product_name' => $bom->product->name, 'items' => $bom->items],
            $bom->toArray()
        );
        return ['bom_details' => $details];
    }

    public function create(array $payload): array
    {
        if (!$this->context->canDo('manufacturing_boms', 'write')) {
            throw new Service_Exception("You do not have permission to create BOMs", 403);
        }

        $this->validatePayload($payload);
        if ($this->hasErrors()) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $companyId = $this->context->companyId;
        $productId = (int) $payload['product_id'];
        $isDefault = !empty($payload['is_default']) ? 1 : 0;

        try {

            $this->db->startTransaction();

            if ($isDefault) {
                $this->unsetDefaultForProduct($companyId, $productId);
            }

            $bom = new Models_ManufacturingBom();
            $bom->company_id = $companyId;
            $bom->product_id = $productId;
            $bom->name = trim($payload['name']);
            $bom->output_qty = (float) ($payload['output_qty'] ?? 1);
            $bom->is_default = $isDefault;
            $bom->notes = trim($payload['notes'] ?? '') ?: null;
            $bom->status = ($payload['status'] ?? '') === 'inactive' ? 'inactive' : 'active';
            $bom->created_by = $this->context->userId;

            if (!$bom->create()) {
                throw new Service_Exception("Failed to create BOM");
            }

            $this->saveItems($bom, $payload['bom_items'] ?? []);

            $this->db->commit();

            return ['success' => true, 'data' => ['bom_id' => $bom->id]];

        } catch (Service_Exception $e) {
            $this->db->rollback();
            throw $e;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Service_Exception("Failed to create BOM");
        }
    }

    public function update(int $bomId, array $payload): array
    {
        if (!$this->context->canDo('manufacturing_boms', 'write')) {
            throw new Service_Exception("You do not have permission to edit BOMs", 403);
        }

        $bom = $this->getBomOrFail($bomId);

        $this->validatePayload($payload);
        if ($this->hasErrors()) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $companyId = $this->context->companyId;
        $productId = (int) $payload['product_id'];
        $isDefault = !empty($payload['is_default']) ? 1 : 0;

        try {

            $this->db->startTransaction();

            if ($isDefault) {
                $this->unsetDefaultForProduct($companyId, $productId, $bomId);
            }

            $bom->product_id = $productId;
            $bom->name = trim($payload['name']);
            $bom->output_qty = (float) ($payload['output_qty'] ?? 1);
            $bom->is_default = $isDefault;
            $bom->status = ($payload['status'] ?? '') === 'inactive' ? 'inactive' : 'active';
            $bom->notes = trim($payload['notes'] ?? '') ?: null;

            if (!$bom->update()) {
                throw new Service_Exception("Failed to update BOM");
            }

            $this->saveItems($bom, $payload['bom_items'] ?? []);

            $this->db->commit();

            return ['success' => true, 'data' => ['bom_id' => $bom->id]];

        } catch (Service_Exception $e) {
            $this->db->rollback();
            throw $e;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Service_Exception("Failed to update BOM");
        }
    }

    public function delete(int $bomId): array
    {
        if (!$this->context->canDo('manufacturing_boms', 'delete')) {
            throw new Service_Exception("You do not have permission to delete BOMs", 403);
        }

        $bom = $this->getBomOrFail($bomId);

        $moCount = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM manufacturing_orders WHERE bom_id = ? AND company_id = ?",
            [$bom->id, $bom->company_id]
        );
        if ((int) $moCount->cnt > 0) {
            throw new Service_Exception("Cannot delete a BOM that has been used in manufacturing orders", 422);
        }

        try {

            $this->db->startTransaction();

            $this->db->delete("manufacturing_bom_items", "bom_id = {$bom->id}");
            $bom->delete();
            if (!$bom->deletedRows) {
                throw new Service_Exception("Failed to delete BOM");
            }

            $this->db->commit();

        } catch (Service_Exception $e) {
            $this->db->rollback();
            throw $e;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Service_Exception("Failed to delete BOM");
        }

        return ['success' => true, 'data' => []];
    }
}
