<?php
class Service_Customer extends Service_Base {


    private function getCustomerOrFail(int $customerId): Models_Customer {

        $customer = new Models_Customer($customerId);
        if ($customer->isEmpty) {
            throw new Service_Exception("The requested customer was not found", 404);
        }

        if ($customer->company_id != $this->context->companyId) {
            throw new Service_Exception("You do not have permission to access this customer", 403);
        }

        return $customer;
    }


    private function validatePayload(array $payload) {

        $paymentTermId = $payload["payment_term_id"] ?? "";
        $customerGroupId = $payload["customer_group_id"] ?? "";
        $type = $payload['customer_type'] ?? 'company';

        if (empty($payload['first_name'])) {
            $this->addError(validationErrMsg("required", "First name"), "first_name");
        }

        if ($type !== 'individual' && empty($payload['company_name'])) {
            $this->addError(validationErrMsg("required", "Company name"), "company_name");
        }

        if (!empty($payload['email']) && !isValidEmail($payload['email'])) {
            $this->addError(validationErrMsg("invalid", "Email"), "email");
        }

        if ($paymentTermId) {
            $paymentTerm = new Models_PaymentTerm($paymentTermId);
            if (!(!$paymentTerm->isEmpty && $paymentTerm->company_id == $this->context->companyId)) {
                $this->addError(validationErrMsg("invalid", "Payment terms"), "payment_term_id");
            }
        }

        if ($customerGroupId) {
            $group = new Models_CustomerGroup($customerGroupId);
            if (!(!$group->isEmpty && $group->company_id == $this->context->companyId)) {
                $this->addError(validationErrMsg("invalid", "Customer group"), "customer_group_id");
            }
        }
    }


    private function normalizePayload(array &$payload) {

        $type = trim($payload['customer_type'] ?? '') ?: 'company';
        $salutation = trim($payload['salutation']    ?? '');
        $firstName = trim($payload['first_name']    ?? '');
        $lastName = trim($payload['last_name']     ?? '');
        $companyName = trim($payload['company_name']  ?? '');
        $displayName = trim($payload['display_name']  ?? '');

        if ($type === 'individual') {
            $displayParts = array_filter([$salutation, $firstName, $lastName]);
            $autoDisplay  = implode(' ', $displayParts);
        } else {
            $autoDisplay = $companyName;
        }

        $payload['customer_type'] = $type;
        $payload['salutation'] = $salutation ?: null;
        $payload['first_name'] = $firstName  ?: null;
        $payload['last_name'] = $lastName   ?: null;
        $payload['company_name'] = $companyName;
        $payload['display_name'] = $displayName ?: $autoDisplay;
        $payload['payment_term_id'] = trim($payload['payment_term_id'] ?? '') ?: null;
        $payload['customer_group_id']= trim($payload['customer_group_id'] ?? '') ?: null;
        $payload['price_list_id'] = trim($payload['price_list_id'] ?? '') ?: null;
        $payload['currency_code'] = trim($payload['currency_code'] ?? '') ?: 'INR';
        $payload['credit_limit'] = trim($payload['credit_limit'] ?? '') ?: null;
        $payload['status'] = trim($payload['status'] ?? '') ?: null;
    }


    private function saveAddresses(int $customerId, array $addresses) {

        foreach (['billing', 'shipping'] as $type) {

            $addr = $addresses[$type] ?? [];
            $fields = ['attention', 'country', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'phone'];
            $hasData = false;

            foreach ($fields as $field) {
                if (!empty($addr[$field])) {
                    $hasData = true;
                    break;
                }
            }

            if (!$hasData) continue;

            $customerAddr = new Models_CustomerAddress();
            $customerAddr->fetchByProperty(["customer_id", "address_type"], [$customerId, $type]);

            $customerAddr->customer_id = $customerId;
            $customerAddr->address_type = $type;
            $customerAddr->attention = $addr['attention'] ?? null;
            $customerAddr->phone = $addr['phone'] ?? null;
            $customerAddr->address_line1 = $addr['address_line1'] ?? null;
            $customerAddr->address_line2 = $addr['address_line2'] ?? null;
            $customerAddr->city = $addr['city'] ?? null;
            $customerAddr->state = $addr['state'] ?? null;
            $customerAddr->postal_code = $addr['postal_code'] ?? null;
            $customerAddr->country = $addr['country'] ?? null;

            if ($customerAddr->isEmpty) {
                $customerAddr->is_default = 1;
                $customerAddr->company_id = $this->context->companyId;
                $customerAddr->created_by = $this->context->userId;
                if (!$customerAddr->create()) {
                    throw new Service_Exception("Failed to save {$type} address");
                }
            } else {
                if (!$customerAddr->update()) {
                    throw new Service_Exception("Failed to save {$type} address");
                }
            }
        }
    }


    public function create(array $payload): array {

        $this->normalizePayload($payload);
        $this->validatePayload($payload);

        if ($this->hasErrors()) {
            return ["success" => false, "errors" => $this->getErrors()];
        }

        $this->db->startTransaction();

        try {

            $companyId = $this->context->companyId;
            $userId = $this->context->userId;

            $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));

