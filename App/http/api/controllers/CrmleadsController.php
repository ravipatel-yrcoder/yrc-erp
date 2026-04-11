<?php
class Api_CrmLeadsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }


    // GET/POST /api/crm/leads
    public function indexAction(TinyPHP_Request $request) {

        if( $request->isMethod("get") ) {
            $this->handleList($request);
        }
        else if( $request->isMethod("post") ) {
            $this->handleSave($request);
        }

        response([], "Method not allowed", 405)->sendJson();
    }


    // GET/POST /api/crm/leads/:id
    public function entityAction(TinyPHP_Request $request) {

        if( $request->isMethod("get") ) {
            $this->handleShow($request);
        }
        else if( $request->isMethod("post") ) {
            $this->handleSave($request);
        }

        response([], "Method not allowed", 405)->sendJson();
    }


    // POST /api/crm/leads/:id/status
    public function statusAction(TinyPHP_Request $request) {

        if( !$request->isMethod("post") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        try {

            $id = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;
            $inputs = $request->getInputs();

            $leadService = new Service_Crm_Lead(new Service_TenantContext($companyId, $userId));
            $response = $leadService->updateStatus($id, $inputs);

            if( $response["success"] ) {
                response($response["data"], "Lead status updated successfully", 200)->sendJson();
            } else {
                response([], "Failed to update lead status", 422)->errors($response["errors"])->sendJson();
            }

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->sendJson();
        } catch (Exception $e) {
            response([], "Failed to update lead status", 500)->sendJson();
        }
    }


    // GET /api/crm/leads/pipeline
    public function pipelineAction(TinyPHP_Request $request) {

        if( !$request->isMethod("get") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        try {

            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;
            $filters = [
                'status' => $request->getInput("status", "String", "active"),
            ];

            $leadService = new Service_Crm_Lead(new Service_TenantContext($companyId, $userId));
            $data = $leadService->getPipelineData($filters);

            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->sendJson();
        } catch (Exception $e) {
            response([], "Failed to load pipeline", 500)->sendJson();
        }
    }


    // GET /api/crm/leads/form-context
    public function formContextAction(TinyPHP_Request $request) {

        if( !$request->isMethod("get") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        try {

            $id = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;

            $leadService = new Service_Crm_Lead(new Service_TenantContext($companyId, $userId));
            $data = $leadService->getFormContext($id);

            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->sendJson();
        } catch (Exception $e) {
            response([], "Failed to load form context", 500)->sendJson();
        }
    }


    // POST /api/crm/leads/:id/note
    public function noteAction(TinyPHP_Request $request) {

        if( !$request->isMethod("post") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        try {

            $id = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;
            $inputs = $request->getInputs();

            $leadService = new Service_Crm_Lead(new Service_TenantContext($companyId, $userId));
            $data = $leadService->addNote($id, $inputs);

            response($data, "Note added", 200)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->sendJson();
        } catch (Exception $e) {
            response([], "Failed to add note", 500)->sendJson();
        }
    }


    // POST /api/crm/leads/reorder
    public function reorderAction(TinyPHP_Request $request) {

        if( !$request->isMethod("post") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        try {

            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;
            $inputs = $request->getInputs();

            $leadService = new Service_Crm_Lead(new Service_TenantContext($companyId, $userId));
            $leadService->reorder($inputs);

            response([], "Reordered", 200)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->sendJson();
        } catch (Exception $e) {
            response([], "Failed to reorder leads", 500)->sendJson();
        }
    }


    // POST /api/crm/leads/:id/stage
    public function stageAction(TinyPHP_Request $request) {

        if( !$request->isMethod("post") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        try {

            $id = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;
            $inputs = $request->getInputs();

            $leadService = new Service_Crm_Lead(new Service_TenantContext($companyId, $userId));
            $data = $leadService->updateStage($id, $inputs);

            response($data, "Stage updated", 200)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->sendJson();
        } catch (Exception $e) {
            response([], "Failed to update stage", 500)->sendJson();
        }
    }


    // GET /api/crm/leads/:id/convert-context
    public function convertContextAction(TinyPHP_Request $request) {

        if( !$request->isMethod("get") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        try {

            $id = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;

            $leadService = new Service_Crm_Lead(new Service_TenantContext($companyId, $userId));
            $data = $leadService->getConvertContext($id);

            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->sendJson();
        } catch (Exception $e) {
            response([], "Failed to load convert context", 500)->sendJson();
        }
    }


    // POST /api/crm/leads/:id/convert
    public function convertAction(TinyPHP_Request $request) {

        if( !$request->isMethod("post") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        try {

            $id = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;
            $inputs = $request->getInputs();

            $leadService = new Service_Crm_Lead(new Service_TenantContext($companyId, $userId));
            $result = $leadService->convert($id, $inputs);

            if( $result["success"] ) {
                response($result["data"], "Lead converted successfully", 200)->sendJson();
            } else {
                response([], "Failed to convert lead", 422)->errors($result["errors"])->sendJson();
            }

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to convert lead", 500)->sendJson();
        }
    }


    // GET /api/crm/leads/:id/history
    public function historyAction(TinyPHP_Request $request) {

        if( !$request->isMethod("get") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        try {

            $id = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;

            $leadService = new Service_Crm_Lead(new Service_TenantContext($companyId, $userId));
            $data = $leadService->getHistory($id);

            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->sendJson();
        } catch (Exception $e) {
            response([], "Failed to fetch lead history", 500)->sendJson();
        }
    }


    private function handleList(TinyPHP_Request $request) {

        $companyId = auth()->getCompanyId();

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

        response($results)->sendJson();
    }


    private function handleSave(TinyPHP_Request $request) {

        try {

            $id = $request->getInput("id", "Int", 0);
            $action = $id ? "update" : "create";

            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;
            $inputs = $request->getInputs();

            $leadService = new Service_Crm_Lead(new Service_TenantContext($companyId, $userId));

            if( $action === "update" ) {
                $response = $leadService->update($id, $inputs);
            } else {
                $response = $leadService->create($inputs);
            }

            if( $response["success"] ) {
                $message = $action === "update" ? "Lead updated successfully" : "Lead created successfully";
                $code = $action === "update" ? 200 : 201;
                response($response["data"], $message, $code)->sendJson();
            } else {
                $message = $action === "update" ? "Failed to update lead" : "Failed to create lead";
                response([], $message, 422)->errors($response["errors"])->sendJson();
            }

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to save lead", 500)->errors([$e->getMessage()])->sendJson();
        }
    }


    private function handleShow(TinyPHP_Request $request) {

        try {

            $id = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;

            $leadService = new Service_Crm_Lead(new Service_TenantContext($companyId, $userId));
            $data = $leadService->show($id);

            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->sendJson();
        } catch (Exception $e) {
            response([], "Failed to fetch lead", 500)->sendJson();
        }
    }
}
