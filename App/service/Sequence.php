<?php
class Service_Sequence {

    
    public static function nextPreview($companyId, $sequanceKey) {
        return self::next($companyId, $sequanceKey, false);
    }


    public static function nextCommit($companyId, $sequanceKey) {

        global $db;

        /**
         * IMPORTANT:
         * This method may start and commit its own transaction
         * if called without an active transaction.
         *
         * Business services (PO, SO, Inventory, etc.) MUST
         * start the transaction before calling this method
         * to avoid burning sequence numbers.
         */

        if( $db->transactionLevel() <= 0 ) {
            $db->startTransaction();
        }

        try {

            $sequanceNumber = self::next($companyId, $sequanceKey, true);
            
            if( $db->transactionLevel() <= 0 ) {
                $db->commit();
            }

            return $sequanceNumber;

        } catch (Exception $e) {

            if( $db->transactionLevel() <= 0 ) {
                $db->rollBack();
            }

            throw $e;
        }
    }

    private static function next($companyId, $sequanceKey, $commit) {

        global $db;
        
        try {

            $pattern = self::lockAndFetchPattern($companyId, $sequanceKey, $commit);

            if( !$pattern ) {
                throw new Exception("Sequence pattern configuration is missing");
            }

            $lastSequanceNumber = $pattern->last_number;
            $pattern->sequence_key = $sequanceKey;

            [$number, $counter] = self::getNextAvailableNumber($lastSequanceNumber, $pattern);


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


    private static function lockAndFetchPattern($companyId, $sequanceKey, $commit) {

        global $db;

        //$sequanceKey = "test";

        // Try product-specific first
        $sql = "SELECT * FROM sequences 
                WHERE company_id = ? AND sequence_key = ? AND is_active = ?";
        
        // only lock for commit
        if( $commit === true ) {
            $sql .=" FOR UPDATE";
        }
        
        $pattern = $db->fetchOne($sql, [$companyId, $sequanceKey, 1]);

        if( $pattern ) {
            return $pattern;
        }

        // create default pattern and return it
        $sequence = new Models_Sequence();
        $sequence->company_id = $companyId;
        $sequence->sequence_key = $sequanceKey;
        if( $sequence->sequence_key === "purchase_orders" ) {
            $sequence->pattern = "PO";
        }
        else if( $sequence->sequence_key === "purchase_order_grns" ) {
            $sequence->pattern = "PR";
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



    private static function getNextAvailableNumber($lastNumber, $pattern) {

        $counter = $lastNumber;
        while (true) {

            $counter++;

            $number = self::applyPattern($pattern, $counter);
            if (!self::sequenceExists($pattern->company_id, $number, $pattern->sequence_key)) {
                return [$number, $counter];
            }
        }
    }




    /**
     * Apply pattern formatting and append padded counter
     */
    private static function applyPattern($pattern, $counter)
    {
        $formatted = (String) $pattern->pattern;
        $formatted = str_replace("{YY}", date("y"), $formatted);
        $formatted = str_replace("{YYYY}", date("Y"), $formatted);
        $formatted = str_replace("{MM}", date("m"), $formatted);

        $padding = $pattern->padding ?: 6;


        return $formatted . str_pad($counter, $padding, "0", STR_PAD_LEFT);
    }


    private static function sequenceExists($companyId, $number, $sequanceKey) {

        global $db;
        if( $sequanceKey === "vendors" ) {

            $sql = "SELECT id FROM vendors WHERE company_id = ? AND vendor_code = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }
        else if( $sequanceKey === "purchase_orders" ) {

            $sql = "SELECT id FROM purchase_orders WHERE company_id = ? AND po_number = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }

        return false;
    }
    
}
?>