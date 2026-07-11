<?php
class Service_VendorPricing extends Service_Base {

    private function getRuleOrFail(int $id): object {

        $rule = new Models_VendorProductPrice($id);
        if ($rule->isEmpty) {
            throw new Service_Exception("Price rule not found", 404);
        }
        if ((int)$rule->company_id !== (int)$this->context->companyId) {
            throw new Service_Exception("You do not have permission to access this rule", 403);
        }
        return $rule;
    }

    private function normalizePayload(array &$payload): void {

        $payload['vendor_id']           = (int)($payload['vendor_id'] ?? 0);
        $payload['product_id']          = (int)($payload['product_id'] ?? 0);
        $payload['min_qty']             = (float)($payload['min_qty'] ?? 1);
        $payload['unit_price']          = (float)($payload['unit_price'] ?? 0);
        $payload['discount_type']       = 'percentage';
        $payload['discount_amount']     = (float)($payload['discount_amount'] ?? 0);
        $payload['lead_time_days']      = !empty($payload['lead_time_days']) ? max(1, (int)$payload['lead_time_days']) : null;
        $payload['status']              = in_array($payload['status'] ?? '', ['active', 'inactive']) ? $payload['status'] : 'active';

        $payload['vendor_product_name'] = !empty($payload['vendor_product_name']) ? trim($payload['vendor_product_name']) : null;
        $payload['vendor_product_code'] = !empty($payload['vendor_product_code']) ? trim($payload['vendor_product_code']) : null;
        $payload['start_date']          = !empty($payload['start_date']) ? trim($payload['start_date']) : null;
        $payload['end_date']            = !empty($payload['end_date']) ? trim($payload['end_date']) : null;
    }

    private function validatePayload(array $payload): void {

        $companyId = $this->context->companyId;

        if (empty($payload['vendor_id'])) {
            $this->addError(validationErrMsg("required", "Vendor"), "vendor_id");
        } else {
            $vendor = new Models_Vendor($payload['vendor_id']);
            if ($vendor->isEmpty || (int)$vendor->company_id !== (int)$companyId || $vendor->status !== 'active') {
                $this->addError(validationErrMsg("invalid", "Vendor"), "vendor_id");
            }
        }

        if (empty($payload['product_id'])) {
            $this->addError(validationErrMsg("required", "Product"), "product_id");
        } else {
            $product = new Models_Product($payload['product_id']);
            if ($product->isEmpty || (int)$product->company_id !== (int)$companyId || $product->status !== 'active') {
                $this->addError(validationErrMsg("invalid", "Product"), "product_id");
            }
        }

        if ($payload['min_qty'] <= 0) {
            $this->addError(validationErrMsg("invalid", "Minimum Quantity"), "min_qty");
        }

        if ($payload['unit_price'] < 0) {
            $this->addError(validationErrMsg("invalid", "Unit Price"), "unit_price");
        }

        if ($payload['discount_amount'] < 0) {
            $this->addError(validationErrMsg("invalid", "Discount"), "discount_amount");
        } elseif ($payload['discount_type'] === 'percentage' && $payload['discount_amount'] > 100) {
            $this->addError("Discount percentage cannot exceed 100", "discount_amount");
        }

        if (!empty($payload['start_date']) && !empty($payload['end_date'])) {
            if ($payload['end_date'] < $payload['start_date']) {
                $this->addError("End date must be on or after start date", "end_date");
            }
        }
    }

    private function applyRow(Models_VendorProductPrice $rule, array $payload): void {

        $rule->vendor_id           = $payload['vendor_id'];
        $rule->product_id          = $payload['product_id'];
        $rule->vendor_product_name = $payload['vendor_product_name'];
        $rule->vendor_product_code = $payload['vendor_product_code'];
        $rule->min_qty             = $payload['min_qty'];
        $rule->unit_price          = $payload['unit_price'];
        $rule->discount_type       = $payload['discount_type'];
        $rule->discount_amount     = $payload['discount_amount'];
        $rule->lead_time_days      = $payload['lead_time_days'];
        $rule->start_date          = $payload['start_date'];
        $rule->end_date            = $payload['end_date'];
        $rule->status              = $payload['status'];
    }

