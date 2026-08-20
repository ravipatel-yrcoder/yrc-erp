<?php
class Service_Activity extends Service_Base {

    private $validTypes = ['call', 'email', 'meeting', 'todo'];
    private $validRelatedTypes = ['lead'];


    public function list(string $entityType, int $entityId): array {

        $featureKey = Service_FeatureKeyResolver::resolve($entityType);
        if (!$featureKey || !$this->context->canDo($featureKey, 'read')) {
            throw new Service_Exception('You do not have permission to view activities', 403);
        }

        $results = $this->db->fetchAll(
            "SELECT a.*, u.name AS assigned_user_name
             FROM activities a
             LEFT JOIN users u ON u.id = a.assigned_to
             WHERE a.company_id = ? AND a.entity_type = ? AND a.entity_id = ?
             ORDER BY a.status ASC, a.due_date ASC, a.due_time ASC",
            [$this->context->companyId, $entityType, $entityId]
        );

        if (!empty($results)) {

            $activityIds = array_map(fn($a) => (int) $a->id, $results);
            $attService = new Service_Attachment($this->context);
            $grouped = $attService->groupFor('activity', $activityIds);
            foreach ($results as &$row) {
                $row->attachments = $grouped[$row->id] ?? [];
            }

            unset($row);
        }

        return $results ?: [];
    }


