<?php
class Api_CrmStagesController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    public function indexAction(TinyPHP_Request $request) {

        if( $request->isMethod("get") ) {
            $this->handleGet($request);
        }
        else if( $request->isMethod("post") ) {
            $this->handlePost($request);
        }
        else if( $request->isMethod("delete") ) {
            $this->handleDelete($request);
        }

        response([], "Method not allowed", 405)->sendJson();
    }


    private function handleGet(TinyPHP_Request $request) {

        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;

        $stageService = new Service_Crm_Stage(new Service_TenantContext($companyId, $userId));
        $stages = $stageService->list();

        response($stages)->sendJson();
    }


    private function handlePost(TinyPHP_Request $request) {

        try {

            $id = $request->getInput("id", "Int", 0);

            $action = "create";
            if( $id ) {
                $action = "update";
            }

            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;
            $inputs = $request->getInputs();

            $stageService = new Service_Crm_Stage(new Service_TenantContext($companyId, $userId));

            if( $action === "update" ) {
                $response = $stageService->update($id, $inputs);
            } else {
                $response = $stageService->create($inputs);
            }

            if( $response["success"] ) {
                $responseMessage = $action === "update" ? "Stage updated successfully" : "Stage created successfully";
                $responseCode = $action === "update" ? 200 : 201;
                response($response["data"], $responseMessage, $responseCode)->sendJson();
            } else {
                $responseMessage = $action === "update" ? "Failed to update stage" : "Failed to create stage";
                response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
            }

        } catch(Service_Exception $e) {
            $error = $e->getMessage();
            $statusCode = $e->getStatusCode() ?: 500;
            response([], "Failed to save stage", $statusCode)->errors([$error])->sendJson();
        } catch(Exception $e) {
            $error = $e->getMessage();
            response([], "Failed to save stage", 500)->errors([$error])->sendJson();
        }
    }


    private function handleDelete(TinyPHP_Request $request) {

        try {

            $id = $request->getInput("id", "Int", 0);

            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;

            $stageService = new Service_Crm_Stage(new Service_TenantContext($companyId, $userId));
            $stageService->delete($id);

            response([], "Stage deleted successfully", 200)->sendJson();

        } catch(Service_Exception $e) {
            $error = $e->getMessage();
            $statusCode = $e->getStatusCode() ?: 500;
            response([], $error, $statusCode)->errors([$error])->sendJson();
        } catch(Exception $e) {
            $error = $e->getMessage();
            response([], "Failed to delete stage", 500)->errors([$error])->sendJson();
        }
    }


    public function formContextAction(TinyPHP_Request $request) {

        if( !$request->isMethod("get") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        try {

            $id = $request->getInput("id", "Int", 0);

            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;

            $stageService = new Service_Crm_Stage(new Service_TenantContext($companyId, $userId));
            $data = $stageService->getFormContext($id);

            response($data)->sendJson();

        } catch(Service_Exception $e) {
            $statusCode = $e->getStatusCode() ?: 500;
            response([], $e->getMessage(), $statusCode)->sendJson();
        } catch(Exception $e) {
            response([], "Failed to load form context", 500)->sendJson();
        }
    }
}
