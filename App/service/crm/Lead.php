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


    public function logHistory(int $leadId, array $payload): int {

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

        return (int) $history->id;
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

            $leadId = $lead->create();
            if( !$leadId ) {
                throw new Service_Exception("Failed to create lead");
            }

            $logTitle = $payload["log_title"] ?? "";
            $log_meta = $payload["log_meta"] ?? [];

            $stage = new Models_CrmStage($lead->stage_id);
            $this->logHistory($leadId, [
                'log_type' => 'created',
                'title' => $logTitle ?: 'Lead created',
                'meta' => array_merge(['code' => $lead->lead_code, 'stage' => $stage->name], $log_meta),
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

        $this->db->startTransaction();

        try {

            $oldLeadDetails = $lead->toArray();

            $prevStageId = $lead->stage_id;

            $lead->fillFromArray($payload, ["id", "company_id", "lead_code", "created_by", "created_at"]);
            $lead->updated_by = $this->context->userId;

            if( !$lead->update() ) {
                throw new Service_Exception("Failed to update lead");
            }

            $newLeadDetails = $lead->toArray();

            $updatedDetails = [];

            // Individual scalar fields
            $trackFields = [
                'probability' => 'Probability',
                'display_name' => 'Display name',
                'job_title' => 'Job title',
                'email' => 'Email',
                'phone' => 'Phone',
                'website' => 'Website',
                'expected_revenue' => 'Expected revenue',
                'expected_close_date' => 'Expected close date',
                'source' => 'Source',
                'priority' => 'Priority',
                'tags' => 'Tags',
            ];

            foreach ($trackFields as $field => $label) {
                
                $oldVal = $oldLeadDetails[$field] ?? null;
                $newVal = $newLeadDetails[$field] ?? null;

                if ($field === 'tags') {
                    $oldDecoded = !empty($oldVal) ? (json_decode($oldVal, true) ?: []) : [];
                    $newDecoded = !empty($newVal) ? (json_decode($newVal, true) ?: []) : [];
                    if ($oldDecoded !== $newDecoded) {
                        $updatedDetails[] = [
                            'field' => $field,
                            'label' => $label,
                            'old_val' => $oldDecoded,
                            'new_val' => $newDecoded,
                        ];
                    }
                } 
                else if($field === 'expected_revenue' ) {

                    $expRevenueOld = $oldVal ? formatCurrency($oldVal) : "";
                    $expRevenueNew = $newVal ? formatCurrency($newVal) : "";
                    if( $expRevenueOld !== $expRevenueNew ) {
                        $updatedDetails[] = [
                            'field' => $field,
                            'label' => $label,
                            'old_val' => $expRevenueOld,
                            'new_val' => $expRevenueNew,
                        ];
                    }
                                        
                } else {
                    if ((string) $oldVal !== (string) $newVal) {
                        $updatedDetails[] = [
                            'field' => $field,
                            'label' => $label,
                            'old_val' => $oldVal,
                            'new_val' => $newVal,
                        ];
                    }
                }
            }

            // Name — grouped: salutation + first_name + last_name
            $buildFullName = function(array $d): string {
                return trim(implode(' ', array_filter([
                    $d['salutation'] ?? null,
                    $d['first_name'] ?? null,
                    $d['last_name'] ?? null,
                ])));
            };
            $oldName = $buildFullName($oldLeadDetails);
            $newName = $buildFullName($newLeadDetails);
            if ($oldName !== $newName) {
                $updatedDetails[] = [
                    'field' => 'name',
                    'label' => 'Name',
                    'old_val' => $oldName,
                    'new_val' => $newName,
                ];
            }

            // Address — grouped: all address fields as a single formatted string
            $buildFullAddress = function(array $d): string {
                return trim(implode(', ', array_filter([
                    $d['address_line1'] ?? null,
                    $d['address_line2'] ?? null,
                    $d['city'] ?? null,
                    $d['state'] ?? null,
                    $d['postal_code'] ?? null,
                    $d['country'] ?? null,
                ])));
            };
            $oldAddress = $buildFullAddress($oldLeadDetails);
            $newAddress = $buildFullAddress($newLeadDetails);
            if ($oldAddress !== $newAddress) {
                $updatedDetails[] = [
                    'field' => 'address',
                    'label' => 'Address',
                    'old_val' => $oldAddress,
                    'new_val' => $newAddress,
                ];
            }

            if (!empty($updatedDetails)) {
                $this->logHistory($leadId, [
                    'log_type' => 'updated_details',
                    'title' => 'Lead details updated',
                    'meta' => $updatedDetails,
                ]);
            }

            // notes change log
            $oldNotes = $oldLeadDetails['notes'] ?? null;
            $newNotes = $newLeadDetails['notes'] ?? null;
            if ((string) $oldNotes !== (string) $newNotes) {
                $this->logHistory($leadId, [
                    'log_type' => 'updated_notes',
                    'title' => 'Notes updated',
                    'meta' => [
                        'old_val' => $oldNotes,
                        'new_val' => $newNotes,
                    ],
                ]);
            }

            // assigned_to change log
            $oldAssignedTo = isset($oldLeadDetails['assigned_to']) ? (int) $oldLeadDetails['assigned_to'] : null;
            $newAssignedTo = isset($newLeadDetails['assigned_to']) ? (int) $newLeadDetails['assigned_to'] : null;
            if ($oldAssignedTo !== $newAssignedTo) {
                $oldUserName = null;
                $newUserName = null;
                if ($oldAssignedTo) {
                    $oldUser = new Models_User($oldAssignedTo);
                    $oldUserName = $oldUser->isEmpty ? null : $oldUser->name;
                }
                if ($newAssignedTo) {
                    $newUser = new Models_User($newAssignedTo);
                    $newUserName = $newUser->isEmpty ? null : $newUser->name;
                }
                $this->logHistory($leadId, [
                    'log_type' => 'assigned_changed',
                    'title' => 'Assigned to ' . ($newUserName ?? 'None'),
                    'meta' => [
                        'from_user_id' => $oldAssignedTo,
                        'from_user_name' => $oldUserName,
                        'to_user_id' => $newAssignedTo,
                        'to_user_name' => $newUserName,
                    ],
                ]);
            }

            // stage change log
            if ($payload['stage_id'] != $prevStageId) {
                $prevStageName = null;
                $newStageName  = null;
                if ($prevStageId) {
                    $prevStage = new Models_CrmStage($prevStageId);
                    $prevStageName = $prevStage->isEmpty ? null : $prevStage->name;
                }
                if ($payload['stage_id']) {
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

            return ["success" => true, "data" => ['id' => $leadId]];

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
            $prevStatus  = $lead->status;
            $prevStageId = $lead->stage_id;

            // Snapshot previous stage name before any update
            $prevStageName = null;
            if( $prevStageId ) {
                $prevStageObj  = new Models_CrmStage($prevStageId);
                $prevStageName = $prevStageObj->isEmpty ? null : $prevStageObj->name;
            }

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
                $lead->closed_at   = null;
                $lead->lost_reason = null;

                $firstStage = $this->db->fetchOne(
                    "SELECT id FROM crm_stages WHERE company_id = ? AND is_won = 0 AND is_lost = 0 AND status = 'active' ORDER BY sort_order ASC, id ASC LIMIT 1",
                    [$companyId]
                );
                if( $firstStage ) {
                    $lead->stage_id = $firstStage->id;
                }
            }

            if( !$lead->update() ) {
                throw new Service_Exception("Failed to update lead status");
            }

            // Resolve new stage name (may have changed for won/lost)
            $newStageId   = $lead->stage_id;
            $newStageName = null;
            if( $newStageId && $newStageId != $prevStageId ) {
                $newStageObj  = new Models_CrmStage($newStageId);
                $newStageName = $newStageObj->isEmpty ? null : $newStageObj->name;
            } else {
                $newStageId   = $prevStageId;
                $newStageName = $prevStageName;
            }

            // History log
            $logTitleByStatus = [
                'won'    => 'Lead marked as Won',
                'lost'   => 'Lead marked as Lost',
                'active' => 'Lead Reopened',
            ];
            $lostReason = $payload['lost_reason'] ?? "";
            $this->logHistory($leadId, [
                'log_type' => 'status_updated',
                'title'    => $logTitleByStatus[$status] ?? "Status changed to {$status}",
                'meta'     => [
                    'from_status'     => $prevStatus,
                    'to_status'       => $status,
                    'note'            => $lostReason,
                    'from_stage_id'   => $prevStageId,
                    'from_stage_name' => $prevStageName,
                    'to_stage_id'     => $newStageId,
                    'to_stage_name'   => $newStageName,
                ],
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

        // Converted customer name
        $data['customer_name'] = null;
        if( $lead->customer_id ) {
            $customer = new Models_Customer($lead->customer_id);
            if( !$customer->isEmpty ) {
                $data['customer_name'] = $customer->display_name;
            }
        }

        // All pipeline stages for the stage bar
        $data['stages'] = $this->db->fetchAll(
            "SELECT id, name, color, probability, is_won, is_lost, sort_order FROM crm_stages WHERE company_id = ? AND status = 'active' ORDER BY sort_order ASC, id ASC",
            [$companyId]
        );

        return $data;
    }


    public function getConvertContext(int $leadId): array {

        $lead = $this->getLeadOrFail($leadId);
        
        try {

            $addressFields = [
                'attention' => $lead->display_name,
                'address_line1' => $lead->address_line1,
                'address_line2' => $lead->address_line2,
                'city' => $lead->city,
                'state' => $lead->state,
                'postal_code' => $lead->postal_code,
                'country' => $lead->country,
                'phone' => $lead->phone,
            ];

            $prefill = [
                'salutation' => $lead->salutation,
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'company_name' => $lead->company_name,
                'display_name' => $lead->display_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'website' => $lead->website,
                'notes' => $lead->notes,
                'billing_address' => $addressFields,
                'shipping_address' => $addressFields,
                'customer_type' => !empty($lead->company_name) ? 'company' : 'individual',
                'status' => 'active',
                'currency_code' => 'INR',
            ];

            $customerService = new Service_Customer($this->context);
            $customerFormContext = $customerService->getFormContext();

            // Suggest existing customers matching lead's email or phone
            $duplicates = [];
            $seenIds = [];

            if( !empty($lead->email) ) {
                $result = $customerService->checkDuplicate('email', $lead->email);
                if( $result['exists'] && !in_array($result['customer']['id'], $seenIds) ) {
                    $duplicates[] = $result['customer'];
                    $seenIds[] = $result['customer']['id'];
                }
            }

            if( !empty($lead->phone) ) {
                $result = $customerService->checkDuplicate('phone', $lead->phone);
                if( $result['exists'] && !in_array($result['customer']['id'], $seenIds) ) {
                    $duplicates[] = $result['customer'];
                    $seenIds[] = $result['customer']['id'];
                }
            }

            return [
                'prefill' => $prefill,
                'customer_form_context' => [
                    'payment_terms' => $customerFormContext['payment_terms'],
                    'customer_groups' => $customerFormContext['customer_groups'],
                ],
                'duplicate_suggestions' => $duplicates,
            ];

        } catch(Exception $e) {
            throw $e;
        }        
    }


    public function convert(int $leadId, array $payload): array {

        $lead = $this->getLeadOrFail($leadId);

        if( !empty($lead->customer_id) ) {
            throw new Service_Exception("This lead has already been converted to a customer.", 422);
        }

        $action = trim($payload['action'] ?? '');
        if( !in_array($action, ['create', 'link']) ) {
            throw new Service_Exception("Missing or invalid action", 422);
        }

        if( $action === 'link' ) {

            $customerId = !empty($payload['customer_id']) ? (int) $payload['customer_id'] : 0;
            if( !$customerId ) {
                throw new Service_Exception("Invalid request, missing customer", 422);
            }

            $customer = new Models_Customer($customerId);
            if( $customer->isEmpty || $customer->company_id != $this->context->companyId ) {
                throw new Service_Exception("Can not convert, invalid customer", 422);
            }
        }


        $this->db->startTransaction();

        try {

            if( $action === 'create' ) {

                // Create customer from lead data(payload)
                
                $customerService = new Service_Customer($this->context);
                $result = $customerService->create($payload);

                if( !$result['success'] ) {
                    return $result;
                }

                $createdCustomerData = $result["data"];

                $customerId = $createdCustomerData['id'];
                $customerName = $createdCustomerData['display_name'];
                $logType = 'converted_to_customer';

            } else {

                $customerName = $customer->display_name;
                $logType = 'linked_to_customer';
            }

            // Update lead data

            $lead->customer_id = $customerId;
            $lead->converted_at = date("Y-m-d H:i:s");
            $lead->updated_by = $this->context->userId;

            if( !$lead->update() ) {
                $failedMsg = $action === 'create' ? "Failed to convert" : "Failed to link";
                throw new Service_Exception($failedMsg, 500);
            }

            $this->logHistory($leadId, [
                'log_type' => $logType,
                'title' => ($logType === 'converted_to_customer' ? 'Converted to customer' : 'Linked to customer') . ': ' . $customerName,
                'reference_type' => 'customer',
                'reference_id' => $customerId,
                'meta' => ['customer_id' => $customerId, 'customer_name' => $customerName],
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'data' => ['lead_id' => $leadId, 'customer_id' => $customerId, 'customer_name' => $customerName],
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
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

        // Batch-fetch attachments for note entries and attach to each row
        $noteIds = [];
        foreach ($rows as $row) {
            if ($row->log_type === 'note') {
                $noteIds[] = (int) $row->id;
            }
        }

        $attByNote = [];
        if (!empty($noteIds)) {
            $attService = new Service_Attachment(new Service_TenantContext($companyId, $this->context->userId));
            $attByNote  = $attService->groupFor('crm_lead_history', $noteIds);
        }

        foreach ($rows as &$row) {
            $row->attachments = $attByNote[$row->id] ?? [];
        }

        return $rows;
    }


    public function addNote(int $leadId, array $payload): array {

        $this->getLeadOrFail($leadId);

        $note = trim($payload['note'] ?? '');
        $attachments = $payload['attachments'] ?? [];

        if( empty($note) ) {
            $this->addError(validationErrMsg("required", "Note"), "note");
        }

        if( $this->hasErrors() ) {
            return ["success" => false, "errors" => $this->getErrors()];
        }


        $this->db->startTransaction();

        try {

            $historyId = $this->logHistory($leadId, [
                'log_type' => 'note',
                'title' => $note,
            ]);

            // upload attachments
            if (!empty($attachments) && is_array($attachments)) {
                $attService = new Service_Attachment($this->context);
                $attService->saveFromBase64($attachments, 'crm_lead_history', $historyId);
            }

            $this->db->commit();

            return [
                'success' => true,
                'data' => ['lead_id' => $leadId],
            ];


        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function reorder(array $payload): array {

        $leadIds = $payload['leads'] ?? [];

        if (empty($leadIds) || !is_array($leadIds)) {
            return [];
        }

        $this->db->startTransaction();

        try {

            $companyId = $this->context->companyId;        
            foreach ($leadIds as $sortOrder => $leadId) {
                $leadId = (int) $leadId;
                if (!$leadId) continue;
                $this->db->update("crm_leads", ['sort_order' => $sortOrder], "id = $leadId AND company_id = $companyId");
            }

            $this->db->commit();

            return ['success' => true,];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function updateStage(int $leadId, array $payload): array {

        $lead = $this->getLeadOrFail($leadId);

        if( in_array($lead->status, ['won', 'lost']) ) {
            throw new Service_Exception("Cannot change stage of a closed lead. Reopen it first.", 422);
        }

        $stageId = !empty($payload['stage_id']) ? (int) $payload['stage_id'] : null;

        $stage = null;
        if( $stageId ) {
            $stage = new Models_CrmStage($stageId);
            if( $stage->isEmpty || $stage->company_id != $this->context->companyId ) {
                throw new Service_Exception("Invalid stage", 422);
            }
        }

        $isWonStage = ($stage && $stage->is_won == 1);

        $this->db->startTransaction();

        try {

            $prevStageId = $lead->stage_id;
            $prevStatus  = $lead->status;

            $lead->stage_id   = $stageId;
            $lead->updated_by = $this->context->userId;

            if( $isWonStage ) {
                $lead->status     = 'won';
                $lead->closed_at  = date("Y-m-d H:i:s");
                $lead->lost_reason = null;
            }

            if( !$lead->update() ) {
                throw new Service_Exception("Failed to update lead stage", 500);
            }

            if( $stageId != $prevStageId ) {

                $prevStageName = null;
                $newStageName  = null;

                if( $prevStageId ) {
                    $prevStage = new Models_CrmStage($prevStageId);
                    $prevStageName = $prevStage->isEmpty ? null : $prevStage->name;
                }

                if( $stageId ) {
                    $newStageName = $stage->name;
                }

                $this->logHistory($leadId, [
                    'log_type' => 'stage_change',
                    'title'    => 'Stage changed to ' . ($newStageName ?? 'None'),
                    'meta'     => [
                        'from_stage_id'   => $prevStageId,
                        'from_stage_name' => $prevStageName,
                        'to_stage_id'     => $stageId,
                        'to_stage_name'   => $newStageName,
                        'from_status'     => $isWonStage ? $prevStatus : null,
                        'to_status'       => $isWonStage ? 'won'       : null,
                    ],
                ]);
            }

            $this->db->commit();

            return [
                'success' => true,
                'data' => ['lead_id' => $leadId],
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function getPipelineData(array $filters = []): array {

        $companyId = $this->context->companyId;
        $status    = $filters['status'] ?? '';

        // All active stages — same pattern as getFormContext()
        $stages = $this->db->fetchAll(
            "SELECT id, name, color, sort_order FROM crm_stages WHERE company_id = ? AND status = 'active' ORDER BY sort_order ASC, id ASC",
            [$companyId]
        );

        // Build indexed columns from stages (fetchAll returns stdClass objects)
        $columns = [];
        foreach ($stages as $stage) {
            $columns[$stage->id] = [
                'id' => $stage->id,
                'name' => $stage->name,
                'color' => $stage->color ?? '#6c757d',
                'leads' => [],
                'lead_count' => 0,
                'total_revenue' => 0.0,
            ];
        }

        // Unstaged bucket — prepended only if leads without a stage exist
        $unstaged = [
            'id' => null,
            'name' => 'No Stage',
            'color' => '#6c757d',
            'leads' => [],
            'lead_count' => 0,
            'total_revenue' => 0.0,
        ];

        // Build leads SQL — same style as getHistory()
        $sql    = "SELECT l.id, l.lead_code, l.display_name, l.company_name,
                          l.expected_revenue, l.priority, l.status, l.stage_id,
                          l.assigned_to, l.expected_close_date, l.tags,
                          u.name AS assigned_user_name
                   FROM crm_leads AS l
                   LEFT JOIN users AS u ON u.id = l.assigned_to
                   WHERE l.company_id = ?";
        $params = [$companyId];

        if ($status) {
            $sql .= " AND l.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY l.sort_order ASC, l.id ASC";

        $leads = $this->db->fetchAll($sql, $params);

        // Distribute leads into their stage column (cast stdClass → array for output)
        foreach ($leads as $lead) {
            $lead = (array) $lead;
            $sid  = !empty($lead['stage_id']) ? (int) $lead['stage_id'] : null;

            if ($sid && isset($columns[$sid])) {
                $columns[$sid]['leads'][]       = $lead;
                $columns[$sid]['lead_count']++;
                $columns[$sid]['total_revenue'] += (float) ($lead['expected_revenue'] ?? 0);
            } else {
                $unstaged['leads'][]       = $lead;
                $unstaged['lead_count']++;
                $unstaged['total_revenue'] += (float) ($lead['expected_revenue'] ?? 0);
            }
        }

        $result = array_values($columns);

        if ($unstaged['lead_count'] > 0) {
            array_unshift($result, $unstaged);
        }

        return ['stages' => $result];
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