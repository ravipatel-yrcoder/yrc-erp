<?php
class Service_Sequence extends Service_Base {

    
    public function nextPreview($sequenceKey) {
        return $this->next($this->context->companyId, $sequenceKey, false);
    }


    public function nextCommit($sequenceKey) {

        //global $db;

        $db = $this->db;

        $db->startTransaction();

        try {

            $sequenceNumber = $this->next($this->context->companyId, $sequenceKey, true);

            $db->commit();

            return $sequenceNumber;

        } catch (Exception $e) {

            $db->rollBack();
            throw $e;
        }
    }

    private function next($companyId, $sequenceKey, $commit) {

        $db = $this->db;
        
        try {

            $pattern = $this->lockAndFetchPattern($companyId, $sequenceKey, $commit);

            if( !$pattern ) {
                throw new Exception("Sequence pattern configuration is missing");
            }

            $lastSequenceNumber = $pattern->last_number;
            $pattern->sequence_key = $sequenceKey;

            [$number, $counter] = $this->getNextAvailableNumber($lastSequenceNumber, $pattern);


            // Save updated last_number
            if( $commit === true )
            {
                if( $number ) {
                    $db->update("sequences", ["last_number" => $counter], "id=$pattern->id");
                }
            }            


            return $number;

        } catch (Exception $e) {
            throw $e;
        }

    }


    private function lockAndFetchPattern($companyId, $sequenceKey, $commit) {

        $db = $this->db;

        //$sequenceKey = "test";

        // Try product-specific first
        $sql = "SELECT * FROM sequences WHERE company_id = ? AND lower(sequence_key) = ? AND is_active = ?";
        
        // only lock for commit
        if( $commit === true ) {
            $sql .=" FOR UPDATE";
        }
        
        $pattern = $db->fetchOne($sql, [$companyId, strtolower($sequenceKey), 1]);

        if( $pattern ) {
            return $pattern;
        }

        // create default pattern and return it
        $sequence = new Models_Sequence();
        $sequence->company_id = $companyId;
        $sequence->sequence_key = $sequenceKey;
        if( $sequence->sequence_key === "purchase_orders" ) {
            $sequence->pattern = "PO";
        }
        else if( $sequence->sequence_key === "purchase_order_grns" ) {
            $sequence->pattern = "PR";
        }
        else if( $sequence->sequence_key === "sales_orders" ) {
            $sequence->pattern = "SO";
        }
        else if( $sequence->sequence_key === "sales_deliveries" ) {
            $sequence->pattern = "DN";
        }
        else if( $sequence->sequence_key === "vendors" ) {
            $sequence->pattern = "VN";
        }
        else if( $sequence->sequence_key === "customers" ) {
            $sequence->pattern = "CN";
        }
        else if( $sequence->sequence_key === "crm_leads" ) {
            $sequence->pattern = "LD";
        }
        else if( $sequence->sequence_key === "manufacturing_orders" ) {
            $sequence->pattern = "MO";
        }

        $id = $sequence->create();
        if( $id ) {

            $pattern = new stdClass();

            $pattern->id = $id;
            $pattern->company_id = $sequence->company_id;
            $pattern->sequence_key = $sequence->sequence_key;
            $pattern->pattern = $sequence->pattern;
            $pattern->padding = $sequence->padding;
            $pattern->last_number = $sequence->last_number;
            $pattern->reset_period = $sequence->reset_period;
            $pattern->is_active = $sequence->is_active;
            $pattern->created_at = $sequence->created_at;
            $pattern->updated_at = $sequence->updated_at;

            return $pattern;
        }

        return null;
    }



    private function getNextAvailableNumber($lastNumber, $pattern) {

        $counter = $lastNumber;
        while (true) {

            $counter++;

            $number = $this->applyPattern($pattern, $counter);
            if (!$this->sequenceExists($pattern->company_id, $number, $pattern->sequence_key)) {
                return [$number, $counter];
            }
        }
    }




    /**
     * Apply pattern formatting and append padded counter
     */
    private function applyPattern($pattern, $counter)
    {
        $formatted = (String) $pattern->pattern;
        $formatted = str_replace("{YY}", date("y"), $formatted);
        $formatted = str_replace("{YYYY}", date("Y"), $formatted);
        $formatted = str_replace("{MM}", date("m"), $formatted);

        $padding = $pattern->padding ?: 6;


        return $formatted . str_pad($counter, $padding, "0", STR_PAD_LEFT);
    }


    private function sequenceExists($companyId, $number, $sequenceKey) {

        $db = $this->db;
        
        if( $sequenceKey === "vendors" ) {

            $sql = "SELECT id FROM vendors WHERE company_id = ? AND vendor_code = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }
        else if( $sequenceKey === "purchase_orders" ) {

            $sql = "SELECT id FROM purchase_orders WHERE company_id = ? AND po_number = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }
        else if( $sequenceKey === "sales_orders" ) {

            $sql = "SELECT id FROM sales_orders WHERE company_id = ? AND so_number = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }
        else if( $sequenceKey === "sales_deliveries" ) {

            $sql = "SELECT id FROM sales_deliveries WHERE company_id = ? AND dn_number = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }
        else if( $sequenceKey === "customers" ) {

            $sql = "SELECT id FROM customers WHERE company_id = ? AND customer_code = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }
        else if( $sequenceKey === "crm_leads" ) {

            $sql = "SELECT id FROM crm_leads WHERE company_id = ? AND lead_code = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }
        else if( $sequenceKey === "manufacturing_orders" ) {

            $sql = "SELECT id FROM manufacturing_orders WHERE company_id = ? AND mo_number = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }

        return false;
    }
    
}
?>