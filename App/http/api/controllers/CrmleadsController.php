<?php
class Api_CrmLeadsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function serviceCrmLead(): Service_Crm_Lead {
        return new Service_Crm_Lead(tenantContext());
    }


    // GET/POST /api/crm/leads
    public function indexAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            return $this->handleList($request);
        }
        else if( $request->isMethod("post") ) {
            return $this->handleSave($request);
        }        
    }


    // GET/POST /api/crm/leads/:id
    public function entityAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            return $this->handleShow($request);
        }
        else if( $request->isMethod("post") ) {
            return $this->handleSave($request);
        }        
    }


    // POST /api/crm/leads/:id/status
    public function statusAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);
        $inputs = $request->getInputs();

        $service = $this->serviceCrmLead();
        $response = $service->updateStatus($id, $inputs);

        if( $response["success"] ) {
            return response($response["data"], "Lead status updated successfully", 200)->sendJson();
        } else {
            return response([], "Failed to update lead status", 422)->errors($response["errors"])->sendJson();
        }
    }


    // GET /api/crm/leads/pipeline
    public function pipelineAction(TinyPHP_Request $request) {
        
        $filters = ['status' => $request->getInput("status", "String", "active")];

        $service = $this->serviceCrmLead();
        $data = $service->getPipelineData($filters);

        return response($data)->sendJson();
    }


    // GET /api/crm/leads/form-context
    public function formContextAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);
        
        $service = $this->serviceCrmLead();
        $data = $service->getFormContext($id);

        return response($data)->sendJson();
    }


    // POST /api/crm/leads/:id/note
    public function noteAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);
        $inputs = $request->getInputs();

        $service = $this->serviceCrmLead();
        $data = $service->addNote($id, $inputs);

        return response($data, "Note added", 200)->sendJson();
    }


    // POST /api/crm/leads/reorder
    public function reorderAction(TinyPHP_Request $request) {
        
        $inputs = $request->getInputs();

        $service = $this->serviceCrmLead();
        $service->reorder($inputs);

        return response([], "Reordered", 200)->sendJson();
    }


    // POST /api/crm/leads/:id/stage
    public function stageAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);
        $inputs = $request->getInputs();

        $service = $this->serviceCrmLead();
        $data = $service->updateStage($id, $inputs);

        return response($data, "Stage updated", 200)->sendJson();
    }


    // GET /api/crm/leads/:id/convert-context
    public function convertContextAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);
        
        $service = $this->serviceCrmLead();
        $data = $service->getConvertContext($id);

        return response($data)->sendJson();
    }


    // POST /api/crm/leads/:id/convert
    public function convertAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);
        $inputs = $request->getInputs();

        $service = $this->serviceCrmLead();
        $result = $service->convert($id, $inputs);

        if( $result["success"] ) {
            return response($result["data"], "Lead converted successfully", 200)->sendJson();
        } else {
            return response([], "Failed to convert lead", 422)->errors($result["errors"])->sendJson();
        }
    }


    // GET /api/crm/leads/:id/history
    public function historyAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        
        $service = $this->serviceCrmLead();
        $data = $service->getHistory($id);

        return response($data)->sendJson();
    }


    // GET /api/crm/leads/export
    public function exportAction(TinyPHP_Request $request) {

        $format = $request->getInput("format", "String", "csv");
        $rows   = $this->buildLeadsDataFetch($request)->fetch();

        $columns = [
            ['label' => 'Lead #', 'key' => 'lead_code'],
            ['label' => 'Title', 'key' => 'title'],
            ['label' => 'Contact Name', 'key' => 'display_name'],
            ['label' => 'Email', 'key' => 'email'],
            ['label' => 'Phone', 'key' => 'phone'],
            ['label' => 'Company', 'key' => 'company_name'],
            ['label' => 'Stage', 'key' => 'stage_name'],
            ['label' => 'Priority', 'key' => 'priority', 'formatter' => fn($v) => ucfirst($v ?? '')],
            ['label' => 'Lead Value', 'key' => 'expected_revenue', 'formatter' => fn($v) => $v !== null && $v !== '' ? number_format((float)$v, 2) : ''],
            ['label' => 'Source', 'key' => 'source', 'formatter' => fn($v) => $this->formatSource($v)],
            ['label' => 'Exp. Close Date', 'key' => 'expected_close_date','formatter' => fn($v) => formatMySqlDate($v, 'm/d/Y', '')],
            ['label' => 'Assigned To', 'key' => 'assigned_user_name'],
            ['label' => 'Status', 'key' => 'status', 'formatter' => fn($v) => ucfirst($v ?? '')],
            ['label' => 'Created At', 'key' => 'created_at', 'formatter' => fn($v) => formatMySqlDate($v, 'm/d/Y H:s', '')],
        ];

        Service_Export::stream($rows, $format, 'leads - '.date("Y-m-d"), $columns);
    }


    private function formatSource(?string $source): string
    {
        $sources = config('constants.crm.lead_sources');
        foreach ($sources as $s) {
            if ($s['key'] === $source) return $s['label'];
        }
        return $source ?? '';
    }


    private function handleList(TinyPHP_Request $request) {

        $results = $this->buildLeadsDataFetch($request)->fetch();

        return response($results)->sendJson();
    }


    private function buildLeadsDataFetch(TinyPHP_Request $request): TinyPHP_DataFetch {

        $companyId = tenantContext()->companyId;

        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = [
            "id" => "l.id",
            "lead_code" => "l.lead_code",
            "title" => "l.title",
            "display_name" => "l.display_name",
            "company_name" => "l.company_name",
            "email" => "l.email",
            "phone" => "l.phone",
            "source" => "l.source",
            "priority" => "l.priority",
            "status" => "l.status",
            "expected_revenue" => "l.expected_revenue",
            "expected_close_date"=> "l.expected_close_date",
            "stage_id" => "l.stage_id",
            "stage_name" => "s.name",
            "stage_color" => "s.color",
            "assigned_to" => "l.assigned_to",
            "assigned_user_name" => "u.name",
            "created_by_name" => "cb.name",
            "created_at" => "l.created_at",
        ];

        $stageIds = $request->getInput("stage_id", "array",  []);
        $assignedTo = $request->getInput("assigned_to", "array",  []);
        $priorities = $request->getInput("priority", "array",  []);
        $sources = $request->getInput("source", "array",  []);
        $closeDateFrom = $request->getInput("close_date_from","String", "");
        $closeDateTo = $request->getInput("close_date_to", "String", "");
        $createdFrom = $request->getInput("created_from", "String", "");
        $createdTo = $request->getInput("created_to", "String", "");
        $leadValueMin = $request->getInput("lead_value_min", "String", "");
        $leadValueMax = $request->getInput("lead_value_max", "String", "");

        $dataFetch->table("crm_leads AS l")
            ->joins("LEFT JOIN crm_stages AS s ON s.id = l.stage_id LEFT JOIN users AS u ON u.id = l.assigned_to LEFT JOIN users AS cb ON cb.id = l.created_by")
            ->columns($columns)
            ->virtualColumns(['display_name' => ['display_name', 'email', 'phone', 'company_name']])
            ->ignoreSearch(['stage_name', 'stage_color', 'status', 'expected_revenue', 'expected_close_date', 'source', 'assigned_to', 'created_by_name', 'created_at'])
            ->where("l.company_id = ?", [$companyId]);

        $scope = (new Service_Scope(tenantContext()))->getCondition('crm_leads', ['l.created_by', 'l.assigned_to']);
        if ($scope['sql']) {
            $dataFetch->where($scope['sql'], $scope['bindings']);
        }

        if( $stageIds ) {
            $stageIds = array_values(array_filter(array_map('intval', $stageIds)));
            if( $stageIds ) {
                $placeholders = implode(',', array_fill(0, count($stageIds), '?'));
                $dataFetch->where("l.stage_id IN ({$placeholders})", $stageIds);
            }
        }

        if( $assignedTo ) {
            $hasUnassigned = in_array('unassigned', $assignedTo);
            $userIds = array_values(array_filter(array_map(
                fn($v) => $v !== 'unassigned' ? (int)$v : null,
                $assignedTo
            )));
            if( $hasUnassigned && $userIds ) {
                $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                $dataFetch->where("(l.assigned_to IS NULL OR l.assigned_to IN ({$placeholders}))", $userIds);
            } elseif( $hasUnassigned ) {
                $dataFetch->where("l.assigned_to IS NULL");
            } elseif( $userIds ) {
                $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                $dataFetch->where("l.assigned_to IN ({$placeholders})", $userIds);
            }
        }

        if( $priorities ) {
            $placeholders = implode(',', array_fill(0, count($priorities), '?'));
            $dataFetch->where("l.priority IN ({$placeholders})", $priorities);
        }
        if( $sources ) {
            $placeholders = implode(',', array_fill(0, count($sources), '?'));
            $dataFetch->where("l.source IN ({$placeholders})", $sources);
        }
        if( $closeDateFrom ) { $dataFetch->where("l.expected_close_date >= ?",  [$closeDateFrom]); }
        if( $closeDateTo )   { $dataFetch->where("l.expected_close_date <= ?",  [$closeDateTo]); }
        if( $createdFrom )   { $dataFetch->where("DATE(l.created_at) >= ?",     [$createdFrom]); }
        if( $createdTo )     { $dataFetch->where("DATE(l.created_at) <= ?",     [$createdTo]); }
        if( $leadValueMin !== '' ) { $dataFetch->where("l.expected_revenue >= ?", [(float)$leadValueMin]); }
        if( $leadValueMax !== '' ) { $dataFetch->where("l.expected_revenue <= ?", [(float)$leadValueMax]); }

        return $dataFetch;
    }


    private function handleSave(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $action = $id ? "update" : "create";

        $inputs = $request->getInputs();

        $service = $this->serviceCrmLead();
        if( $action === "update" ) {
            $response = $service->update($id, $inputs);
        } else {
            $response = $service->create($inputs);
        }

        if( $response["success"] ) {
            
            $message = $action === "update" ? "Lead updated successfully" : "Lead created successfully";
            $code = $action === "update" ? 200 : 201;
            
            return response($response["data"], $message, $code)->sendJson();
        } else {
            
            $message = $action === "update" ? "Failed to update lead" : "Failed to create lead";
            
            return response([], $message, 422)->errors($response["errors"])->sendJson();
        }
    }


    private function handleShow(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
            
        $service = $this->serviceCrmLead();
        $data = $service->show($id);

        return response($data)->sendJson();
    }
}
