<?php
class Service_Crm_Lead extends Service_Base {

    private function getLeadOrFail(int $leadId): Models_CrmLead {

        $lead = new Models_CrmLead($leadId);
        if( $lead->isEmpty ) {
            throw new Service_Exception("The requested lead was not found", 404);
        }
        if( $lead->company_id != $this->context->companyId ) {
            throw new Service_Exception("You do not have permission to access this lead", 403);
        }
        return $lead;
    }


    private function normalizePayload(array &$payload): void {

        $payload['salutation'] = trim($payload['salutation'] ?? '') ?: null;
        $payload['first_name'] = trim($payload['first_name'] ?? '');
        $payload['last_name'] = trim($payload['last_name'] ?? '') ?: null;
        $payload['company_name'] = trim($payload['company_name'] ?? '') ?: null;
        $payload['display_name'] = trim($payload['display_name'] ?? '');
        $payload['job_title'] = trim($payload['job_title'] ?? '') ?: null;
        $payload['email'] = trim($payload['email'] ?? '') ?: null;
        $payload['phone'] = trim($payload['phone'] ?? '') ?: null;
        $payload['website'] = trim($payload['website'] ?? '') ?: null;
        $payload['source'] = trim($payload['source'] ?? '') ?: null;
        $payload['priority'] = in_array($payload['priority'] ?? '', ['low','medium','high']) ? $payload['priority'] : 'medium';
        $payload['stage_id'] = !empty($payload['stage_id']) ? (int) $payload['stage_id'] : null;
        $payload['assigned_to'] = !empty($payload['assigned_to']) ? (int) $payload['assigned_to'] : null;
        $payload['expected_revenue'] = is_numeric($payload['expected_revenue']    ?? '') ? (float) $payload['expected_revenue'] : null;
        $payload['expected_close_date'] = trim($payload['expected_close_date'] ?? '') ?: null;
        $payload['notes'] = trim($payload['notes']          ?? '') ?: null;
        $payload['address_line1'] = trim($payload['address_line1'] ?? '') ?: null;
        $payload['address_line2'] = trim($payload['address_line2'] ?? '') ?: null;
        $payload['city'] = trim($payload['city'] ?? '') ?: null;
        $payload['state'] = trim($payload['state'] ?? '') ?: null;
        $payload['postal_code'] = trim($payload['postal_code']    ?? '') ?: null;
        $payload['country'] = trim($payload['country'] ?? '') ?: 'IN';
        $payload['tags'] = !empty($payload['tags']) ? (is_array($payload['tags']) ? json_encode($payload['tags']) : $payload['tags']) : null;

        // Auto-build display_name if empty
        if( empty($payload['display_name']) ) {
            $parts = array_filter([$payload['salutation'], $payload['first_name'], $payload['last_name']]);
            $payload['display_name'] = implode(' ', $parts) ?: $payload['first_name'];
        }
    }


    private function validatePayload(array $payload): void {

        if( empty($payload['first_name']) ) {
            $this->addError(validationErrMsg("required", "First name"), "first_name");
        }

        if( empty($payload['display_name']) ) {
            $this->addError(validationErrMsg("required", "Display name"), "display_name");
        }

        if( !empty($payload['email']) && !isValidEmail($payload['email']) ) {
            $this->addError(validationErrMsg("invalid", "Email"), "email");
        }

        if( !empty($payload['expected_close_date']) && !strtotime($payload['expected_close_date']) ) {
            $this->addError(validationErrMsg("invalid", "Expected close date"), "expected_close_date");
        }

        if( !empty($payload['expected_revenue']) && !is_numeric($payload['expected_revenue']) ) {
            $this->addError(validationErrMsg("invalid", "Expected revenue"), "expected_revenue");
        }

        // Validate stage belongs to this company if provided
        if( !empty($payload['stage_id']) ) {
            $stage = new Models_CrmStage($payload['stage_id']);
            if( $stage->isEmpty || $stage->company_id != $this->context->companyId ) {
                $this->addError(validationErrMsg("invalid", "Stage"), "stage_id");
            }
        }

        // Validate assigned_to user belongs to this company
        if( !empty($payload['assigned_to']) ) {
            $user = new Models_User($payload['assigned_to']);
            if( $user->isEmpty || $user->company_id != $this->context->companyId ) {
                $this->addError(validationErrMsg("invalid", "Assigned to"), "assigned_to");
            }
        }
    }