            $customer = new Models_Customer();
            $customer->fillFromArray($payload);
            $customer->customer_code = $seqService->nextCommit("customers");
            $customer->company_id = $companyId;
            $customer->created_by = $userId;

            $customerId = $customer->create();
            if (!$customerId) {                
                throw new Service_Exception("Failed to create customer");
            }

            $addresses = [
                'billing' => $payload['billing_address'] ?? [],
                'shipping' => $payload['shipping_address'] ?? [],
            ];
            $this->saveAddresses($customerId, $addresses);

            $this->db->commit();

            return ["success" => true, "data" => ["id" => $customerId]];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function update(int $customerId, array $payload): array {

        $customer = $this->getCustomerOrFail($customerId);

        $this->normalizePayload($payload);
        $this->validatePayload($payload);

        if ($this->hasErrors()) {
            return ["success" => false, "errors" => $this->getErrors()];
        }

        $this->db->startTransaction();

        try {

            $customer->fillFromArray($payload, ["company_id", "created_by", "created_at", "customer_code"]);
            if (!$customer->update()) {
                throw new Service_Exception("Failed to update customer");
            }

            $addresses = [
                'billing' => $payload['billing_address'] ?? [],
                'shipping' => $payload['shipping_address'] ?? [],
            ];
            $this->saveAddresses($customerId, $addresses);

            $this->db->commit();

            return ["success" => true, "data" => []];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function saveAddress(int $customerId, array $payload): array {

        $this->getCustomerOrFail($customerId);

        $addressType  = trim($payload['address_type']  ?? '');
        $addressLine1 = trim($payload['address_line1'] ?? '');

        if (!in_array($addressType, ['billing', 'shipping'])) {
            $this->addError(validationErrMsg("required", "Address type"), "address_type");
        }
        if (empty($addressLine1)) {
            $this->addError(validationErrMsg("required", "Address line 1"), "address_line1");
        }

        if ($this->hasErrors()) {
            return ["success" => false, "errors" => $this->getErrors()];
        }

        $addr = new Models_CustomerAddress();
        $addr->company_id    = $this->context->companyId;
        $addr->customer_id   = $customerId;
        $addr->address_type  = $addressType;
        $addr->label         = trim($payload['label']         ?? '') ?: null;
        $addr->attention     = trim($payload['attention']     ?? '') ?: null;
        $addr->phone         = trim($payload['phone']         ?? '') ?: null;
        $addr->address_line1 = $addressLine1;
        $addr->address_line2 = trim($payload['address_line2'] ?? '') ?: null;
        $addr->city          = trim($payload['city']          ?? '') ?: null;
        $addr->state         = trim($payload['state']         ?? '') ?: null;
        $addr->postal_code   = trim($payload['postal_code']   ?? '') ?: null;
        $addr->country       = trim($payload['country']       ?? '') ?: 'IN';
        $addr->is_default    = 0;
        $addr->created_by    = $this->context->userId;

        if (!$addr->create()) {
            throw new Service_Exception("Failed to save address");
        }

        $parts        = array_filter([$addr->address_line1, $addr->address_line2, $addr->city, $addr->state, $addr->country]);
        $displayLabel = implode(', ', $parts);

        return ["success" => true, "data" => ["id" => $addr->id, "label" => $displayLabel, "address_type" => $addressType]];
    }


    public function checkDuplicate(string $field, string $value, int $customerId = 0): array {

        $allowed = ["email", "phone"];
        if (!in_array($field, $allowed, true) || $value === "") {
            return ["exists" => false, "customer" => null];
        }

        $value = strtolower(trim($value));

        $companyId = $this->context->companyId;
        $bindings  = [$companyId, $value];

        $sql = "SELECT id, display_name FROM customers WHERE company_id = ? AND lower({$field}) = ?";

        if ($customerId > 0) {
            $sql .= " AND id != ?";
            $bindings[] = $customerId;
        }
        $sql .= " LIMIT 1";

        $result = $this->db->fetchOne($sql, $bindings);

        if ($result) {
            return ["exists" => true, "customer" => ["id" => $result->id, "display_name" => $result->display_name]];
        }

        return ["exists" => false, "customer" => null];
    }


    public function getFormContext(int $customerId = 0): array {

        $companyId = $this->context->companyId;
        
        $customerDetails = [];
        if ($customerId > 0) {
            $customer = $this->getCustomerOrFail($customerId);
            $customerDetails = array_merge(['id' => $customer->id], $customer->toArray());
            $customerDetails['billing_address']  = $customer->getBillingAddress();
            $customerDetails['shipping_address'] = $customer->getShippingAddress();
        }

        $paymentTerm = new Models_PaymentTerm();
        $paymentTerms = $paymentTerm->getAll([], ["company_id" => $companyId, "status" => ["active"]]);

        $group = new Models_CustomerGroup();
        $customerGroups = $group->getAll([], ["company_id" => $companyId, "status" => ["active"]]);

        return [
            'customerDetails' => $customerDetails,
            'paymentTerms'    => $paymentTerms,
            'customerGroups'  => $customerGroups,
        ];
    }
}
?>
