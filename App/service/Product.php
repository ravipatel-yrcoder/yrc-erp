<?php
class Service_Product extends Service_Base {

    
    private function getSkuProductOrFail(int $id) : Models_Product {

        // validate purchase order and permissions
        $product = new Models_Product($id);
        if( $product->isEmpty ) {
            throw new Service_Exception("The requested product was not found", 404);
        }

        if( $product->company_id != $this->context->companyId ) {
            throw new Service_Exception("You do not have permission to access this product", 403);
        }

        return $product;
    }


    private function isValidProdCategory($categoryId) {
        
        $prodCategory = new Models_ProdCategory($categoryId);
        if( !$prodCategory->isEmpty && $prodCategory->company_id == $this->context->companyId ) {
            return true;
        }

        return false;
    }


    // Currently this is only handled for Simple Product, must handle variants whenever enabled variants support
    private function isUniqueSku($sku, $id=0) {
        
        $sku = strtolower(trim($sku));
        
        $bind = [$sku, $this->context->companyId, "archived"];
        $sql = "SELECT COUNT(id) FROM products
                WHERE lower(sku)=? AND company_id=? AND status <> ?";
        if( $id ) {
            $sql .=" AND id!=?";
            $bind[] = $id;
        }
        
        $count = $this->db->fetchVar($sql, $bind);

        return $count == 0;
    }


    private function isValidUom($baseUomId) {

        $bind = [$baseUomId, "active"];
        $sql = "SELECT COUNT(id) FROM uoms
                WHERE id=? AND status=?";
        
        $count = $this->db->fetchVar($sql, $bind);

        return $count == 1;
    }

    private function isValidPurchaseTaxes($purchase_tax_ids) {
        $placeholderIds = implode(',', array_fill(0, count($purchase_tax_ids), '?'));
        $bind = array_merge($purchase_tax_ids, [$this->context->companyId, "active"]);
        $sql = "SELECT COUNT(id) FROM taxes WHERE id IN ($placeholderIds) AND company_id=? AND status=?";
        $count = $this->db->fetchVar($sql, $bind);
        return $count == count($purchase_tax_ids);
    }

    private function isValidSalesTaxes($sales_tax_ids) {
        $placeholderIds = implode(',', array_fill(0, count($sales_tax_ids), '?'));
        $bind = array_merge($sales_tax_ids, [$this->context->companyId, "active"]);
        $sql = "SELECT COUNT(id) FROM taxes WHERE id IN ($placeholderIds) AND company_id=? AND status=?";
        $count = $this->db->fetchVar($sql, $bind);
        return $count == count($sales_tax_ids);
    }