    public function logHistory(int $leadId, array $payload): void {

        $meta = empty($payload['meta']) ? null : json_encode($payload['meta'], JSON_UNESCAPED_UNICODE);

        $history = new Models_CrmLeadHistory();
        $history->company_id = $this->context->companyId;
        $history->lead_id = $leadId;
        $history->log_type = $payload['log_type'];
        $history->title = $payload['title'];
        $history->reference_type = $payload['reference_type'] ?? null;
        $history->reference_id = $payload['reference_id']   ?? null;
        $history->meta = $meta;
        $history->created_by = $this->context->userId;

        if (!$history->create()) {
            throw new Service_Exception("Failed to log lead history");
        }
    }


    public function create(array $payload): array {

        $this->normalizePayload($payload);
        $this->validatePayload($payload);

        if( $this->hasErrors() ) {
            return ["success" => false, "errors" => $this->getErrors()];
        }

        $companyId = $this->context->companyId;
        $userId = $this->context->userId;

        $this->db->startTransaction();

        try {

            $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));

            $lead = new Models_CrmLead();
            $lead->fillFromArray($payload);
            $lead->company_id = $companyId;
            $lead->lead_code = $seqService->nextCommit("crm_leads");
            $lead->status = 'active';
            $lead->created_by = $userId;
            $lead->updated_by = $userId;

            /*
            $lead->stage_id = $payload['stage_id'];
            $lead->probability = $payload['probability'] ?? 10;
            $lead->salutation = $payload['salutation'];
            $lead->first_name = $payload['first_name'];
            $lead->last_name = $payload['last_name'];
            $lead->company_name = $payload['company_name'];
            $lead->display_name = $payload['display_name'];
            $lead->job_title = $payload['job_title'];
            $lead->email = $payload['email'];
            $lead->phone = $payload['phone'];
            $lead->website = $payload['website'];
            $lead->address_line1 = $payload['address_line1'];
            $lead->address_line2 = $payload['address_line2'];
            $lead->city = $payload['city'];
            $lead->state = $payload['state'];
            $lead->postal_code = $payload['postal_code'];
            $lead->country = $payload['country'];
            $lead->expected_revenue = $payload['expected_revenue'];
            $lead->expected_close_date  = $payload['expected_close_date'];
            $lead->source = $payload['source'];
            $lead->priority = $payload['priority'];
            $lead->tags = $payload['tags'];
            $lead->assigned_to = $payload['assigned_to'];
            $lead->notes = $payload['notes'];
            */

            

            $leadId = $lead->create();
            if( !$leadId ) {
                throw new Service_Exception("Failed to create lead");
            }

            $stage = new Models_CrmStage($lead->stage_id);
            $this->logHistory($leadId, [
                'log_type' => 'created',
                'title'    => 'Lead created',
                'meta' => [
                    'code' => $lead->lead_code,
                    'stage' => $stage->name,
                ],
            ]);

            $this->db->commit();

