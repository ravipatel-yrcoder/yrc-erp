<?php
class Helpers_FileUpload
{
    /**
     * Validate a Base64 encoded file.
     *
     * @param array $file  Expected keys: ['original_name', 'extension', 'mime_type', 'content']
     * @param array $allowedTypes  Allowed MIME types (e.g. ['image/jpeg', 'image/png'])
     * @param int   $maxSizeMB     Max file size in MB
     * @return array ['valid' => bool, 'error' => string|null, 'size' => int|null, 'mime' => string|null]
     */
    public static function validate(array $file, array $allowedTypes, int $maxSizeMB): array
    {
        // ---- Check content ----
        if (empty($file['content'])) {
            return ['valid' => false, 'error' => 'No file data provided'];
        }

        // ---- Decode Base64 ----
        $decoded = base64_decode($file['content'], true);
        if ($decoded === false) {
            return ['valid' => false, 'error' => 'Invalid Base64 content'];
        }

        // ---- File size check ----
        $sizeBytes = strlen($decoded);
        $maxBytes = $maxSizeMB * 1024 * 1024;

        if ($sizeBytes > $maxBytes) {
            return [
                'valid' => false,
                'error' => 'File too large. Max allowed size is ' . $maxSizeMB . 'MB',
                'size'  => $sizeBytes
            ];
        }

        // ---- Detect MIME type from actual data ----
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_buffer($finfo, $decoded);
        finfo_close($finfo);

        if (!in_array($realMime, $allowedTypes)) {
            return [
                'valid' => false,
                'error' => 'Invalid file type: ' . $realMime,
                'mime'  => $realMime
            ];
        }

        // ---- Validate extension (optional but good practice) ----
        $extension = strtolower($file['extension'] ?? '');
        $expectedExt = self::getExtensionsForMime($realMime);
        if ($expectedExt && !in_array($extension, $expectedExt)) {
            return [
                'valid' => false,
                'error' => 'File extension does not match type (' . $extension . ')',
                'mime'  => $realMime
            ];
        }

        return [
            'valid' => true,
            'size'  => $sizeBytes,
            'mime'  => $realMime,
            'error' => null
        ];
    }

    /**
     * Save a validated Base64 file to disk.
     *
     * @param array  $fileData  Base64 file data.
     * @param string $savePath  Absolute directory path (must be writable).
     * @param string|null $prefix Optional file name prefix.
     * @return array ['file_name' => string, 'path' => string, 'url' => string]
     */
    public static function save(array $fileData, string $savePath, ?string $prefix = null): array
    {
        if (!file_exists($savePath)) {
            mkdir($savePath, 0777, true);
        }


        // Extract and sanitize original name
        $originalName = pathinfo($fileData['name'] ?? 'file', PATHINFO_FILENAME);
        $extension = strtolower($fileData['extension'] ?? 'bin');


        // Remove any unwanted characters from filename
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $originalName);


        // Optionally prepend prefix
        if ($prefix) {
            $safeName = $prefix . '_' . $safeName;
        }


        // Build the initial file path
        $filePath = rtrim($savePath, '/') . '/' . $safeName . '.' . $extension;


        // Handle duplicates → append _1, _2, etc.
        $counter = 1;
        while (file_exists($filePath)) {
            $filePath = rtrim($savePath, '/') . '/' . $safeName . '_' . $counter . '.' . $extension;
            $counter++;
        }

            
        // Write file to disk
        file_put_contents($filePath, base64_decode($fileData['content']));
        

        // Compute relative URL for serving (under /public)
        $publicUrl = str_replace(ROOT_PATH . '/public', '', $filePath);

        return [
            'file_name' => basename($filePath),
            'path'      => $filePath,
            'url'       => $publicUrl
        ];
    }

    /**
     * Internal: map MIME types to possible extensions
     */
    protected static function getExtensionsForMime(string $mime): array
    {
        $map = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png'  => ['png'],
            'image/gif'  => ['gif'],
            'image/webp' => ['webp'],
            'application/pdf' => ['pdf'],
            'application/zip' => ['zip'],
            'application/vnd.ms-excel' => ['xls'],
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        ];
        return $map[$mime] ?? [];
    }
}