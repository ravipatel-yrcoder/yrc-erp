<?php
class AttachmentsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    // GET /attachments/:id
    public function downloadAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $companyId = auth()->getCompanyId();

        $attachment = new Models_Attachment($id);

        if ($attachment->isEmpty || $attachment->company_id != $companyId) {
            http_response_code(404);
            exit('Not found');
        }

        $attService = new Service_Attachment(tenantContext());
        $featureKey = $attService->resolveParentFeatureKey($attachment);
        if (!$featureKey || !tenantContext()->canDo($featureKey, 'read')) {
            http_response_code(403);
            exit('Access denied');
        }

        $filePath = APP_PATH . '/storage/attachments/' . $attachment->company_id . '/' . $attachment->file_name;

        if (!file_exists($filePath)) {
            http_response_code(404);
            exit('File not found');
        }

        $mime = $attachment->mime_type ?: 'application/octet-stream';
        //$isImage = strpos($mime, 'image/') === 0;
        $isImage = false;

        // Release session lock before streaming so other requests aren't blocked
        //session_write_close();

        // Discard any output already buffered (PHP notices, whitespace, etc.)
        // so they don't corrupt the binary file response
        //while (ob_get_level() > 0) {
            //ob_end_clean();
        //}

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($filePath));
        // Images render inline in browser; all other types trigger a download
        header('Content-Disposition: ' . ($isImage ? 'inline' : 'attachment') . '; filename="' . rawurlencode($attachment->original_name) . '"');
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');

        readfile($filePath);
        exit;
    }
}
?>