            return ["success" => true, "data" => ["id" => $leadId, "lead_code" => $lead->lead_code]];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function update(int $leadId, array $payload): array {

        $lead = $this->getLeadOrFail($leadId);

        if( in_array($lead->status, ['won', 'lost']) ) {
            throw new Service_Exception("Cannot edit a closed lead. Reopen it first.", 422);
        }

        $this->normalizePayload($payload);
        $this->validatePayload($payload);

        if( $this->hasErrors() ) {
            return ["success" => false, "errors" => $this->getErrors()];
        }

        $companyId = $this->context->companyId;
        $userId    = $this->context->userId;

        $this->db->startTransaction();

        try {

            $prevStageId = $lead->stage_id;

            $lead->stage_id = $payload['stage_id'];
            $lead->probability = $payload['probability'] ?? $lead->probability;
            $lead->salutation = $payload['salutation'];
            $lead->first_name = $payload['first_name'];
            $lead->last_name = $payload['last_name'];
            $lead->company_name = $payload['company_name'];
            $lead->display_name = $payload['display_name'];
            $lead->job_title = $payload['job_title'];
            $lead->email = $payload['email'];
            $lead->phone = $payload['phone'];
            $lead->website = $payload['website'];
            $lead->address_line1 = $payload['address_line1'];
            $lead->address_line2 = $payload['address_line2'];
            $lead->city = $payload['city'];
            $lead->state = $payload['state'];
            $lead->postal_code = $payload['postal_code'];
            $lead->country = $payload['country'];
            $lead->expected_revenue = $payload['expected_revenue'];
            $lead->expected_close_date  = $payload['expected_close_date'];
            $lead->source = $payload['source'];
            $lead->priority = $payload['priority'];
            $lead->tags = $payload['tags'];
            $lead->assigned_to = $payload['assigned_to'];
            $lead->notes = $payload['notes'];
            $lead->updated_by = $userId;

            if( !$lead->update() ) {
                throw new Service_Exception("Failed to update lead");
            }

            // Log stage change
            if( $payload['stage_id'] != $prevStageId ) {
                $prevStageName = null;
                $newStageName = null;
                if( $prevStageId ) {
                    $prevStage = new Models_CrmStage($prevStageId);
                    $prevStageName = $prevStage->isEmpty ? null : $prevStage->name;
                }
                if( $payload['stage_id'] ) {
                    $newStage = new Models_CrmStage($payload['stage_id']);
                    $newStageName = $newStage->isEmpty ? null : $newStage->name;
                }
                $this->logHistory($leadId, [
                    'log_type' => 'stage_change',
                    'title' => 'Stage changed to ' . ($newStageName ?? 'None'),
                    'meta' => [
                        'from_stage_id' => $prevStageId,
                        'from_stage_name' => $prevStageName,
                        'to_stage_id' => $payload['stage_id'],
                        'to_stage_name' => $newStageName,
                    ],
                ]);
            }

            $this->db->commit();

            return ["success" => true, "data" => []];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function updateStatus(int $leadId, array $payload): array {

        $lead = $this->getLeadOrFail($leadId);

        $status = trim($payload['status'] ?? '');

        if( !in_array($status, ['won', 'lost', 'active']) ) {
            throw new Service_Exception("Invalid status. Allowed: won, lost, active", 422);
        }

        $companyId = $this->context->companyId;
        $userId = $this->context->userId;

        $this->db->startTransaction();

        try {

            $now = date("Y-m-d H:i:s");
            $prevStatus = $lead->status;

            $lead->status = $status;
            $lead->updated_by = $userId;

            if( $status === 'won' ) {
                $lead->closed_at = $now;
                $lead->lost_reason = null;

                // If a Won stage exists, move lead into it automatically
                $wonStage = $this->db->fetchOne(
                    "SELECT id FROM crm_stages WHERE company_id = ? AND is_won = 1 AND status = 'active' LIMIT 1",
                    [$companyId]
                );
                if( $wonStage ) {
                    $lead->stage_id = $wonStage->id;
                }
            }
            else if( $status === 'lost' ) {
                $lead->closed_at = $now;
                $lead->lost_reason = trim($payload['lost_reason'] ?? '') ?: null;

                // If a Lost stage exists, move lead into it automatically
                $lostStage = $this->db->fetchOne(
                    "SELECT id FROM crm_stages WHERE company_id = ? AND is_lost = 1 AND status = 'active' LIMIT 1",
                    [$companyId]
                );
                if( $lostStage ) {
                    $lead->stage_id = $lostStage->id;
                }
            }
            else if( $status === 'active' ) {
                // Reopening
                $lead->closed_at = null;
                $lead->lost_reason = null;
            }

            if( !$lead->update() ) {
                throw new Service_Exception("Failed to update lead status");
            }

            // History log
            $logTitle = match($status) {
                'won' => 'Lead marked as Won',
                'lost' => 'Lead marked as Lost' . (!empty($payload['lost_reason']) ? ': ' . $payload['lost_reason'] : ''),
                'active' => 'Lead reopened',
                default => "Status changed to {$status}",
            };

            $this->logHistory($leadId, [
                'log_type' => 'system',
                'title' => $logTitle,
                'meta' => ['from_status' => $prevStatus, 'to_status' => $status],
            ]);

            $this->db->commit();

            return ["success" => true, "data" => []];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    

    public function show(int $leadId): array {

        $lead = $this->getLeadOrFail($leadId);
        $companyId = $this->context->companyId;

        $data = array_merge(['id' => $leadId], $lead->toArray());

        // Decode tags JSON
        if( !empty($data['tags']) ) {
            $data['tags'] = json_decode($data['tags'], true) ?: [];
        }

        // Stage info
        $data['stage'] = null;
        if( $lead->stage_id ) {
            $stage = new Models_CrmStage($lead->stage_id);
            if( !$stage->isEmpty ) {
                $data['stage'] = ['id' => $stage->id, 'name' => $stage->name, 'color' => $stage->color, 'is_won' => $stage->is_won, 'is_lost' => $stage->is_lost];
            }
        }

        // Assigned user
        $data['assigned_user'] = null;
        if( $lead->assigned_to ) {
            $user = new Models_User($lead->assigned_to);
            if( !$user->isEmpty ) {
                $data['assigned_user'] = ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
            }
        }

        // All pipeline stages for the stage bar
        $data['stages'] = $this->db->fetchAll(
            "SELECT id, name, color, probability, is_won, is_lost, sort_order FROM crm_stages WHERE company_id = ? AND status = 'active' ORDER BY sort_order ASC, id ASC",
            [$companyId]
        );

        return $data;
    }


    public function getHistory(int $leadId): array {

        $this->getLeadOrFail($leadId);
        $companyId = $this->context->companyId;

        $sql = "SELECT h.*, u.name AS created_by_name
                FROM crm_lead_history h
                LEFT JOIN users u ON u.id = h.created_by
                WHERE h.lead_id = ? AND h.company_id = ?
                ORDER BY h.created_at DESC";

        $rows = $this->db->fetchAll($sql, [$leadId, $companyId]);

        foreach ($rows as &$row) {
            if( !empty($row->meta) ) {
                $row->meta = json_decode($row->meta, true);
            }
        }

        return $rows;
    }


    public function getFormContext(int $leadId = 0): array {

        $companyId = $this->context->companyId;

        $leadDetails = [];
        if( $leadId > 0 ) {
            $leadDetails = $this->show($leadId);
        }

        // Active pipeline stages
        $stages = $this->db->fetchAll(
            "SELECT id, name, color, probability, is_won, is_lost FROM crm_stages WHERE company_id = ? AND status = 'active' ORDER BY sort_order ASC, id ASC",
            [$companyId]
        );

        // Company users for assigned_to dropdown
        $users = $this->db->fetchAll(
            "SELECT id, name, email FROM users WHERE company_id = ? AND status = 'active' ORDER BY name ASC",
            [$companyId]
        );

        return [
            'leadDetails' => $leadDetails,
            'stages' => $stages,
            'users' => $users,
        ];
    }


}
?>