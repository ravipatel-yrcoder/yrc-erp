<?php
class Service_Activity extends Service_Base {

    private $validTypes        = ['call', 'email', 'meeting', 'todo'];
    private $validRelatedTypes = ['lead'];


    public function list(string $relatedType, int $relatedId): array {

        $results = $this->db->fetchAll(
            "SELECT a.*, u.name AS assigned_user_name
             FROM activities a
             LEFT JOIN users u ON u.id = a.assigned_to
             WHERE a.company_id = ? AND a.related_type = ? AND a.related_id = ?
             ORDER BY a.is_done ASC, a.due_date ASC, a.due_time ASC",
            [$this->context->companyId, $relatedType, $relatedId]
        );

        return $results ?: [];
    }


    public function create(array $payload): array {

        $this->validatePayload($payload);

        if( $this->hasErrors() ) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $activity = new Models_Activity();
        $activity->company_id   = $this->context->companyId;
        $activity->related_type = $payload['related_type'];
        $activity->related_id   = (int) $payload['related_id'];
        $activity->type         = $payload['type'];
        $activity->summary      = $payload['summary'];
        $activity->due_date     = $payload['due_date'];
        $activity->due_time     = !empty($payload['due_time']) ? $payload['due_time'] : null;
        $activity->assigned_to  = !empty($payload['assigned_to']) ? (int) $payload['assigned_to'] : null;
        $activity->note         = !empty($payload['note']) ? $payload['note'] : null;
        $activity->created_by   = $this->context->userId;

        if( !$activity->create() ) {
            throw new Service_Exception("Failed to create activity");
        }

        return ['success' => true, 'data' => ['id' => $activity->id]];
    }


    public function update(int $activityId, array $payload): array {

        $activity = $this->getActivityOrFail($activityId);

        $this->validatePayload($payload, $activityId);

        if( $this->hasErrors() ) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $activity->type        = $payload['type'];
        $activity->summary     = $payload['summary'];
        $activity->due_date    = $payload['due_date'];
        $activity->due_time    = !empty($payload['due_time']) ? $payload['due_time'] : null;
        $activity->assigned_to = !empty($payload['assigned_to']) ? (int) $payload['assigned_to'] : null;
        $activity->note        = !empty($payload['note']) ? $payload['note'] : null;

        if( !$activity->update() ) {
            throw new Service_Exception("Failed to update activity");
        }

        return ['success' => true, 'data' => ['id' => $activityId]];
    }


    public function markDone(int $activityId, array $payload): array {

        $activity = $this->getActivityOrFail($activityId);

        if( $activity->is_done ) {
            throw new Service_Exception("Activity is already marked as done", 422);
        }

        $activity->is_done = 1;
        $activity->done_at = date("Y-m-d H:i:s");
        $activity->outcome = !empty($payload['outcome']) ? $payload['outcome'] : null;

        if( !$activity->update() ) {
            throw new Service_Exception("Failed to mark activity as done");
        }

        // Log to crm_lead_history when related to a lead
        if( $activity->related_type === 'lead' ) {
            $typeLabels = ['call' => 'Call', 'email' => 'Email', 'meeting' => 'Meeting', 'todo' => 'To-Do'];
            $typeLabel  = $typeLabels[$activity->type] ?? ucfirst($activity->type);

            $history = new Models_CrmLeadHistory();
            $history->company_id = $this->context->companyId;
            $history->lead_id    = $activity->related_id;
            $history->log_type   = 'activity';
            $history->title      = $typeLabel . ' done: ' . $activity->summary;
            $history->meta       = !empty($payload['outcome'])
                ? json_encode(['outcome' => $payload['outcome']], JSON_UNESCAPED_UNICODE)
                : null;
            $history->created_by = $this->context->userId;
            $history->create();
        }

        return ['success' => true, 'data' => []];
    }


    public function delete(int $activityId): void {
        
        $activity = $this->getActivityOrFail($activityId);
        $activity->delete();

        if( !($activity->getDeletedRows() > 0)  ) {
            throw new Service_Exception("Failed to delete activity");
        }
    }


    public function getFormContext(int $activityId = 0): array {

        $users = $this->db->fetchAll(
            "SELECT id, name FROM users WHERE company_id = ? AND status = 'active' ORDER BY name ASC",
            [$this->context->companyId]
        );

        $activityDetails = null;
        if( $activityId ) {
            $activity = $this->getActivityOrFail($activityId);
            $activityDetails = array_merge(['id' => $activityId], $activity->toArray());
        }

        return [
            'users'           => $users ?: [],
            'activityDetails' => $activityDetails,
        ];
    }


    private function validatePayload(array $payload, int $excludeId = 0): void {

        if( empty($payload['type']) || !in_array($payload['type'], $this->validTypes) ) {
            $this->addError(validationErrMsg("invalid", "Activity type"), "type");
        }

        if( empty($payload['summary']) ) {
            $this->addError(validationErrMsg("required", "Summary"), "summary");
        }

        if( empty($payload['due_date']) ) {
            $this->addError(validationErrMsg("required", "Due date"), "due_date");
        }

        // required only on create
        if( !$excludeId ) {
            if( empty($payload['related_type']) || !in_array($payload['related_type'], $this->validRelatedTypes) ) {
                $this->addError(validationErrMsg("invalid", "Related type"), "related_type");
            }
            if( empty($payload['related_id']) ) {
                $this->addError(validationErrMsg("required", "Related record"), "related_id");
            }
        }
    }


    private function getActivityOrFail(int $activityId): Models_Activity {
        $activity = new Models_Activity($activityId);
        if( $activity->isEmpty || $activity->company_id != $this->context->companyId ) {
            throw new Service_Exception("Activity not found", 404);
        }
        return $activity;
    }

}
?>
