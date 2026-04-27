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


    private function handleList(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;

        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = [
            "id" => "l.id",
            "lead_code" => "l.lead_code",
            "display_name" => "l.display_name",
            "company_name" => "l.company_name",
            "email" => "l.email",
            "phone" => "l.phone",
            "priority" => "l.priority",
            "status" => "l.status",
            "expected_revenue" => "l.expected_revenue",
            "expected_close_date" => "l.expected_close_date",
            "stage_id" => "l.stage_id",
            "stage_name" => "s.name",
            "stage_color" => "s.color",
            "assigned_to" => "l.assigned_to",
            "assigned_user_name" => "u.name",
            "created_at" => "l.created_at",
        ];

        $status = $request->getInput("status", "String", "");
        $stageId = $request->getInput("stage_id", "Int", 0);
        $assignedTo = $request->getInput("assigned_to", "Int", 0);

        $dataFetch->table("crm_leads AS l")
            ->joins("LEFT JOIN crm_stages AS s ON s.id = l.stage_id LEFT JOIN users AS u ON u.id = l.assigned_to")
            ->columns($columns)
            ->where("l.company_id = ?", [$companyId]);

        if( $status ) {
            $dataFetch->where("l.status = ?", [$status]);
        }
        if( $stageId ) {
            $dataFetch->where("l.stage_id = ?", [$stageId]);
        }
        if( $assignedTo ) {
            $dataFetch->where("l.assigned_to = ?", [$assignedTo]);
        }

        $results = $dataFetch->fetch();

        return response($results)->sendJson();
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
