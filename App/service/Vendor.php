<?php
class Service_Vendor extends Service_Base {


    private function getVendorOrFail(int $vendorId) {

        // validate
        $vendor = new Models_Vendor($vendorId);
        if( $vendor->isEmpty ) {
            throw new Service_Exception("The requested vendor was not found", 404);
        }

        if( $vendor->company_id != $this->context->companyId ) {
            throw new Service_Exception("You do not have permission to access this vendor", 403);
        }

        return $vendor;
    }

    private function isUniqueGstin(string $gstin, int $vendorId = 0): bool {

        $companyId = $this->context->companyId;

        $sql = "SELECT count(id) FROM vendors WHERE company_id = ? AND lower(gstin) = ?";
        $bindings  = [$companyId, strtolower(trim($gstin))];
        if ($vendorId > 0) {
            $sql .= " AND id != ?";
            $bindings[] = $vendorId;
        }

        $count = $this->db->fetchVar($sql, $bindings);        
        if ( $count > 0) {
            return false;
        }

        return true;
    }


    public function checkDuplicate(string $field, string $value, int $vendorId = 0): array {

        $allowed = ["email", "phone"];
        if (!in_array($field, $allowed, true) || $value === "") {
            return ["exists" => false, "vendor" => null];
        }

        $value     = strtolower(trim($value));
        $companyId = $this->context->companyId;
        $bindings  = [$companyId, $value];

        $sql = "SELECT id, display_name FROM vendors WHERE company_id = ? AND lower({$field}) = ?";

        if ($vendorId > 0) {
            $sql      .= " AND id != ?";
            $bindings[] = $vendorId;
        }
        $sql .= " LIMIT 1";

        $result = $this->db->fetchOne($sql, $bindings);

        if ($result) {
            return ["exists" => true, "vendor" => ["id" => $result->id, "display_name" => $result->display_name]];
        }

        return ["exists" => false, "vendor" => null];
    }


    private function validatePayload(array $payload, int $vendorId=0) {

        $paymentTermId = $payload["payment_term_id"] ?? "";
        $gstin = trim($payload['gstin'] ?? '');

        if(empty($payload['legal_name'])) {
            $this->addError(validationErrMsg("required", "Company name"), "company_name");
        }

        if(empty($payload['email'])) {
            $this->addError(validationErrMsg("required", "Email"), "email");
        } else if( !isValidEmail($payload['email']) ) {
            $this->addError(validationErrMsg("invalid", "Email"), "email");
        }

        if(empty($payload['phone'])) {
            $this->addError(validationErrMsg("invalid", "Phone"), "phone");
        }

        if( $paymentTermId ) {
            
            $paymentTerm = new Models_PaymentTerm($paymentTermId);
            if( !(!$paymentTerm->isEmpty && $paymentTerm->company_id == $this->context->companyId) ) {
                $this->addError(validationErrMsg("invalid", "Payment terms"), "payment_term_id");
            }
        }

        if( !empty($gstin) ) {
            if( !$this->isUniqueGstin($gstin, $vendorId) ) {
                $this->addError(validationErrMsg("duplicate", "GSTIN"), "gstin");
            }
        }
    }


    private function normalizePayload(&$payload) {

        $type = trim($payload['vendor_type'] ?? '') ?: 'company';
        $companyName = trim($payload['company_name'] ?? '');
        $firstName = trim($payload['first_name'] ?? '');
        $lastName = trim($payload['last_name'] ?? '');
        $status = trim($payload['status'] ?? '') ?: null;
        $paymentTermId = trim($payload['payment_term_id'] ?? '') ?: null;
        $currencyCode = trim($payload['currency_code'] ?? '') ?: 'INR';

        if( $type === "personal" ) {
            $companyName = trim($firstName . ' ' . $lastName);
        }
        $legalName = $companyName; // currently legal name will be same as company name

        $payload["display_name"] = $companyName;
        $payload["legal_name"] = $legalName;
        $payload["status"] = $status;
        $payload["payment_term_id"] = $paymentTermId;
        $payload["currency_code"] = $currencyCode;
    }


