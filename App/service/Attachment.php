<?php
class Service_Attachment extends Service_Base {

    private const ALLOWED_MIME_TYPES = [
        // Images
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        // PDF
        'application/pdf',
        // Word
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        // Excel
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        // PowerPoint
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        // CSV / Text
        'text/csv', 'application/csv', 'text/plain',
        // Rich Text
        'application/rtf', 'text/rtf',
        // Archives
        'application/zip', 'application/x-zip-compressed',
        // Data exchange
        'application/xml', 'text/xml', 'application/json',
        // Email
        'application/vnd.ms-outlook',
    ];

    private const MAX_FILE_SIZE  = 10 * 1024 * 1024; // 10 MB
    private const MAX_FILES = 5;
    private const VALID_ENTITIES = ['activity', 'crm_lead_history', 'sales_order_history', 'purchase_order_history', 'purchase_inquiry_history'];


    /**
     * Decode and save base64-encoded files from the JSON payload.
     * $attachments = array of { name, mime_type, content (base64 string) }
     */
    public function saveFromBase64(array $attachments, string $entity, int $entityId): void {

        if (empty($attachments) || !in_array($entity, self::VALID_ENTITIES)) return;

        if (count($attachments) > self::MAX_FILES) {
            throw new Service_Exception("Maximum " . self::MAX_FILES . " attachments allowed", 422);
        }



        $this->db->startTransaction();


        $savedFiles = [];

        try {

            $companyId = $this->context->companyId;
            $storageDir = $this->storageDir($companyId);
            $finfo = new finfo(FILEINFO_MIME_TYPE);

            foreach ($attachments as $att) {

                $att = (array) $att;

                $content = base64_decode($att['content'] ?? '', true);
                if ($content === false || empty($content)) {
                    throw new Service_Exception("Invalid file content for: " . ($att['name'] ?? 'unknown'), 422);
                }

                if (strlen($content) > self::MAX_FILE_SIZE) {
                    throw new Service_Exception("File too large (max 10 MB): " . ($att['name'] ?? ''), 422);
                }

                // Detect MIME from actual bytes — do not trust the frontend value
                $mime = $finfo->buffer($content);

                // finfo returns 'application/octet-stream' for many file types on Windows
                // when its magic database doesn't recognise the format (PDF, Office docs, etc.).
                // Fall back to a safe extension→MIME map so those files aren't wrongly rejected.
                if ($mime === 'application/octet-stream') {
                    $ext = strtolower(pathinfo($att['name'] ?? '', PATHINFO_EXTENSION));
                    $extMap = [
                        'pdf'  => 'application/pdf',
                        'doc'  => 'application/msword',
                        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'xls'  => 'application/vnd.ms-excel',
                        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'ppt'  => 'application/vnd.ms-powerpoint',
                        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'csv'  => 'text/csv',
                        'rtf'  => 'application/rtf',
                        'zip'  => 'application/zip',
                        'xml'  => 'application/xml',
                        'json' => 'application/json',
                        'txt'  => 'text/plain',
                        'msg'  => 'application/vnd.ms-outlook',
                    ];
                    if (isset($extMap[$ext])) {
                        $mime = $extMap[$ext];
                    }
                }

                if (!in_array($mime, self::ALLOWED_MIME_TYPES)) {
                    throw new Service_Exception("File type not allowed: " . ($att['name'] ?? ''), 422);
                }

                $ext = strtolower(pathinfo($att['name'] ?? '', PATHINFO_EXTENSION));
                $fileName = bin2hex(random_bytes(16)) . ($ext ? '.' . $ext : '');
                $destPath = $storageDir . '/' . $fileName;

                if (file_put_contents($destPath, $content) === false) {
                    throw new Service_Exception("Failed to store file: " . ($att['name'] ?? ''), 500);
                }

                $attachment = new Models_Attachment();
                $attachment->company_id = $companyId;
                $attachment->entity = $entity;
                $attachment->entity_id = $entityId;
                $attachment->file_name = $fileName;
                $attachment->original_name = $att['name'] ?? $fileName;
                $attachment->file_size = strlen($content);
                $attachment->mime_type = $mime;
                $attachment->created_by = $this->context->userId;

                if (!$attachment->create()) {
                    unlink($destPath);
                    throw new Service_Exception("Failed to save attachment record", 500);
                }

                $savedFiles[] = $destPath;
            }


            $this->db->commit();

        } catch(Exception $e) {

            $this->db->rollback();

            // Unlink saved files
            foreach($savedFiles as $savedFile) {
                unlink($savedFile);
            }

            throw $e;
        }

    }