    public function create(array $payload): array {

        if (!$this->context->canDo('vendor_pricelists', 'write')) {
            throw new Service_Exception("You do not have permission to create price rules", 403);
        }

        $this->normalizePayload($payload);
        $this->validatePayload($payload);

        if ($this->hasErrors()) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $this->db->startTransaction();
        try {
            $rule             = new Models_VendorProductPrice();
            $rule->company_id = $this->context->companyId;
            $rule->created_by = $this->context->userId;
            $this->applyRow($rule, $payload);

            $id = $rule->create();
            if (!$id) {
                throw new Service_Exception("Failed to create price rule");
            }

            $this->db->commit();
            return ['success' => true, 'data' => ['id' => $id]];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function update(int $id, array $payload): array {

        $rule = $this->getRuleOrFail($id);

        if (!$this->context->canDo('vendor_pricelists', 'write')) {
            throw new Service_Exception("You do not have permission to update price rules", 403);
        }

        $this->normalizePayload($payload);
        $this->validatePayload($payload);

        if ($this->hasErrors()) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $this->db->startTransaction();
        try {
            $this->applyRow($rule, $payload);

            if (!$rule->update()) {
                throw new Service_Exception("Failed to update price rule");
            }

            $this->db->commit();
            return ['success' => true, 'data' => ['id' => $id]];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function delete(int $id): array {

        $rule = $this->getRuleOrFail($id);

        if (!$this->context->canDo('vendor_pricelists', 'delete')) {
            throw new Service_Exception("You do not have permission to delete price rules", 403);
        }

        $rule->delete();
        return ['success' => true];
    }

    public function getForVendor(int $vendorId): array {

        $companyId = $this->context->companyId;
        $today     = date('Y-m-d');

        $sql = "SELECT vpp.id, vpp.product_id, p.name AS product_name, p.sku AS product_sku,
                       vpp.vendor_product_name, vpp.vendor_product_code,
                       vpp.min_qty, vpp.unit_price, vpp.discount_type, vpp.discount_amount,
                       vpp.lead_time_days, vpp.start_date, vpp.end_date, vpp.status
                FROM vendor_product_prices vpp
                JOIN products p ON p.id = vpp.product_id
                WHERE vpp.company_id = ? AND vpp.vendor_id = ? AND vpp.status = 'active'
                  AND (vpp.start_date IS NULL OR vpp.start_date <= ?)
                  AND (vpp.end_date IS NULL OR vpp.end_date >= ?)
                ORDER BY vpp.product_id ASC, vpp.min_qty ASC";

        return $this->db->fetchAll($sql, [$companyId, $vendorId, $today, $today]);
    }

    public function getForProduct(int $productId): array {

        $companyId = $this->context->companyId;

        $sql = "SELECT vpp.id, vpp.vendor_id, v.display_name AS vendor_name,
                       vpp.vendor_product_name, vpp.vendor_product_code,
                       vpp.min_qty, vpp.unit_price, vpp.discount_type, vpp.discount_amount,
                       vpp.lead_time_days, vpp.start_date, vpp.end_date, vpp.status
                FROM vendor_product_prices vpp
                JOIN vendors v ON v.id = vpp.vendor_id
                WHERE vpp.company_id = ? AND vpp.product_id = ?
                ORDER BY v.display_name ASC, vpp.min_qty ASC";

        return $this->db->fetchAll($sql, [$companyId, $productId]);
    }

    public function getList(array $filters = []): array {

        $companyId = $this->context->companyId;
        $where     = ["vpp.company_id = ?"];
        $bindings  = [$companyId];

        if (!empty($filters['vendor_id'])) {
            $where[]    = "vpp.vendor_id = ?";
            $bindings[] = (int)$filters['vendor_id'];
        }

        if (!empty($filters['product_id'])) {
            $where[]    = "vpp.product_id = ?";
            $bindings[] = (int)$filters['product_id'];
        }

        if (!empty($filters['status'])) {
            $where[]    = "vpp.status = ?";
            $bindings[] = $filters['status'];
        }

        $sql = "SELECT vpp.id, vpp.vendor_id, v.display_name AS vendor_name,
                       vpp.product_id, p.name AS product_name, p.sku AS product_sku,
                       vpp.vendor_product_name, vpp.vendor_product_code,
                       vpp.min_qty, vpp.unit_price, vpp.discount_type, vpp.discount_amount,
                       vpp.lead_time_days, vpp.start_date, vpp.end_date, vpp.status, vpp.created_at
                FROM vendor_product_prices vpp
                JOIN vendors v ON v.id = vpp.vendor_id
                JOIN products p ON p.id = vpp.product_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY v.display_name ASC, p.name ASC, vpp.min_qty ASC";

        return $this->db->fetchAll($sql, $bindings);
    }
}
?>
