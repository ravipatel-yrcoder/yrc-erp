<?php
class Service_ExtraCharge extends Service_Base {

    private static array $validAppliesTo    = ['sales', 'purchase', 'both'];
    private static array $validRefundPolicy = ['never', 'pro_rata', 'full_only', 'manual'];

    public function list(?string $appliesTo = null): array {
        $companyId = $this->context->companyId;
        $params    = [$companyId];
        $filter    = '';
        if ($appliesTo === 'sales') {
            $filter = "AND ec.applies_to IN ('sales','both')";
        } elseif ($appliesTo === 'purchase') {
            $filter = "AND ec.applies_to IN ('purchase','both')";
        }
        return $this->db->fetchAll(
            "SELECT ec.*, t.name AS default_tax_name, t.rate AS default_tax_rate
             FROM extra_charges ec
             LEFT JOIN taxes t ON t.id = ec.default_tax_id
             WHERE ec.company_id = ? AND ec.is_active = 1 $filter
             ORDER BY ec.sort_order ASC, ec.name ASC",
            $params
        );
    }

    public function listAll(): array {
        return $this->db->fetchAll(
            "SELECT ec.*, t.name AS default_tax_name, t.rate AS default_tax_rate
             FROM extra_charges ec
             LEFT JOIN taxes t ON t.id = ec.default_tax_id
             WHERE ec.company_id = ?
             ORDER BY ec.sort_order ASC, ec.name ASC",
            [$this->context->companyId]
        );
    }

    public function create(array $inputs): array {
        $this->resetErrors();
        $fields = $this->validateInputs($inputs);
        if ($this->hasErrors()) {
            return ['success' => false, 'data' => [], 'errors' => $this->getErrors()];
        }

        $ec               = new Models_ExtraCharge();
        $ec->company_id   = $this->context->companyId;
        $ec->name         = $fields['name'];
        $ec->applies_to   = $fields['applies_to'];
        $ec->default_tax_id = $fields['default_tax_id'];
        $ec->sac_code     = $fields['sac_code'];
        $ec->refund_policy = $fields['refund_policy'];
        $ec->affects_landed_cost = $fields['affects_landed_cost'];
        $ec->is_active    = $fields['is_active'];
        $ec->sort_order   = $fields['sort_order'];

        $newId = $ec->create();
        if (!$newId) {
            return ['success' => false, 'data' => [], 'errors' => ['general' => 'Failed to save extra charge.']];
        }

        $row = $this->getById((int) $newId);
        return ['success' => true, 'data' => $row, 'errors' => []];
    }

    public function update(int $id, array $inputs): array {
        $this->resetErrors();
        $ec = $this->getOwnedOrFail($id);

        $fields = $this->validateInputs($inputs);
        if ($this->hasErrors()) {
            return ['success' => false, 'data' => [], 'errors' => $this->getErrors()];
        }

        $ec->name                = $fields['name'];
        $ec->applies_to          = $fields['applies_to'];
        $ec->default_tax_id      = $fields['default_tax_id'];
        $ec->sac_code            = $fields['sac_code'];
        $ec->refund_policy       = $fields['refund_policy'];
        $ec->affects_landed_cost = $fields['affects_landed_cost'];
        $ec->is_active           = $fields['is_active'];
        $ec->sort_order          = $fields['sort_order'];
        $ec->update();

        $row = $this->getById($id);
        return ['success' => true, 'data' => $row, 'errors' => []];
    }

    public function delete(int $id): array {
        $this->resetErrors();
        $this->getOwnedOrFail($id);

        $used = $this->db->fetchOne(
            "SELECT 1
             FROM (
                 SELECT extra_charge_id FROM sales_order_extra_charges            WHERE extra_charge_id = ?
                 UNION ALL
                 SELECT extra_charge_id FROM purchase_order_extra_charges         WHERE extra_charge_id = ?
                 UNION ALL
                 SELECT extra_charge_id FROM sales_proforma_invoice_extra_charges WHERE extra_charge_id = ?
             ) refs
             LIMIT 1",
            [$id, $id, $id]
        );

        if ($used) {
            throw new Service_Exception(
                'This charge type is used on existing documents and cannot be deleted. Deactivate it instead.',
                422
            );
        }

        $this->db->query(
            "DELETE FROM extra_charges WHERE id = ? AND company_id = ?",
            [$id, $this->context->companyId]
        );
        return ['success' => true];
    }

    private function validateInputs(array $inputs): array {
        $name      = trim($inputs['name'] ?? '');
        $appliesTo = trim($inputs['applies_to'] ?? '');
        $refundPolicy = trim($inputs['refund_policy'] ?? 'manual');
        $sacCode   = trim($inputs['sac_code'] ?? '') ?: null;
        $defaultTaxId = isset($inputs['default_tax_id']) ? (int) $inputs['default_tax_id'] : null;
        $affectsLandedCost = isset($inputs['affects_landed_cost']) ? (int)(bool) $inputs['affects_landed_cost'] : 0;
        $isActive  = isset($inputs['is_active']) ? (int)(bool) $inputs['is_active'] : 1;
        $sortOrder = isset($inputs['sort_order']) ? (int) $inputs['sort_order'] : 0;

        if ($name === '') {
            $this->addError('Name is required.', 'name');
        } elseif (mb_strlen($name) > 150) {
            $this->addError('Name must be 150 characters or fewer.', 'name');
        }

        if (!in_array($appliesTo, self::$validAppliesTo, true)) {
            $this->addError('Invalid value for Applies To.', 'applies_to');
        }

        if (!in_array($refundPolicy, self::$validRefundPolicy, true)) {
            $this->addError('Invalid refund policy.', 'refund_policy');
        }

        if ($sacCode !== null && mb_strlen($sacCode) > 20) {
            $this->addError('SAC Code must be 20 characters or fewer.', 'sac_code');
        }

        if ($defaultTaxId !== null && $defaultTaxId > 0) {
            $tax = $this->db->fetchOne(
                "SELECT id FROM taxes WHERE id = ? AND company_id = ? AND status = 'active' LIMIT 1",
                [$defaultTaxId, $this->context->companyId]
            );
            if (!$tax) {
                $this->addError('Selected tax rate is not valid.', 'default_tax_id');
                $defaultTaxId = null;
            }
        } else {
            $defaultTaxId = null;
        }

        return [
            'name'                => $name,
            'applies_to'          => $appliesTo,
            'refund_policy'       => $refundPolicy,
            'sac_code'            => $sacCode,
            'default_tax_id'      => $defaultTaxId,
            'affects_landed_cost' => $affectsLandedCost,
            'is_active'           => $isActive,
            'sort_order'          => $sortOrder,
        ];
    }

    private function getOwnedOrFail(int $id): Models_ExtraCharge {
        $ec = new Models_ExtraCharge($id);
        if ($ec->isEmpty) {
            throw new Service_Exception('Extra charge not found.', 404);
        }
        if ((int) $ec->company_id !== (int) $this->context->companyId) {
            throw new Service_Exception('Access denied.', 403);
        }
        return $ec;
    }

    private function getById(int $id): ?object {
        return $this->db->fetchOne(
            "SELECT ec.*, t.name AS default_tax_name, t.rate AS default_tax_rate
             FROM extra_charges ec
             LEFT JOIN taxes t ON t.id = ec.default_tax_id
             WHERE ec.id = ? LIMIT 1",
            [$id]
        );
    }
}
?>