    public function create(array $payload): array {

        $featureKey = Service_FeatureKeyResolver::resolve($payload['entity_type'] ?? '');
        if (!$featureKey || !$this->context->canDo($featureKey, 'write')) {
            throw new Service_Exception('You do not have permission to create activities', 403);
        }

        $this->validatePayload($payload);

        if( $this->hasErrors() ) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $this->db->startTransaction();

        try {

            $activity = new Models_Activity();
            $activity->fillFromArray($payload);
            $activity->company_id = $this->context->companyId;
            $activity->created_by = $this->context->userId;

            //$activity->entity_type = $payload['entity_type'];
            //$activity->entity_id = (int) $payload['entity_id'];
            //$activity->activity_type = $payload['activity_type'];
            //$activity->summary = $payload['summary'];
            //$activity->due_date = $payload['due_date'];
            //$activity->due_time = !empty($payload['due_time']) ? $payload['due_time'] : null;
            //$activity->assigned_to = !empty($payload['assigned_to']) ? (int) $payload['assigned_to'] : null;
            //$activity->description = !empty($payload['description']) ? $payload['description'] : null;
            

            if( !$activity->create() ) {
                throw new Service_Exception("Failed to create activity");
            }

            $attachments = $payload['attachments'] ?? [];
            if (!empty($attachments) && is_array($attachments)) {
                $attService = new Service_Attachment($this->context);
                $attService->saveFromBase64($attachments, 'activity', (int) $activity->id);
            }

            $this->db->commit();

            return ['success' => true, 'data' => ['id' => $activity->id]];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }


    public function update(int $activityId, array $payload): array {

        $activity = $this->getActivityOrFail($activityId);
        $featureKey = Service_FeatureKeyResolver::resolve($activity->entity_type);
        if (!$featureKey || !$this->context->canDo($featureKey, 'write')) {
            throw new Service_Exception('You do not have permission to update activities', 403);
        }

        $this->validatePayload($payload, $activityId);

        if( $this->hasErrors() ) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $this->db->startTransaction();

        try {

            $activity->activity_type = $payload['activity_type'];
            $activity->summary = $payload['summary'];
            $activity->due_date = $payload['due_date'];
            $activity->due_time = !empty($payload['due_time']) ? $payload['due_time'] : null;
            $activity->assigned_to = !empty($payload['assigned_to']) ? (int) $payload['assigned_to'] : null;
            $activity->description = !empty($payload['description']) ? $payload['description'] : null;

            if( !$activity->update() ) {
                throw new Service_Exception("Failed to update activity");
            }

            $attService = new Service_Attachment($this->context);

            // Delete attachments the user marked for removal (deferred until save)
            $deleteIds = !empty($payload['delete_attachment_ids']) && is_array($payload['delete_attachment_ids']) ? array_map('intval', $payload['delete_attachment_ids']) : [];
            if (!empty($deleteIds)) {
                $attService->deleteByIds($deleteIds);
            }

            // Save newly added attachments
            $attachments = $payload['attachments'] ?? [];
            if (!empty($attachments) && is_array($attachments)) {
                $attService->saveFromBase64($attachments, 'activity', $activityId);
            }

            $this->db->commit();

            return ['success' => true, 'data' => ['id' => $activityId]];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }        
    }


    public function updateStatus(int $activityId, array $payload): array {

        $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled', 'skipped'];
        $newStatus = $payload['status'] ?? '';
        if (!in_array($newStatus, $validStatuses, true)) {
            return ['success' => false, 'errors' => ['status' => validationErrMsg('invalid', 'Status')]];
        }

        $activity = $this->getActivityOrFail($activityId);

        $featureKey = Service_FeatureKeyResolver::resolve($activity->entity_type);
        $hasParentPerm = $featureKey && $this->context->canDo($featureKey, 'write');
        $hasActivitiesPerm = $this->context->canDo('activities', 'mark_complete');
        if (!$hasParentPerm && !$hasActivitiesPerm) {
            throw new Service_Exception('You do not have permission to update this activity status', 403);
        }

        $this->db->startTransaction();

        try {

            $wasCompleted = $activity->status === 'completed';
            $activity->status = $newStatus;

            if ($newStatus === 'completed') {
                $activity->completed_at = date("Y-m-d H:i:s");
                $activity->completed_by = $this->context->userId;
                $activity->outcome      = !empty($payload['outcome']) ? $payload['outcome'] : null;
            } elseif ($wasCompleted) {
                // Reverting from completed — clear completion fields
                $activity->completed_at = null;
                $activity->completed_by = null;
                $activity->outcome      = null;
            }

            if (!$activity->update()) {
                throw new Service_Exception("Failed to update activity status");
            }

            // Log to crm_lead_history when completing a lead activity
            if ($newStatus === 'completed' && $activity->entity_type === 'lead') {

                $typeLabels = ['call' => 'Call', 'email' => 'Email', 'meeting' => 'Meeting', 'todo' => 'To-Do'];
                $typeLabel  = $typeLabels[$activity->activity_type] ?? ucfirst($activity->activity_type);

                $history             = new Models_CrmLeadHistory();
                $history->company_id = $this->context->companyId;
                $history->lead_id    = $activity->entity_id;
                $history->log_type   = 'activity_done';
                $history->title      = $typeLabel . ' done: ' . $activity->summary;
                $history->meta       = !empty($payload['outcome']) ? json_encode(['outcome' => $payload['outcome']], JSON_UNESCAPED_UNICODE) : null;
                $history->created_by = $this->context->userId;
                $history->create();
            }

            $this->db->commit();

            return ['success' => true, 'data' => []];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }


    public function listForPage(array $filters, array $dtParams): array {

        if (!$this->context->canDo('activities', 'read')) {
            throw new Service_Exception('You do not have permission to view activities', 403);
        }

        $scope = (new Service_Scope($this->context))->getCondition('activities', ['a.created_by', 'a.assigned_to']);

        $where    = ["a.company_id = ?"];
        $params   = [$this->context->companyId];

        if ($scope['sql']) {
            $where[]  = "(" . $scope['sql'] . ")";
            $params   = array_merge($params, $scope['bindings']);
        }

        // Filters
        if (!empty($filters['activity_type'])) {
            $where[]  = "a.activity_type = ?";
            $params[] = $filters['activity_type'];
        }
        if (!empty($filters['status'])) {
            $where[]  = "a.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $where[]  = "a.priority = ?";
            $params[] = $filters['priority'];
        }
        if (!empty($filters['entity_type'])) {
            $where[]  = "a.entity_type = ?";
            $params[] = $filters['entity_type'];
        }
        if (!empty($filters['assigned_to'])) {
            $where[]  = "a.assigned_to = ?";
            $params[] = (int) $filters['assigned_to'];
        }

        // Due date preset / range
        $today = dateNow('Y-m-d');
        $preset = $filters['due_date_preset'] ?? '';
        if ($preset === 'overdue') {
            $where[]  = "a.due_date < ? AND a.status NOT IN ('completed','cancelled','skipped')";
            $params[] = $today;
        } elseif ($preset === 'today') {
            $where[]  = "a.due_date = ?";
            $params[] = $today;
        } elseif ($preset === 'this_week') {
            $where[]  = "a.due_date BETWEEN ? AND ?";
            $params[] = dateNow('Y-m-d', 'monday this week');
            $params[] = dateNow('Y-m-d', 'sunday this week');
        } elseif ($preset === 'unscheduled') {
            $where[] = "a.due_date IS NULL";
        } elseif ($preset === 'custom') {
            if (!empty($filters['due_date_from'])) {
                $where[]  = "a.due_date >= ?";
                $params[] = $filters['due_date_from'];
            }
            if (!empty($filters['due_date_to'])) {
                $where[]  = "a.due_date <= ?";
                $params[] = $filters['due_date_to'];
            }
        }

        $baseWhere = implode(' AND ', $where);

        // Total (unfiltered by search)
        $totalRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM activities a WHERE {$baseWhere}",
            $params
        );
        $total = $totalRow ? (int) $totalRow->cnt : 0;

        // DataTable search
        $search = trim($dtParams['search']['value'] ?? '');
        if ($search !== '') {
            $where[]  = "(a.summary LIKE ? OR u.name LIKE ?)";
            $like     = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $filteredWhere = implode(' AND ', $where);

        $filteredRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM activities a
             LEFT JOIN users u ON u.id = a.assigned_to
             WHERE {$filteredWhere}",
            $params
        );
        $filtered = $filteredRow ? (int) $filteredRow->cnt : 0;

        $start  = max(0, (int) ($dtParams['start']  ?? 0));
        $length = max(1, (int) ($dtParams['length'] ?? 25));

        $rows = $this->db->fetchAll(
            "SELECT a.*, u.name AS assigned_user_name, cb.name AS created_by_name
             FROM activities a
             LEFT JOIN users u  ON u.id  = a.assigned_to
             LEFT JOIN users cb ON cb.id = a.created_by
             WHERE {$filteredWhere}
             ORDER BY
               CASE a.status WHEN 'pending' THEN 0 WHEN 'in_progress' THEN 1 ELSE 2 END ASC,
               a.due_date ASC, a.due_time ASC
             LIMIT ? OFFSET ?",
            array_merge($params, [$length, $start])
        );

        return [
            'data'            => array_map([$this, 'formatPageRow'], $rows ?: []),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
        ];
    }


    public function getPageFormContext(): array {

        $scopeType = $this->context->scopeFor('activities');
        $users     = [];

        if ($scopeType !== 'own') {
            $users = $this->db->fetchAll(
                "SELECT id, name FROM users WHERE company_id = ? AND status = 'active' ORDER BY name ASC",
                [$this->context->companyId]
            ) ?: [];
        }

        return [
            'scope' => $scopeType,
            'users' => $users,
        ];
    }


    private function formatPageRow(object $row): array {
        return [
            'id'                 => (int) $row->id,
            'activity_type'      => $row->activity_type,
            'summary'            => $row->summary,
            'entity_type'        => $row->entity_type,
            'entity_id'          => (int) $row->entity_id,
            'status'             => $row->status,
            'priority'           => $row->priority,
            'due_date'           => $row->due_date,
            'due_time'           => $row->due_time,
            'assigned_to'        => $row->assigned_to ? (int) $row->assigned_to : null,
            'assigned_user_name' => $row->assigned_user_name ?? null,
            'created_by_name'    => $row->created_by_name   ?? null,
            'completed_at'       => $row->completed_at,
        ];
    }


    public function delete(int $activityId): array {

        $activity = $this->getActivityOrFail($activityId);
        $featureKey = Service_FeatureKeyResolver::resolve($activity->entity_type);
        if (!$featureKey || !$this->context->canDo($featureKey, 'delete')) {
            throw new Service_Exception('You do not have permission to delete activities', 403);
        }

        $this->db->startTransaction();

        try {

            $activity->delete();

            if( !($activity->getDeletedRows() > 0)  ) {
                throw new Service_Exception("Failed to delete activity");
            }

            $this->db->commit();

            return ['success' => true];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }        
    }


    public function getFormContext(int $activityId = 0): array {

        try {
            
            $users = $this->db->fetchAll("SELECT id, name FROM users WHERE company_id = ? AND status = 'active' ORDER BY name ASC", [$this->context->companyId]);
            $activityDetails = null;
            
            if( $activityId ) {

                $activity = $this->getActivityOrFail($activityId);
                $featureKey = Service_FeatureKeyResolver::resolve($activity->entity_type);
                if (!$featureKey || !$this->context->canDo($featureKey, 'read')) {
                    throw new Service_Exception('You do not have permission to view this activity', 403);
                }

                $attService = new Service_Attachment($this->context);
                $activityDetails = array_merge(['id' => $activityId], $activity->toArray(), ['attachments' => $attService->listFor('activity', $activityId)]);
            }

            return [
                'users' => $users ?: [],
                'activityDetails' => $activityDetails,
            ];    

        } catch(Throwable $e) {
            throw $e;
        }        
    }


    private function validatePayload(array $payload, int $excludeId = 0): void {

        if( empty($payload['activity_type']) || !in_array($payload['activity_type'], $this->validTypes) ) {
            $this->addError(validationErrMsg("invalid", "Activity type"), "activity_type");
        }

        if( empty($payload['summary']) ) {
            $this->addError(validationErrMsg("required", "Summary"), "summary");
        }

        if( empty($payload['due_date']) ) {
            $this->addError(validationErrMsg("required", "Due date"), "due_date");
        }

        // required only on create
        if( !$excludeId ) {
            if( empty($payload['entity_type']) || !in_array($payload['entity_type'], $this->validRelatedTypes) ) {
                $this->addError(validationErrMsg("invalid", "Entity type"), "entity_type");
            }
            if( empty($payload['entity_id']) ) {
                $this->addError(validationErrMsg("required", "Entity record"), "entity_id");
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