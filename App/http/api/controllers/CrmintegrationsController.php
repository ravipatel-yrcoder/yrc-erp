<?php
class Api_CrmIntegrationsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }


    // GET /api/crm/integrations       — list
    // POST /api/crm/integrations      — create
    // DELETE /api/crm/integrations    — delete (id in body)
    public function indexAction(TinyPHP_Request $request) {

        if( $request->isMethod("get") ) {
            $this->handleList($request);
        } elseif( $request->isMethod("post") ) {
            $this->handleSave($request, 0);
        } elseif( $request->isMethod("delete") ) {
            $this->handleDelete($request);
        }

        response([], "Method not allowed", 405)->sendJson();
    }


    // GET /api/crm/integrations/:id   — single
    // POST /api/crm/integrations/:id  — update
    public function entityAction(TinyPHP_Request $request) {

        if( $request->isMethod("post") ) {
            $id = $request->getInput("id", "Int", 0);
            $this->handleSave($request, $id);
        }

        response([], "Method not allowed", 405)->sendJson();
    }


    // GET /api/crm/integrations/form-context
    public function formContextAction(TinyPHP_Request $request) {

        if( !$request->isMethod("get") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        try {

            $id        = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId    = auth()->user()->id;

            $service = new Service_Webhook_Integration(new Service_TenantContext($companyId, $userId));
            $data    = $service->getFormContext($id);

            response($data)->sendJson();

        } catch( Service_Exception $e ) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->sendJson();
        } catch( Exception $e ) {
            response([], "Failed to load form context", 500)->sendJson();
        }
    }


    private function handleList(TinyPHP_Request $request) {

        try {

            $companyId = auth()->getCompanyId();
            $userId    = auth()->user()->id;

            $service = new Service_Webhook_Integration(new Service_TenantContext($companyId, $userId));
            $data    = $service->list();

            response($data)->sendJson();

        } catch( Exception $e ) {
            response([], "Failed to load integrations", 500)->sendJson();
        }
    }


    private function handleSave(TinyPHP_Request $request, int $id) {

        try {

            $companyId = auth()->getCompanyId();
            $userId    = auth()->user()->id;
            $inputs    = $request->getInputs();

            $service = new Service_Webhook_Integration(new Service_TenantContext($companyId, $userId));

            if( $id > 0 ) {
                $result  = $service->update($id, $inputs);
                $message = "Integration updated successfully";
                $code    = 200;
            } else {
                $result  = $service->create($inputs);
                $message = "Integration created successfully";
                $code    = 201;
            }

            if( $result["success"] ) {
                response($result["data"], $message, $code)->sendJson();
            } else {
                response([], "Failed to save integration", 422)->errors($result["errors"])->sendJson();
            }

        } catch( Service_Exception $e ) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->sendJson();
        } catch( Exception $e ) {
            response([], "Failed to save integration", 500)->sendJson();
        }
    }


    private function handleDelete(TinyPHP_Request $request) {

        try {

            $id        = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId    = auth()->user()->id;

            $service = new Service_Webhook_Integration(new Service_TenantContext($companyId, $userId));
            $service->delete($id);

            response([], "Integration deleted successfully", 200)->sendJson();

        } catch( Service_Exception $e ) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->sendJson();
        } catch( Exception $e ) {
            response([], "Failed to delete integration", 500)->sendJson();
        }
    }
}
?>
