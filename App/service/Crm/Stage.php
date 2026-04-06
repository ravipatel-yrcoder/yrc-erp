<?php
class Service_Crm_Stage extends Service_Base {


    private function getStageOrFail(int $stageId) {

        $stage = new Models_CrmStage($stageId);
        if( $stage->isEmpty ) {
            throw new Service_Exception("The requested stage was not found", 404);
        }

        if( $stage->company_id != $this->context->companyId ) {
            throw new Service_Exception("You do not have permission to access this stage", 403);
        }

        return $stage;
    }


    public function list() {

        $companyId = $this->context->companyId;

        $sql = "SELECT id, name, probability, sort_order, is_won, is_lost, color, status, created_at
                FROM crm_stages
                WHERE company_id = ?
                ORDER BY sort_order ASC, id ASC";

        return $this->db->fetchAll($sql, [$companyId]);
    }


    public function getFormContext(int $stageId = 0) {

        $stageDetails = [];

        if( $stageId ) {
            $stage = $this->getStageOrFail($stageId);
            $stageDetails = array_merge(['id' => $stageId], $stage->toArray());
        }

        return [
            'stageDetails' => $stageDetails,
        ];
    }


    private function validatePayload(array $payload, int $stageId = 0) {

        $companyId = $this->context->companyId;

        if( empty(trim($payload['name'] ?? '')) ) {
            $this->addError(validationErrMsg("required", "Stage name"), "name");
        } else {
            // check uniqueness within company
            $sql = "SELECT id FROM crm_stages WHERE company_id = ? AND LOWER(name) = ?";
            $bindings = [$companyId, strtolower(trim($payload['name']))];
            if( $stageId > 0 ) {
                $sql .= " AND id != ?";
                $bindings[] = $stageId;
            }
            $sql .= " LIMIT 1";
            $existing = $this->db->fetchOne($sql, $bindings);
            if( $existing ) {
                $this->addError(validationErrMsg("duplicate", "Stage name"), "name");
            }
        }

        $probability = $payload['probability'] ?? '';
        if( $probability !== '' && $probability !== null ) {
            if( !is_numeric($probability) || $probability < 0 || $probability > 100 ) {
                $this->addError(validationErrMsg("invalid", "Probability"), "probability");
            }
        }

        // Only one Won stage and one Lost stage allowed per company
        if( !empty($payload['is_won']) ) {
            $sql = "SELECT id FROM crm_stages WHERE company_id = ? AND is_won = 1";
            $bindings = [$companyId];
            if( $stageId > 0 ) {
                $sql .= " AND id != ?";
                $bindings[] = $stageId;
            }
            $sql .= " LIMIT 1";
            if( $this->db->fetchOne($sql, $bindings) ) {
                $this->addError("A Won stage already exists. Only one Won stage is allowed.", "stage_type");
            }
        }

        if( !empty($payload['is_lost']) ) {
            $sql = "SELECT id FROM crm_stages WHERE company_id = ? AND is_lost = 1";
            $bindings = [$companyId];
            if( $stageId > 0 ) {
                $sql .= " AND id != ?";
                $bindings[] = $stageId;
            }
            $sql .= " LIMIT 1";
            if( $this->db->fetchOne($sql, $bindings) ) {
                $this->addError("A Lost stage already exists. Only one Lost stage is allowed.", "stage_type");
            }
        }
    }


    private function normalizePayload(array &$payload) {

        $payload['name'] = trim($payload['name'] ?? '');
        $payload['probability'] = (int) ($payload['probability'] ?? 0);
        $payload['sort_order'] = (int) ($payload['sort_order'] ?? 0);
        $payload['is_won'] = !empty($payload['is_won']) ? 1 : 0;
        $payload['is_lost'] = !empty($payload['is_lost']) ? 1 : 0;
        $payload['color'] = trim($payload['color'] ?? '') ?: null;
        $payload['status'] = ($payload['status'] ?? '') === 'active' ? 'active' : 'inactive';

        // is_won and is_lost are mutually exclusive
        if( $payload['is_won'] && $payload['is_lost'] ) {
            $payload['is_lost'] = 0;
        }
    }


    public function create(array $payload) {

        $this->normalizePayload($payload);
        $this->validatePayload($payload);

        if( $this->hasErrors() ) {
            return [
                "success" => false,
                "errors"  => $this->getErrors()
            ];
        }

        $companyId = $this->context->companyId;
        $userId = $this->context->userId;

        // Auto-assign sort_order if not provided
        if( $payload['sort_order'] <= 0 ) {
            $maxSort = $this->db->fetchVar(
                "SELECT COALESCE(MAX(sort_order), 0) FROM crm_stages WHERE company_id = ?",
                [$companyId]
            );
            $payload['sort_order'] = (int) $maxSort + 1;
        }

        $stage = new Models_CrmStage();
        $stage->company_id = $companyId;
        $stage->name = $payload['name'];
        $stage->probability = $payload['probability'];
        $stage->sort_order = $payload['sort_order'];
        $stage->is_won = $payload['is_won'];
        $stage->is_lost = $payload['is_lost'];
        $stage->color = $payload['color'];
        $stage->status = $payload['status'];
        $stage->created_by = $userId;

        $id = $stage->create();

        if( !$id ) {
            throw new Service_Exception("Failed to create stage");
        }

        return [
            "success" => true,
            "data" => ["id" => $id],
        ];
    }


    public function update(int $stageId, array $payload) {

        $stage = $this->getStageOrFail($stageId);

        $this->normalizePayload($payload);
        $this->validatePayload($payload, $stageId);

        if( $this->hasErrors() ) {
            return [
                "success" => false,
                "errors"  => $this->getErrors()
            ];
        }

        $stage->name = $payload['name'];
        $stage->probability = $payload['probability'];
        $stage->sort_order = $payload['sort_order'];
        $stage->is_won = $payload['is_won'];
        $stage->is_lost = $payload['is_lost'];
        $stage->color = $payload['color'];
        $stage->status = $payload['status'];

        if( !$stage->update() ) {
            throw new Service_Exception("Failed to update stage");
        }

        return [
            "success" => true,
            "data" => [],
        ];
    }


    public function delete(int $stageId) {

        $stage = $this->getStageOrFail($stageId);

        // Check if any leads are using this stage
        $leadCount = $this->db->fetchVar(
            "SELECT COUNT(id) FROM crm_leads WHERE stage_id = ? AND company_id = ?",
            [$stageId, $this->context->companyId]
        );

        if( $leadCount > 0 ) {
            throw new Service_Exception("Cannot delete stage that has leads assigned to it. Move or reassign leads first.", 422);
        }

        $stage->delete();

        if( $stage->getDeletedRows() > 0 ) {
            return ["success" => true];
        }

        throw new Service_Exception("Failed to delete stage");
    }
}
?>
