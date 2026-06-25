<?php
class Service_Inv_Sequence extends Service_Base {
    
    /**
     * Generate next LOT/SERIAL numbers without committing last_number (safe to call without saving).
     */
    public function generatePreview(int $productId, string $sequenceType, int $count = 1): array
    {
        return $this->generate($productId, $sequenceType, $count, false);
    }

    /**
     * Generate next LOT/SERIAL numbers and commit last_number immediately.
     * Use when the numbers will be stored in inventory (e.g. PO receive staging).
     */
    public function generateCommit(int $productId, string $sequenceType, int $count = 1): array
    {
        $this->db->startTransaction();
        try {
            $numbers = $this->generate($productId, $sequenceType, $count, true);
            $this->db->commit();
            return $numbers;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * @param bool $commit  true → lock row + persist last_number; false → plain SELECT only
     */
    private function generate(int $productId, string $sequenceType, int $count, bool $commit): array
    {
        $pattern = $this->lockAndFetchPattern($productId, $sequenceType, $commit);

        if (!$pattern) {
            throw new Exception("Sequence pattern configuration is missing");
        }

        $lastSequenceNumber = $pattern->last_number;
        $pattern->sequence_type = $sequenceType;

        $numbers = [];
        for ($i = 0; $i < $count; $i++) {
            [$number, $lastSequenceNumber] = $this->getNextAvailableNumber($lastSequenceNumber, $pattern);
            $numbers[] = $number;
        }

        if ($commit && $lastSequenceNumber) {
            $patternId = $pattern->id;
            $this->db->update('inv_sequence_patterns', ['last_number' => $lastSequenceNumber], "id = $patternId");
        }

        return $numbers;
    }

    /**
     * Fetch the sequence pattern row.
     * When $commit = true, appends FOR UPDATE to lock the row within an open transaction.
     * Falls back to the global company default, auto-creating it if none exists.
     */
    private function lockAndFetchPattern(int $productId, string $sequenceType, bool $commit)
    {
        $companyId = $this->context->companyId;
        $lock = $commit ? ' FOR UPDATE' : '';

        // Try product-specific first
        $pattern = $this->db->fetchOne(
            "SELECT * FROM inv_sequence_patterns
             WHERE company_id = ? AND product_id = ? AND sequence_type = ?{$lock}",
            [$companyId, $productId, $sequenceType]
        );
        if ($pattern) {
            return $pattern;
        }

        // Fallback → global default for this company
        $pattern = $this->db->fetchOne(
            "SELECT * FROM inv_sequence_patterns
             WHERE company_id = ? AND product_id IS NULL AND (sequence_type = ? OR sequence_type = 'both'){$lock}",
            [$companyId, $sequenceType]
        );
        if ($pattern) {
            return $pattern;
        }

        // No pattern exists — auto-create a global default for this company.
        // Two concurrent requests could both reach this point; wrap in try/catch
        // so a duplicate-key failure on the second INSERT is handled gracefully.
        try {
            $now = date('Y-m-d H:i:s');
            $this->db->insert('inv_sequence_patterns', [
                'company_id'    => $companyId,
                'product_id'    => null,
                'pattern'       => 'SN',
                'last_number'   => 0,
                'reset_period'  => 'none',
                'sequence_type' => 'both',
                'padding'       => 6,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        } catch (Exception $e) {
            // A concurrent request already inserted the default — fall through to re-select.
        }

        // Re-select (with lock if commit) whether we just inserted or a concurrent request did.
        return $this->db->fetchOne(
            "SELECT * FROM inv_sequence_patterns
             WHERE company_id = ? AND product_id IS NULL AND (sequence_type = ? OR sequence_type = 'both'){$lock}",
            [$companyId, $sequenceType]
        );
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


    /**
     * Resolve the pattern prefix by substituting date placeholders.
     * Extracted so both applyPattern() and updateLastNumber() use identical logic.
     */
    private function resolvePrefix(string $pattern): string
    {
        $p = str_replace('{YY}',   date('y'), $pattern);
        $p = str_replace('{YYYY}', date('Y'), $p);
        $p = str_replace('{MM}',   date('m'), $p);
        return $p;
    }


    /**
     * Advance last_number in inv_sequence_patterns to reflect serial numbers
     * that have just been committed (to staging or to inv_serials).
     *
     * Scans each submitted serial, strips the resolved pattern prefix, and
     * parses the numeric suffix. If a parsed counter exceeds the stored
     * last_number the counter is updated. Serials that do not match the
     * pattern prefix (manually entered vendor serials, etc.) are skipped.
     */
    public function updateLastNumber(int $productId, array $serialNumbers): void
    {
        if (empty($serialNumbers)) return;

        $pattern = $this->lockAndFetchPattern($productId, 'serial', true);
        if (!$pattern) return;

        $resolvedPrefix = $this->resolvePrefix($pattern->pattern);
        $prefixLen      = strlen($resolvedPrefix);
        $maxCounter     = (int) $pattern->last_number;

        foreach ($serialNumbers as $sn) {
            $sn = trim((string) $sn);
            if ($sn === '') continue;

            if ($prefixLen > 0) {
                if (strpos($sn, $resolvedPrefix) !== 0) continue;
                $numericPart = substr($sn, $prefixLen);
            } else {
                $numericPart = $sn;
            }

            if (ctype_digit($numericPart)) {
                $counter = (int) $numericPart;
                if ($counter > $maxCounter) {
                    $maxCounter = $counter;
                }
            }
        }

        if ($maxCounter > (int) $pattern->last_number) {
            $patternId = $pattern->id;
            $this->db->update('inv_sequence_patterns', ['last_number' => $maxCounter], "id = $patternId");
        }
    }
}