    /**
     * Delete specific attachment IDs — validates company ownership before acting.
     */
    public function deleteByIds(array $ids): void {


        $this->db->startTransaction();

        try {


            $deletedFiles = [];
            foreach ($ids as $id) {

                $id = (int) $id;
                if (!$id) continue;

                $attachment = new Models_Attachment($id);
                if ($attachment->isEmpty || $attachment->company_id != $this->context->companyId) continue;

                $filePath = $this->storageDir($attachment->company_id) . '/' . $attachment->file_name;
                if (file_exists($filePath)) {
                    //unlink($filePath);

                    $deletedFiles[] = $filePath;
                }

                $attachment->delete();
            }

            // Unlink files
            foreach($deletedFiles as $deletedFile) {
                unlink($deletedFile);
            }

            $this->db->commit();

        } catch(Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }


    /**
     * List all attachments for a single entity record.
     */
    public function listFor(string $entity, int $entityId): array {

        $rows = $this->db->fetchAll(
            "SELECT * FROM attachments WHERE entity = ? AND entity_id = ? AND company_id = ? ORDER BY created_at ASC",
            [$entity, $entityId, $this->context->companyId]
        );

        return array_map([$this, 'formatRow'], $rows ?: []);
    }


    /**
     * Batch fetch attachments for multiple entity IDs — keyed by entity_id.
     * Avoids N+1 in list/history views.
     */
    public function groupFor(string $entity, array $ids): array {

        if (empty($ids)) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->db->fetchAll(
            "SELECT * FROM attachments WHERE entity = ? AND entity_id IN ($placeholders) AND company_id = ? ORDER BY created_at ASC",
            array_merge([$entity], $ids, [$this->context->companyId])
        );

        $grouped = [];
        foreach ($rows ?: [] as $row) {
            $grouped[(int) $row->entity_id][] = $this->formatRow($row);
        }
        return $grouped;
    }


    /**
     * Resolves the parent feature key for an attachment — used by the download
     * controller to check the caller has read access to the parent object.
     * Returns null if the entity type is unknown or the parent record is missing.
     */
    public function resolveParentFeatureKey(Models_Attachment $attachment): ?string
    {
        if ($attachment->entity === 'activity') {
            $row = $this->db->fetchOne(
                "SELECT entity_type FROM activities WHERE id = ? AND company_id = ? LIMIT 1",
                [$attachment->entity_id, $this->context->companyId]
            );
            return $row ? Service_FeatureKeyResolver::resolve($row->entity_type) : null;
        }

        if ($attachment->entity === 'crm_lead_history') {
            return 'crm_leads';
        }

        if ($attachment->entity === 'sales_order_history') {
            $row = $this->db->fetchOne(
                "SELECT so.id FROM sales_order_history soh
                 JOIN sales_orders so ON so.id = soh.sales_order_id AND so.company_id = ?
                 WHERE soh.id = ? LIMIT 1",
                [$this->context->companyId, $attachment->entity_id]
            );
            return $row ? 'sales_orders' : null;
        }

        if ($attachment->entity === 'purchase_order_history') {
            $row = $this->db->fetchOne(
                "SELECT po.id FROM purchase_order_history poh
                 JOIN purchase_orders po ON po.id = poh.purchase_order_id AND po.company_id = ?
                 WHERE poh.id = ? LIMIT 1",
                [$this->context->companyId, $attachment->entity_id]
            );
            return $row ? 'purchase_orders' : null;
        }

        return null;
    }


    public function formatRow(object $row): array {
        return [
            'id' => (int) $row->id,
            'original_name' => $row->original_name,
            'file_size' => (int) $row->file_size,
            'mime_type' => $row->mime_type,
            'is_image' => strpos($row->mime_type, 'image/') === 0,
            'download_url'  => '/attachments/' . $row->id,
        ];
    }


    private function storageDir(int $companyId): string {
        $dir = APP_PATH . '/storage/attachments/' . $companyId;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }
}
?>
