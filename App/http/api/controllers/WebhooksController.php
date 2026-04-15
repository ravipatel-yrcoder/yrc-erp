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

        $db = DB();

        try {

            $payload = $request->getRawPayload();

            $source = strtolower(trim($request->getInput('source', 'String', '')));
            $token = trim($request->getInput('token', 'String', ''));
            $method = strtoupper($request->getMethod());
            $ip = $request->getIp();

            $headers = $request->getHeaders();
            
            // Reject unsupported sources — return 400 only for completely unknown sources            
            if( empty($source) || !in_array($source, self::SUPPORTED_SOURCES, true) ) {
                response([], 'Invalid request', 400)->sendJson();
            }

            // Look up the integration — match on both token and source
            $integration = new Models_WebhookIntegration();
            $integration->fetchByProperty(['token', 'source'], [$token, $source]);

            if( $integration->isEmpty ) {
                // still return 200 for bad tokens so we do not leak information to attackers
                response([], 'Received', 200)->sendJson();
            }


            $db->startTransaction();

            $integrationId = $integration->id;
            $companyId = $integration->company_id;

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

            // Integration disabled - log as ignored
            if( (bool) $integration->is_active !== 1) {
                $log->status = 'ignored';
                $log->failure_reason = 'Integration is inactive';
            }
            
            if( !$log->create() ) {
                throw new Service_Exception("Failed to record webhook request");
            }

            $db->commit();

            response(['received' => true])->sendJson();

        } catch(Service_Exception $e) {

            $db->rollback();
            response([], $e->getMessage() ?: "Failed to process request", 500)->sendJson();
        } 
        catch(Exception $e) {

            $db->rollback();
            response([], $e->getMessage() ?: "Failed to process request", 500)->sendJson();
        }        
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
