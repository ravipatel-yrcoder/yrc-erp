<?php
class Service_Inv_Sequence extends Service_Base {
    
    /**
     * Generate next LOT/SERIAL numbers with full locking
     */
    public function generate($productId, $sequenceType, $count = 1)
    {
        $this->db->startTransaction();

        try {

            $pattern = $this->lockAndFetchPattern($productId, $sequenceType);
            
            if( !$pattern ) {
                throw new Exception("Sequence pattern configuration is missing");
            }
            
            $lastSequenceNumber = $pattern->last_number;
            $pattern->sequence_type = $sequenceType;

            $numbers = [];
            for ($i = 0; $i < $count; $i++) {

                [$number, $lastSequenceNumber] = $this->getNextAvailableNumber($lastSequenceNumber, $pattern);

                $numbers[] = $number;
            }


            // Save updated last_number
            if( $lastSequenceNumber )
            {
                // save logic to update last_number in `inv_sequence_patterns` table
                // for first version will not implement this but will implement this when start seeing real issue with data
            }
            

            $this->db->commit();

            return $numbers;

        } catch (Exception $e) {

            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Lock pattern row using SELECT ... FOR UPDATE
     */
    private function lockAndFetchPattern($productId, $sequenceType)
    {
        $companyId = $this->context->companyId;

        // Try product-specific first
        $sql = "SELECT * FROM inv_sequence_patterns 
                WHERE company_id = ? AND product_id = ? AND sequence_type = ?
                FOR UPDATE";
        
        $pattern = $this->db->fetchOne($sql, [$companyId, $productId, $sequenceType]);
        if( $pattern ) {
            return $pattern;
        }

        // Fallback → Global default
        $sql = "SELECT * FROM inv_sequence_patterns 
                WHERE company_id = ? AND product_id IS NULL AND (sequence_type = ? OR sequence_type = ?)
                FOR UPDATE";
        $pattern = $this->db->fetchOne($sql, [$companyId, $sequenceType, "both"]);

        return $pattern;
    }


    private function getNextAvailableNumber($lastNumber, $pattern) {

        $counter = $lastNumber;
        while (true) {

            $counter++;

            $number = $this->applyPattern($pattern, $counter);
            if (!$this->numberExists($pattern->company_id, $number, $pattern->sequence_type, $pattern->product_id)) {
                return [$number, $counter];
            }
        }
    }


    /**
     * Check if generated number already exists in DB
     */
    private function numberExists($companyId, $number, $sequenceType, $productId=null)
    {
        $queryBinding = [$companyId, $number];
        
        $prodWhere = "";
        if( $productId ) {
            $prodWhere = "AND product_id = ? ";
            $queryBinding[] = $productId;
        }

        if ($sequenceType === "serial") {
            $sql = "SELECT id FROM inv_serials WHERE company_id = ? AND serial_number = ? {$prodWhere}LIMIT 1";
        } else {
            $sql = "SELECT id FROM inv_lots WHERE company_id = ? AND lot_number = ? {$prodWhere}LIMIT 1";
        }
        
        return (bool) $this->db->fetchCol($sql, $queryBinding);
    }

    /**
     * Apply pattern formatting and append padded counter
     */
    private function applyPattern($pattern, $counter)
    {
        $formatted = $pattern->pattern;
        $formatted = str_replace("{YY}", date("y"), $formatted);
        $formatted = str_replace("{YYYY}", date("Y"), $formatted);
        $formatted = str_replace("{MM}", date("m"), $formatted);

        $padding = $pattern->padding ?: 6;


        return $formatted . str_pad($counter, $padding, "0", STR_PAD_LEFT);
    }
}