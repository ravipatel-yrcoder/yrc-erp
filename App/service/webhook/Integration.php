<?php
class Service_Webhook_Integration extends Service_Base {

    /** Sources supported by the system. Add to this list as new sources are built. */
    public const SOURCES = [
        'indiamart' => 'IndiaMART',
    ];


    private function getIntegrationOrFail(int $id): Models_WebhookIntegration {

        $integration = new Models_WebhookIntegration($id);

        if( $integration->isEmpty ) {
            throw new Service_Exception("The requested integration was not found", 404);
        }

        if( $integration->company_id != $this->context->companyId ) {
            throw new Service_Exception("You do not have permission to access this integration", 403);
        }

        return $integration;
    }


    /**
     * Generate a unique token that embeds the company ID so it is
     * always scoped to a specific tenant.
     */
    private function generateToken(int $companyId): string {
        return md5($companyId . '_webhook_' . uniqid('', true));
    } 


    public function getFormContext(int $id = 0): array {

        $integrationDetails = [];

        if( $id > 0 ) {
            $integration = $this->getIntegrationOrFail($id);
            $integrationDetails = array_merge(['id' => $id], $integration->toArray());
        }

        return [
            'sources' => self::SOURCES,
            'integrationDetails' => $integrationDetails,
        ];
    }


    private function normalizePayload(array &$payload): void {

        $payload['name'] = trim($payload['name']   ?? '');
        $payload['source'] = trim($payload['source'] ?? '');
        $payload['is_active'] = isset($payload['is_active']) && $payload['is_active'] ? 1 : 0;
    }


    private function validatePayload(array $payload, int $id = 0): void {

        if( empty($payload['name']) ) {
            $this->addError(validationErrMsg("required", "Name"), "name");
        }

        if( empty($payload['source']) ) {
            $this->addError(validationErrMsg("required", "Source"), "source");
        } elseif( !array_key_exists($payload['source'], self::SOURCES) ) {
            $this->addError(validationErrMsg("invalid", "Source"), "source");
        }

        // Prevent duplicate source per company (only one integration per source)
        if( !empty($payload['source']) ) {
            $sql      = "SELECT id FROM webhook_integrations WHERE company_id = ? AND source = ?";
            $bindings = [$this->context->companyId, $payload['source']];
            if( $id > 0 ) {
                $sql      .= " AND id != ?";
                $bindings[] = $id;
            }
            $sql .= " LIMIT 1";
            if( $this->db->fetchOne($sql, $bindings) ) {
                $this->addError("An integration for this source already exists.", "source");
            }
        }
    }


    public function create(array $payload): array {

        if (!$this->context->canDo('crm_integrations', 'write')) {
            throw new Service_Exception('You do not have permission to create integrations', 403);
        }

        $this->normalizePayload($payload);
        $this->validatePayload($payload);

        if( $this->hasErrors() ) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $this->db->startTransaction();

        try {

            $companyId = $this->context->companyId;
            $userId = $this->context->userId;

            $integration = new Models_WebhookIntegration();
            $integration->company_id  = $companyId;
            $integration->name = $payload['name'];
            $integration->source = $payload['source'];
            $integration->token = $this->generateToken($companyId);
            $integration->is_active = $payload['is_active'];
            $integration->created_by  = $userId;

            $id = $integration->create();

            if( !$id ) {
                throw new Service_Exception("Failed to create integration");
            }

            $this->db->commit();

            return ['success' => true, 'data' => ['id' => $id]];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }        
    }


    public function update(int $id, array $payload): array {

        if (!$this->context->canDo('crm_integrations', 'write')) {
            throw new Service_Exception('You do not have permission to update integrations', 403);
        }

        $integration = $this->getIntegrationOrFail($id);

        $this->normalizePayload($payload);

        // Only name and is_active are editable; source and token are immutable after creation
        if( empty($payload['name']) ) {
            $this->addError(validationErrMsg("required", "Name"), "name");
        }

        if( $this->hasErrors() ) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $this->db->startTransaction();

        try {

            $integration->name = $payload['name'];
            $integration->is_active = $payload['is_active'];

            if( !$integration->update() ) {
                throw new Service_Exception("Failed to update integration");
            }

            $this->db->commit();

            return ['success' => true, 'data' => []];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }        
    }


    public function delete(int $id): array {

        if (!$this->context->canDo('crm_integrations', 'delete')) {
            throw new Service_Exception('You do not have permission to delete integrations', 403);
        }

        $integration = $this->getIntegrationOrFail($id);

        $this->db->startTransaction();

        try {

            $integration->delete();

            if( !($integration->getDeletedRows() > 0) ) {
                throw new Service_Exception("Failed to delete integration");
            }

            $this->db->commit();
            
            return ['success' => true];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }        
    }
}
?>