    private function validatePayload(array $payload) {

        $id = $payload['id'] ?? "";
        $structureType = $payload['structure_type'] ?? ""; 
        $name = $payload['name'] ?? ""; 
        $sku = $payload['sku'] ?? "";
        $baseUomId = $payload['base_uom_id'] ?? 0; 
        $categoryId = $payload['category_id'] ?? 0;
        $productType = $payload['type'] ?? "";
        $stockTrackingMethod = $payload['stock_tracking_method'] ?? "";
        $salePrice = (float) $payload['sale_price'] ?? "";
        $costPrice = (float) $payload['cost_price'] ?? "";
        $salesTaxes = $payload['sales_taxes'] ?? [];
        $purchaseTaxes = $payload['purchase_taxes'] ?? [];
        $status = $payload['status'] ?? "";

        if( empty($structureType) || !in_array($structureType, ['simple','variable']) ) {
            $this->addError(validationErrMsg("missing_or_invalid", "Structure type"), "structure_type");
        }

        if( empty($name) ) {
            $this->addError(validationErrMsg("required", "Name"), "name");
        }

        if( !empty($sku) && !$this->isUniqueSku($sku, $id) ) {
            $this->addError(validationErrMsg("duplicate", "SKU"), "sku");
        }

        if( empty($baseUomId) || !$this->isValidUom($baseUomId) ) {
            $this->addError(validationErrMsg("missing_or_invalid", "UOM"), "base_uom_id");
        }

        if( !empty($categoryId) && !$this->isValidProdCategory($categoryId) ) {
            $this->addError(validationErrMsg("invalid", "Category"), "category_id");
        }

        if( empty($productType) || !in_array($productType, ['goods','service','combo'])) {
            $this->addError(validationErrMsg("missing_or_invalid", "Product type"), "type");
        }

        if( empty($stockTrackingMethod) || !in_array($stockTrackingMethod, ['none','quantity','lot','serial'])) {
            $this->addError(validationErrMsg("missing_or_invalid", "Stock tracking method"), "stock_tracking_method");
        }

        if( $salePrice && !isValidPrice($salePrice) ) {
            $this->addError(validationErrMsg("invalid_price", "Sale price"), "sale_price");
        }

        if( $costPrice && !isValidPrice($costPrice) ) {
            $this->addError(validationErrMsg("invalid_price", "Cost"), "cost_price");
        }

        if( !empty($salesTaxes) && !$this->isValidSalesTaxes($salesTaxes) ) {
            $this->addError(validationErrMsg("invalid", "Sales Tax"), "sales_taxes[]");
        }        

        if( !empty($purchaseTaxes) && !$this->isValidPurchaseTaxes($purchaseTaxes) ) {
            $this->addError(validationErrMsg("invalid", "Purchase Tax"), "purchase_taxes[]");
        }

        
        if(!in_array($status, ['active','inactive','archived'])) {
            $this->addError(validationErrMsg("missing_or_invalid", "Status"), "status");
        }



        if( $structureType === "variable" ) {

            $this->addError("Variable products are not supported yet", "structure_type");

            // validate sku items for variable products
            // logic is not implemented yet
        }
    }

    private function hasBaseUomChanged(Models_Product $product, $baseUomId) {
        return (int) $baseUomId !== (int) $product->base_uom_id;
    }

