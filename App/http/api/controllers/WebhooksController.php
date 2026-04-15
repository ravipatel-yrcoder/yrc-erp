<?php
class Api_WebhooksController extends TinyPHP_Controller {

    /**
     * Known/supported webhook sources.
     * Add new sources here as integrations are built.
     */
    private const SUPPORTED_SOURCES = ['indiamart', 'justdial', 'wordpress'];

    public function init() {
        $this->setNoRenderer(true);
    }


    // POST /api/webhooks/:source/:token
    public function receiveAction(TinyPHP_Request $request) {

        if( !$request->isMethod("post") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        $payload = $request->getRawPayload();

        $source = strtolower(trim($request->getInput('source', 'String', '')));
        $token = trim($request->getInput('token', 'String', ''));
        $method = strtoupper($request->getMethod());
        $ip = $request->getIp();

        $headers = $request->getHeaders();
        
        // Reject unsupported sources — return 400 only for completely unknown sources
        // (still return 200 for bad tokens so we do not leak information to attackers)
        if( empty($source) || !in_array($source, self::SUPPORTED_SOURCES, true) ) {
            response([], 'Unknown webhook source', 400)->sendJson();
        }

        // Look up the integration — match on both token and source
        $integration = new Models_WebhookIntegration();
        $integration->fetchByProperty(['token', 'source'], [$token, $source]);

        $integrationId = !$integration->isEmpty ? $integration->id : null;
        $companyId = !$integration->isEmpty ? $integration->company_id : null;

        // Write the log row immediately — we want every call persisted
        // regardless of what happens next
        $log = new Models_WebhookLog();
        $log->integration_id  = $integrationId;
        $log->company_id = $companyId;
        $log->source = $source;
        $log->token = $token;
        $log->http_method = $method;
        $log->headers = !empty($headers) ? json_encode($headers) : null;
        $log->raw_payload = $payload ?: null;
        $log->status = 'received';
        $log->ip_address = $ip;        

        // Integration not found — log as ignored and return 200
        // (do not reveal that the token is invalid)
        if( $integration->isEmpty ) {

            $log->status = 'ignored';
            $log->failure_reason = 'No integration found for this token and source';
            $log->create();
            response(['received' => true])->sendJson();
        }

        // Integration disabled — log as ignored and return 200
        if( !(bool)$integration->is_active ) {
            $log->status = 'ignored';
            $log->failure_reason = 'Integration is inactive';
            $log->create();
            response(['received' => true])->sendJson();
        }

        // Integration is valid and active — persist the log row first so nothing is lost
        $log->create();


        /*
        // Parse the payload into a structured representation
        $parsedPayload = $this->parsePayload($rawBody, $capturedHeaders);

        // Update the log with the parsed payload and mark as processed
        $log->parsed_payload = $parsedPayload !== null ? json_encode($parsedPayload) : null;
        $log->status         = 'processed';
        $log->processed_at   = date('Y-m-d H:i:s');
        $log->update();
        */

        response(['received' => true])->sendJson();
    }


    /**
     * Attempt to parse the raw body into a structured array.
     * Tries JSON first, then form-encoded, then returns null.
     *
     * @param string|null $rawBody
     * @param array       $headers
     * @return array|null
     */
    /*
    private function parsePayload(?string $rawBody, array $headers): ?array {

        if( empty($rawBody) ) {
            return null;
        }

        $contentType = strtolower($headers['content-type'] ?? '');

        // Try JSON
        if( strpos($contentType, 'application/json') !== false ) {
            $decoded = json_decode($rawBody, true);
            return is_array($decoded) ? $decoded : null;
        }

        // Try form-encoded
        if( strpos($contentType, 'application/x-www-form-urlencoded') !== false ) {
            parse_str($rawBody, $parsed);
            return !empty($parsed) ? $parsed : null;
        }

        // Fallback — try JSON regardless of content-type (some senders omit it)
        $decoded = json_decode($rawBody, true);
        if( is_array($decoded) ) {
            return $decoded;
        }

        return null;
    }
    */
}
?>