    // currently it supports one Billing & one Shipping address
    private function saveAddresses($vendorId, $addresses) {

        $billingAddress = $addresses["billing"] ?? [];
        $billingAttention = $billingAddress["attention"] ?? "";
        $billingCountry = $billingAddress["country"] ?? "";
        $billingLine1 = $billingAddress["address_line1"] ?? "";
        $billingLine2 = $billingAddress["address_line2"] ?? "";
        $billingCity = $billingAddress["city"] ?? "";
        $billingState = $billingAddress["state"] ?? "";
        $billingPostalcode = $billingAddress["postal_code"] ?? "";
        $billingPhone = $billingAddress["phone"] ?? "";
        

        if( $billingAttention || $billingCountry || $billingLine1 || $billingLine2 || $billingCity || $billingState || $billingPostalcode || $billingPhone ) {
                    
            $vendorBillingAddress = new Models_VendorAddress();
            $vendorBillingAddress->fetchByProperty(["vendor_id", "address_type"], [$vendorId, "billing"]);

            $vendorBillingAddress->vendor_id = $vendorId;
            $vendorBillingAddress->address_type = "billing";
            $vendorBillingAddress->attention = $billingAttention;
            $vendorBillingAddress->phone = $billingPhone;
            $vendorBillingAddress->address_line1 = $billingLine1;
            $vendorBillingAddress->address_line2 = $billingLine2;
            $vendorBillingAddress->city = $billingCity;
            $vendorBillingAddress->state = $billingState;
            $vendorBillingAddress->postal_code = $billingPostalcode;
            $vendorBillingAddress->country = $billingCountry;

            if( $vendorBillingAddress->isEmpty ) {
                
                $vendorBillingAddress->is_default = 1;                
                $vendorBillingAddress->company_id = $this->context->companyId;
                $vendorBillingAddress->created_by = $this->context->userId;

                if( !$vendorBillingAddress->create() ) {
                    throw new Service_Exception("Failed to save billing address");
                }

            } else {

                if( !$vendorBillingAddress->update() ) {                    
                    throw new Service_Exception("Failed to save billing address");
                }
            }                    
        }


        $shippingAddress = $addresses["shipping"];
        $shippingAttention = $shippingAddress["attention"] ?? "";
        $shippingCountry = $shippingAddress["country"] ?? "";
        $shippingLine1 = $shippingAddress["address_line1"] ?? "";
        $shippingLine2 = $shippingAddress["address_line2"] ?? "";
        $shippingCity = $shippingAddress["city"] ?? "";
        $shippingState = $shippingAddress["state"] ?? "";
        $shippingPostalcode = $shippingAddress["postal_code"] ?? "";
        $shippingPhone = $shippingAddress["phone"] ?? "";


        if( $shippingAttention || $shippingCountry || $shippingLine1 || $shippingLine2 || $shippingCity || $shippingCity || $shippingState || $shippingPostalcode || $shippingPhone ) {
                    
            $vendorShippingAddress = new Models_VendorAddress();
            $vendorShippingAddress->fetchByProperty(["vendor_id", "address_type"], [$vendorId, "shipping"]);

            $vendorShippingAddress->vendor_id = $vendorId;
            $vendorShippingAddress->address_type = "shipping";
            $vendorShippingAddress->attention = $shippingAttention;
            $vendorShippingAddress->phone = $shippingPhone;
            $vendorShippingAddress->address_line1 = $shippingLine1;
            $vendorShippingAddress->address_line2 = $shippingLine2;
            $vendorShippingAddress->city = $shippingCity;
            $vendorShippingAddress->state = $shippingState;
            $vendorShippingAddress->postal_code = $shippingPostalcode;
            $vendorShippingAddress->country = $shippingCountry;

            if( $vendorShippingAddress->isEmpty ) {
                
                $vendorShippingAddress->is_default = 1;
                $vendorShippingAddress->company_id = $this->context->companyId;
                $vendorShippingAddress->created_by = $this->context->userId;

                if( !$vendorShippingAddress->create() ) {
                    throw new Service_Exception("Failed to save shipping address");
                }

            } else {

                if( !$vendorShippingAddress->update() ) {
                    throw new Service_Exception("Failed to save shipping address");
                }
            }                    
        }
    }

    
    public function create(array $payload) {

        if (!$this->context->canDo('vendors', 'write')) {
            throw new Service_Exception('You do not have permission to create vendors', 403);
        }

        // normalize payload
        $this->normalizePayload($payload);

        // Validate incoming data
        $this->validatePayload($payload);

        if ($this->hasErrors()) {
            return [
                "success" => false,
                "errors"  => $this->getErrors()
            ];
        }
        
        // Begin transaction
        $this->db->startTransaction();

        try {            

            $companyId = $this->context->companyId;
            $userId = $this->context->userId;

            $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));

            $vendor = new Models_Vendor();
            $vendor->fillFromArray($payload);
            $vendor->vendor_code = $seqService->nextCommit("vendors"); 
            $vendor->company_id = $companyId;
            $vendor->created_by = $userId;
            $vendorId = $vendor->create();
            
            if( !$vendorId ) {
                throw new Service_Exception("Failed to create vendor");
            }

            
            // save billing & shipping addresses
            $addresses = ["billing" => $payload["billing_address"] ?? [], "shipping" => $payload["shipping_address"] ?? []];
            $this->saveAddresses($vendorId, $addresses);

            

            // Commit
            $this->db->commit();


            return [
                "success" => true,
                "data" => [
                    "id"           => $vendorId,
                    "display_name" => $payload["display_name"],
                    "currency_code" => $payload["currency_code"],
                ],
            ];

        } catch (Exception $e) {
            
            $this->db->rollBack();
            throw $e;
        }
    }



    public function update(int $vendorId, array $payload) {

        if (!$this->context->canDo('vendors', 'write')) {
            throw new Service_Exception('You do not have permission to update vendors', 403);
        }

        $vendor = $this->getVendorOrFail($vendorId);

        // normalize payload
        $this->normalizePayload($payload);


        // Validate payload
        $this->validatePayload($payload, $vendorId);

        
        if ($this->hasErrors()) {
            return [
                "success" => false,
                "errors"  => $this->getErrors()
            ];
        }

        // Begin transaction
        $this->db->startTransaction();

        try {

            $vendor->fillFromArray($payload, ["company_id", "created_by", "created_at"]);
            if( !$vendor->update() ) {
                throw new Service_Exception("Failed to update vendor");
            }


            // save billing & shipping addresses
            $addresses = ["billing" => $payload["billing_address"] ?? [], "shipping" => $payload["shipping_address"] ?? []];
            $this->saveAddresses($vendorId, $addresses);

            

            // Commit
            $this->db->commit();


            return [
                "success" => true,
                "data" => [],
            ];


        } catch (Exception $e) {

            $this->db->rollBack();
            throw $e;
        }
    }



}
?>