    private function validateImmutableFields(Models_Product $product, array $payload) {

        $uomChanged        = $this->hasBaseUomChanged($product, $payload['base_uom_id'] ?? 0);
        $trackStockChanged = isset($payload['stock_tracking_method']) && $payload['stock_tracking_method'] !== $product->stock_tracking_method;

        if( $uomChanged || $trackStockChanged ) {

            $companyId = $this->context->companyId;
            $productId = $product->id;

            // Check existing stock records
            $stockCount = (int) $this->db->fetchVar(
                "SELECT COUNT(id) FROM inv_product_stock WHERE company_id = ? AND product_id = ?",
                [$companyId, $productId]
            );

            // Count active (draft/confirmed) SO + PO lines — completed and cancelled orders do not block changes
            $openOrderCount = (int) $this->db->fetchVar(
                "SELECT SUM(cnt) FROM (
                     SELECT COUNT(soi.id) AS cnt
                     FROM sales_order_items soi
                     INNER JOIN sales_orders so ON so.id = soi.sales_order_id
                     WHERE soi.product_id = ? AND so.company_id = ? AND so.status IN ('draft', 'confirmed')
                     UNION ALL
                     SELECT COUNT(poi.id) AS cnt
                     FROM purchase_order_items poi
                     INNER JOIN purchase_orders po ON po.id = poi.purchase_order_id
                     WHERE poi.product_id = ? AND po.company_id = ? AND po.status IN ('draft', 'confirmed')
                 ) AS open_lines",
                [$productId, $companyId, $productId, $companyId]
            );

            if( $uomChanged && $stockCount > 0 ) {
                $this->addError("UOM cannot be changed because inventory records exist", "base_uom_id");
            }

            if( $trackStockChanged ) {

                $currentMethod = $product->stock_tracking_method;
                $newMethod     = $payload['stock_tracking_method'];
                $isEnabling    = ($currentMethod === 'none' || $currentMethod === null);

                if( $isEnabling ) {
                    // none → tracked: only block if open orders exist
                    if( $openOrderCount > 0 ) {
                        $this->addError("Inventory tracking cannot be enabled because this product has active orders", "stock_tracking_method");
                    }
                } else {
                    // tracked → none or between tracked methods: block if stock records OR open orders exist
                    if( $stockCount > 0 ) {
                        $errorField = ($newMethod === 'none') ? "track_inventory" : "stock_tracking_method";
                        $this->addError("Tracking method cannot be changed because inventory records exist", $errorField);
                    } elseif( $openOrderCount > 0 ) {
                        $errorField = ($newMethod === 'none') ? "track_inventory" : "stock_tracking_method";
                        $this->addError("Tracking method cannot be changed because this product has active orders", $errorField);
                    }
                }
            }
        }
    }


    private function validateAndUploadImage(array $image) {
        
        if( $image ) {
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $validate = Helpers_FileUpload::validate($image, $allowedTypes, 1);
            if( $validate["valid"] === true ) {

                $file = Helpers_FileUpload::save($image, ROOT_PATH."/Public/uploads/".$this->context->companyId."/".date("Y")."/".date("m"));
                return $file["url"];
            }
            else
            {
                $this->addError($validate["error"], "image_url");
            }
        } else {
            $this->addError("Missing image", "image_url");
        }

        return false;
    }


    private function validateDeletionAllowed(int $productId) {

        $checks = [
            "SELECT COUNT(id) FROM inv_adjustments                    WHERE product_id = ?",
            "SELECT COUNT(id) FROM inv_product_stock                  WHERE product_id = ?",
            "SELECT COUNT(id) FROM inv_stock_movements                WHERE product_id = ?",
            "SELECT COUNT(id) FROM inv_serials                        WHERE product_id = ?",
            "SELECT COUNT(id) FROM purchase_order_items               WHERE product_id = ?",
            "SELECT COUNT(id) FROM sales_order_items                  WHERE product_id = ?",
            "SELECT COUNT(id) FROM manufacturing_boms                 WHERE product_id = ?",
            "SELECT COUNT(id) FROM manufacturing_bom_items            WHERE product_id = ?",
            "SELECT COUNT(id) FROM manufacturing_order_material_items WHERE product_id = ?",
        ];

        foreach ($checks as $sql) {
            if ((int) $this->db->fetchVar($sql, [$productId]) > 0) {
                throw new Service_Exception("Product cannot be deleted because related records exist");
            }
        }
    }


    private function createMasterProduct(array $payload) {

        $master = new Models_ProductMaster();
        $master->fillFromArray($payload);
        $master->company_id = $this->context->companyId;
        $master->created_by = $this->context->userId;
        $masterId = $master->create();
        if( !$masterId ) {
            throw new Exception("Failed to create product");
        }

        return $masterId;
    }


    private function createSkuProduct(int $masterProdId, array $payload) {

        $product = new Models_Product();
        $product->fillFromArray($payload);
        $product->master_id = $masterProdId;
        $product->company_id = $this->context->companyId;
        $product->created_by = $this->context->userId;
        $skuProdId = $product->create();
        if( !$skuProdId ) {
            throw new Service_Exception("Failed to create product");
        }
        
        return $skuProdId;
    }


    private function saveProductTaxes(int $productId, string $taxType, $taxes) {

        $companyId = $this->context->companyId;
        $userId = $this->context->userId;

        $prodTax = new Models_ProductDefaultTax();
        $savedProdTaxes = $prodTax->getAll(["tax_id"], ["company_id" => $companyId, "product_id" => $productId, "apply_on" => $taxType]);
        $savedProdTaxIds = [];
        foreach($savedProdTaxes as $savedProdTax) {
            $savedProdTaxIds[] = $savedProdTax->tax_id;
        }

        $idsToCreate = array_values(array_diff($taxes, $savedProdTaxIds));
        $idsToDelete = array_values(array_diff($savedProdTaxIds, $taxes));
        
        //$idsToUpdate = array_values(array_intersect($savedProdTaxIds, $taxes));

        // create
        foreach($idsToCreate as $taxId) {
            $prodTax = new Models_ProductDefaultTax();
            $prodTax->company_id = $companyId;
            $prodTax->product_id = $productId;
            $prodTax->tax_id = $taxId;
            $prodTax->apply_on = $taxType;
            $prodTax->created_by = $userId;
            $taxId = $prodTax->create();
            if( !$taxId ) {                        
                throw new Service_Exception("Failed to save {$taxType} tax");
            }
        }

        // delete
        if( $idsToDelete ) {
            $prodTax = new Models_ProductDefaultTax();
            $prodTax->delete("product_id={$productId} AND tax_id IN(".implode(",", $idsToDelete).") AND apply_on='{$taxType}'");
            if( !$prodTax->getDeletedRows() ) {
                throw new Service_Exception("Failed to save {$taxType} tax");
            }
        }
    }


    // UOM Change validation should applied before calling this method
    private function saveBaseUomAsProductUoms(int $productId, int $baseUomId, int $oldBaseUomId=0) {

        $companyId = $this->context->companyId;
        $userId = $this->context->userId;
        $uom = new Models_Uom($baseUomId);


        if( $uom->isEmpty ) {
            throw new Service_Exception("Failed to save UOM configuration");
        }

        
        // Delete existing base uom from product_uoms if exist
        if( $oldBaseUomId ) {
            $productUom = new Models_ProductUom();
            $productUom->fetchByProperty(["company_id", "product_id", "base_uom_id", "is_base"], [$companyId, $productId, $oldBaseUomId, 1]);
            if( !$productUom->isEmpty ) {
                
                // delete
                $productUom->delete();
                if( !$productUom->getDeletedRows() > 0 ) {
                    throw new Service_Exception("Failed to save UOM configuration");
                }
            }
        }        


        // create new base uom in product_uoms
        $productUom = new Models_ProductUom();
        $productUom->company_id = $companyId;
        $productUom->product_id = $productId;
        $productUom->name = $uom->name;        
        $productUom->base_uom_id = $uom->id;
        $productUom->conversion_factor = 1;
        $productUom->status = 'active';
        $productUom->is_base = 1;
        $productUom->created_by = $userId;
        if( !$productUom->create() ) {
            throw new Service_Exception("Failed to save UOM configuration");
        }
    }


    /**
     * Retrive add/edit form context data
     */
    public function getFormContext(int $skuProdId) : array {
        
        $companyId = $this->context->companyId;

        $productDetails = [];
        if( $skuProdId ) {

            $product = $this->getSkuProductOrFail($skuProdId);

            $purchaseTaxes = array_column($product->getTaxes("purchase"), "tax_id");
            $salesTaxes = array_column($product->getTaxes("sale"), "tax_id");

            $productDetails = array_merge([
                'id'                      => $skuProdId,
                'sales_taxes'             => $salesTaxes,
                'purchase_taxes'          => $purchaseTaxes,
                'category_id'             => $product->master->category_id,
                'type'                    => $product->master->type,
                'tax_classification_type' => $product->master->tax_classification_type,
                'tax_classification_code' => $product->master->tax_classification_code,
            ], $product->toArray());
        }

        $categories = Models_ProdCategory::getCategories($companyId, "tree");
        
        $uom = new Models_Uom();
        $baseUoms = $uom->getAll(["id", "name", "code"], ["status" => "active"]);

        $tax = new Models_Tax();
        $allTaxes = $tax->getAll(["id", "name", "code"], ["company_id" => $companyId, "status" => "active"]);

        $data = [
            'categories'      => $categories,
            'base_uoms'       => $baseUoms,
            'purchase_taxes'  => $allTaxes,
            'sales_taxes'     => $allTaxes,
            'product_details' => $productDetails,
            'cost_method'     => $this->getCostMethod(),
        ];

        return $data;
    }


    public function create(array $payload) {

        if (!$this->context->canDo('products', 'write')) {
            throw new Service_Exception('You do not have permission to create products', 403);
        }

        // Validate incoming data
        $this->validatePayload($payload);


        // validate and upload image
        if( !empty($payload["image"]) ) {
            $imgUrl = $this->validateAndUploadImage($payload["image"]);
            if( $imgUrl !== false ) {
                $payload["image_url"] = $imgUrl;
            }
        }        

        if ($this->hasErrors()) {
            return [
                "success" => false,
                "errors"  => $this->getErrors()
            ];
        }


        // Begin transaction
        $this->db->startTransaction();

        try {

            // create master product if require
            $masterId = $payload["master_id"] ?? 0;
            if( empty($masterId) ) {
                $masterId = $this->createMasterProduct($payload);
            }

            // create sku product
            $skuProductId = $this->createSkuProduct($masterId, $payload);

            // For Standard pricing, current_cost mirrors cost_price
            if ($this->getCostMethod() === 'standard') {
                $costPrice = isset($payload['cost_price']) && $payload['cost_price'] !== '' ? (float)$payload['cost_price'] : null;
                $this->db->query(
                    "UPDATE products SET current_cost = ? WHERE id = ? AND company_id = ?",
                    [$costPrice, $skuProductId, $this->context->companyId]
                );
            }


            // save product uoms
            $baseUomId = $payload['base_uom_id'] ?? 0; 
            $this->saveBaseUomAsProductUoms($skuProductId, $baseUomId);
            

            // save purchase taxes
            $purchaseTaxes = $payload["purchase_taxes"] ?? [];
            $this->saveProductTaxes($skuProductId, "purchase", $purchaseTaxes);            


            // create sales taxes
            $salesTaxes = $payload["sales_taxes"] ?? [];
            $this->saveProductTaxes($skuProductId, "sale", $salesTaxes);


            // Commit
            $this->db->commit();

            return [
                "success" => true,
                "data" => [
                    "masterId" => $masterId,
                    "id" => $skuProductId,
                ],
            ];

        } catch (Exception $e) {

            $this->db->rollback();
            throw $e;
        }
    }


    public function update(int $prodId, array $payload) {

        if (!$this->context->canDo('products', 'write')) {
            throw new Service_Exception('You do not have permission to update products', 403);
        }

        $product = $this->getSkuProductOrFail($prodId);

        // Validate payload
        $this->validatePayload($payload);

        // Validate UOM & Tracking Method fields
        $this->validateImmutableFields($product, $payload);
        
        // validate and upload image
        if( !empty($payload["image"]) ) {
            $imgUrl = $this->validateAndUploadImage($payload["image"]);
            if( $imgUrl !== false ) {
                $payload["image_url"] = $imgUrl;
            }
        }

        if ($this->hasErrors()) {
            return [
                "success" => false,
                "errors"  => $this->getErrors()
            ];
        }

        // Begin transaction
        $this->db->startTransaction();
        
        try {

            $baseUomId = $payload['base_uom_id'] ?? 0;
            $oldBaseUomId = $product->base_uom_id;
            $uomChanged = $this->hasBaseUomChanged($product, $baseUomId);

            // update master product
            $masterProduct = $product->master;
            $masterProduct->fillFromArray($payload, ["company_id", "created_by", "created_at"]);
            if( !$masterProduct->update() ) {
                throw new Service_Exception("Failed to update product");
            }

            $product->fillFromArray($payload, ["company_id", "master_id", "created_by", "created_at"]);
            if( !$product->update() ) {
                throw new Service_Exception("Failed to update product");
            }

            // For Standard pricing, current_cost mirrors cost_price
            if ($this->getCostMethod() === 'standard') {
                $costPrice = isset($payload['cost_price']) && $payload['cost_price'] !== '' ? (float)$payload['cost_price'] : null;
                $this->db->query(
                    "UPDATE products SET current_cost = ? WHERE id = ? AND company_id = ?",
                    [$costPrice, $prodId, $this->context->companyId]
                );
            }


            // update base uom in product_uoms if base uom changed
            if( $uomChanged ) {
                $this->saveBaseUomAsProductUoms($prodId, $baseUomId, $oldBaseUomId);
            }            


            // save purchase taxes
            $purchaseTaxes = $payload["purchase_taxes"] ?? [];
            $this->saveProductTaxes($prodId, "purchase", $purchaseTaxes);            


            // create sales taxes
            $salesTaxes = $payload["sales_taxes"] ?? [];
            $this->saveProductTaxes($prodId, "sale", $salesTaxes);



            // Commit
            $this->db->commit();

            return [
                "success" => true,
                "data" => [],
            ];

        } catch (Exception $e) {

            $this->db->rollback();
            throw $e;
        }


    }


    public function delete(int $prodId) {

        if (!$this->context->canDo('products', 'delete')) {
            throw new Service_Exception('You do not have permission to delete products', 403);
        }

        $product = $this->getSkuProductOrFail($prodId);

        $this->validateDeletionAllowed($prodId);

        // Begin transaction
        $this->db->startTransaction();

        try {

            $masterProdId = $product->master_id;

            $product->delete();
            if( !$product->getDeletedRows() > 0 ) {
                throw new Service_Exception("Failed to delete product");
            }

            // delete master product
            $masterProd = new Models_ProductMaster($masterProdId);
            $masterProd->delete();
            if( !$masterProd->getDeletedRows() > 0 ) {
                throw new Service_Exception("Failed to delete product");
            }

            // Delete product taxes
            $prodTax = new Models_ProductDefaultTax();
            $prodTax->delete("company_id={$this->context->companyId} AND product_id={$prodId}");

            // Commit
            $this->db->commit();

            return [
                "success" => true,
                "data" => [],
            ];

        } catch (Exception $e) {

            $this->db->rollback();
            throw $e;
        }

    }

    /**
     * Resolves the active inventory cost method for a product.
     * Phase 1: company-level only. The $productId param is reserved for future
     * per-product / per-category overrides — pass 0 when product-level isn't needed.
     */
    public function getCostMethod(int $productId = 0): string {
        return (new Service_CompanySettings($this->context))->get('inventory.cost_method', 'standard');
    }


    /**
     * Updates products.current_cost after an incoming stock movement.
     * Called by Service_Inv_Movement after adjust_in or purchase_receipt.
     */
    public function updateCurrentCost(int $productId, float $incomingQty, float $incomingCost): void {
        switch ($this->getCostMethod($productId)) {
            case 'avco':
                $this->applyAvcoUpdate($productId, $incomingQty, $incomingCost);
                break;
            case 'standard':
                // Standard price is managed manually via the product form — never auto-updated.
                break;
        }
    }


    private function applyAvcoUpdate(int $productId, float $incomingQty, float $incomingCost): void {
        $companyId = $this->context->companyId;

        // Stock quantity already includes the incoming qty at this point (movement already recorded).
        $currentStock = (float) $this->db->fetchVar(
            "SELECT COALESCE(SUM(unrestricted_qty), 0) FROM inv_product_stock
             WHERE product_id = ? AND company_id = ?",
            [$productId, $companyId]
        );

        $currentCost = (float) $this->db->fetchVar(
            "SELECT COALESCE(current_cost, cost_price, 0) FROM products WHERE id = ? AND company_id = ?",
            [$productId, $companyId]
        );

        $previousStock = max(0.0, $currentStock - $incomingQty);
        $total         = $previousStock + $incomingQty;
        $newCost       = $total > 0
            ? (($previousStock * $currentCost) + ($incomingQty * $incomingCost)) / $total
            : $incomingCost;

        $this->db->query(
            "UPDATE products SET current_cost = ? WHERE id = ? AND company_id = ?",
            [round($newCost, 4), $productId, $companyId]
        );
    }


    public function search(string $q): array
    {
        if (trim($q) === '') {
            return [];
        }

        $companyId = $this->context->companyId;
        $like = '%' . $q . '%';

        return $this->db->fetchAll(
            "SELECT p.id, p.name
             FROM products AS p
             INNER JOIN product_masters AS pm ON pm.id = p.master_id
             WHERE pm.company_id = ?
               AND pm.status <> 'archived'
               AND p.stock_tracking_method IN ('quantity', 'lot', 'serial')
               AND p.name LIKE ?
             ORDER BY p.name ASC
             LIMIT 20",
            [$companyId, $like]
        );
    }


    public function import(array $rows): array
    {
        if (!$this->context->canDo('products', 'write')) {
            throw new Service_Exception('You do not have permission to import products', 403);
        }

        $companyId = $this->context->companyId;

        // --- Build lookup maps (single DB round-trip each) ---

        $uomMap = [];
        foreach ($this->db->fetchAll("SELECT id, code FROM uoms WHERE status = 'active'") as $r) {
            $uomMap[strtoupper(trim($r->code))] = (int) $r->id;
        }

        $categoryMap = [];
        foreach ($this->db->fetchAll("SELECT id, name FROM product_categories WHERE company_id = ? AND status = 'active'", [$companyId]) as $r) {
            $categoryMap[strtolower(trim($r->name))] = (int) $r->id;
        }

        $taxMap = [];
        foreach ($this->db->fetchAll("SELECT id, code FROM taxes WHERE company_id = ? AND status = 'active'", [$companyId]) as $r) {
            $taxMap[strtolower(trim($r->code))] = (int) $r->id;
        }

        $existingSkus = [];
        foreach ($this->db->fetchAll("SELECT sku FROM products WHERE company_id = ? AND status <> 'archived' AND sku IS NOT NULL AND sku <> ''", [$companyId]) as $r) {
            $existingSkus[strtolower(trim($r->sku))] = true;
        }

        // --- Phase 1: Validate all rows, collect all errors ---

        $errors       = [];
        $seenSkus     = [];
        $validTypes   = ['goods', 'service'];
        $validMethods = ['none', 'quantity', 'serial'];

        foreach ($rows as $i => $row) {
            $rowNum  = $i + 2; // +1 for header, +1 for 1-based
            $name    = trim($row[0]);
            $sku     = trim($row[1]);
            $uomCode = strtoupper(trim($row[3]));
            $type    = strtolower(trim($row[5]));
            $classCode = trim($row[6] ?? '');
            $method  = strtolower(trim($row[7]));
            $salePrice   = str_replace(',', '', trim($row[8]));
            $salesTaxRaw = trim($row[9]);
            $costPrice   = str_replace(',', '', trim($row[10]));
            $purchTaxRaw = trim($row[11] ?? '');

            if (empty($name)) {
                $errors[] = ['row' => $rowNum, 'column' => 'Product', 'message' => 'Product name is required'];
            }

            if (!empty($sku)) {
                $skuKey = strtolower($sku);
                if (isset($existingSkus[$skuKey])) {
                    $errors[] = ['row' => $rowNum, 'column' => 'Sku', 'message' => "SKU '{$sku}' already exists"];
                } elseif (isset($seenSkus[$skuKey])) {
                    $errors[] = ['row' => $rowNum, 'column' => 'Sku', 'message' => "SKU '{$sku}' is duplicated in the file"];
                } else {
                    $seenSkus[$skuKey] = true;
                }
            }

            if (empty($uomCode) || !isset($uomMap[$uomCode])) {
                $errors[] = ['row' => $rowNum, 'column' => 'UOM', 'message' => empty($uomCode) ? 'UOM is required' : "UOM code '{$uomCode}' not found"];
            }

            if (empty($type) || !in_array($type, $validTypes)) {
                $errors[] = ['row' => $rowNum, 'column' => 'Product Type', 'message' => empty($type) ? 'Product Type is required' : "Invalid Product Type '{$row[5]}'. Must be: goods, service"];
            }

            if (empty($method) || !in_array($method, $validMethods)) {
                $errors[] = ['row' => $rowNum, 'column' => 'Tracking Method', 'message' => empty($method) ? 'Tracking Method is required' : "Invalid Tracking Method '{$row[7]}'. Must be: none, quantity, serial"];
            }

            if ($salePrice !== '' && !isValidPrice((float) $salePrice)) {
                $errors[] = ['row' => $rowNum, 'column' => 'Sales Price', 'message' => "Invalid Sales Price '{$salePrice}'"];
            }

            if ($costPrice !== '' && !isValidPrice((float) $costPrice)) {
                $errors[] = ['row' => $rowNum, 'column' => 'Cost', 'message' => "Invalid Cost '{$costPrice}'"];
            }

            if ($salesTaxRaw !== '') {
                foreach (array_map('trim', explode(',', $salesTaxRaw)) as $code) {
                    if ($code !== '' && !isset($taxMap[strtolower($code)])) {
                        $errors[] = ['row' => $rowNum, 'column' => 'Sales Taxes', 'message' => "Sales tax code '{$code}' not found"];
                    }
                }
            }

            if ($purchTaxRaw !== '') {
                foreach (array_map('trim', explode(',', $purchTaxRaw)) as $code) {
                    if ($code !== '' && !isset($taxMap[strtolower($code)])) {
                        $errors[] = ['row' => $rowNum, 'column' => 'Purchase Taxes', 'message' => "Purchase tax code '{$code}' not found"];
                    }
                }
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // --- Phase 2: Import all rows in a single transaction ---

        $this->db->startTransaction();
        try {

            $imported = 0;
            foreach ($rows as $row) {
                $name      = trim($row[0]);
                $sku       = trim($row[1]) ?: null;
                $desc      = trim($row[2]) ?: null;
                $uomCode   = strtoupper(trim($row[3]));
                $catName   = trim($row[4]);
                $type      = strtolower(trim($row[5]));
                $classCode = trim($row[6] ?? '') ?: null;
                $method    = strtolower(trim($row[7]));
                $salePriceStr = str_replace(',', '', trim($row[8]));
                $salePrice    = $salePriceStr !== '' ? (float) $salePriceStr : null;
                $salesTaxRaw  = trim($row[9]);
                $costPriceStr = str_replace(',', '', trim($row[10]));
                $costPrice    = $costPriceStr !== '' ? (float) $costPriceStr : null;
                $purchTaxRaw  = trim($row[11] ?? '');

                // Resolve or auto-create category
                $categoryId = null;
                if ($catName !== '') {
                    $catKey = strtolower($catName);
                    if (isset($categoryMap[$catKey])) {
                        $categoryId = $categoryMap[$catKey];
                    } else {
                        $cat             = new Models_ProdCategory();
                        $cat->company_id = $companyId;
                        $cat->name       = $catName;
                        $cat->status     = 'active';
                        $newCatId = $cat->create();
                        if (!$newCatId) {
                            throw new Service_Exception("Failed to create category '{$catName}'");
                        }
                        $categoryMap[$catKey] = $newCatId;
                        $categoryId = $newCatId;
                    }
                }

                // Resolve tax IDs from codes
                $salesTaxIds = [];
                if ($salesTaxRaw !== '') {
                    foreach (array_map('trim', explode(',', $salesTaxRaw)) as $code) {
                        if ($code !== '') {
                            $salesTaxIds[] = $taxMap[strtolower($code)];
                        }
                    }
                }

                $purchTaxIds = [];
                if ($purchTaxRaw !== '') {
                    foreach (array_map('trim', explode(',', $purchTaxRaw)) as $code) {
                        if ($code !== '') {
                            $purchTaxIds[] = $taxMap[strtolower($code)];
                        }
                    }
                }

                $classType = match($type) {
                    'goods'   => 'hsn',
                    'service' => 'sac',
                    default   => null,
                };

                $payload = [
                    'name'                    => $name,
                    'sku'                     => $sku,
                    'description'             => $desc,
                    'type'                    => $type,
                    'tax_classification_type' => $classType,
                    'tax_classification_code' => $classCode,
                    'structure_type'          => 'simple',
                    'stock_tracking_method'   => $method,
                    'base_uom_id'             => $uomMap[$uomCode],
                    'category_id'             => $categoryId,
                    'sale_price'              => $salePrice,
                    'cost_price'              => $costPrice,
                    'status'                  => 'active',
                ];

                $masterId = $this->createMasterProduct($payload);
                $skuId    = $this->createSkuProduct($masterId, $payload);
                $this->saveBaseUomAsProductUoms($skuId, $uomMap[$uomCode]);

                if (!empty($salesTaxIds)) {
                    $this->saveProductTaxes($skuId, 'sale', $salesTaxIds);
                }
                if (!empty($purchTaxIds)) {
                    $this->saveProductTaxes($skuId, 'purchase', $purchTaxIds);
                }

                $imported++;
            }

            $this->db->commit();
            return ['success' => true, 'data' => ['imported' => $imported]];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}