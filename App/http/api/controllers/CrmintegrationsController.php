<?php
class Api_CrmIntegrationsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }


    private function serviceWebhookIntegeration(): Service_Webhook_Integration {
        return new Service_Webhook_Integration(tenantContext());
    }


    // GET /api/crm/integrations — list
    // POST /api/crm/integrations — create
    // DELETE /api/crm/integrations — delete (id in body)
    public function indexAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            return $this->handleList($request);
        } elseif( $request->isMethod("post") ) {
            return $this->handleSave($request, 0);
        } elseif( $request->isMethod("delete") ) {
            return $this->handleDelete($request);
        }        
    }


    // GET /api/crm/integrations/:id   — single
    // POST /api/crm/integrations/:id  — update
    public function entityAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("post") ) {
            
            $id = $request->getInput("id", "Int", 0);
            return $this->handleSave($request, $id);
        }
    }


    // GET /api/crm/integrations/form-context
    public function formContextAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);

        $service = $this->serviceWebhookIntegeration();
        $data = $service->getFormContext($id);

        return response($data)->sendJson();
    }


    private function handleList(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;

        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = [
            "id" => "a.id",
            "name" => "a.name",
            "source" => "a.source",
            "token" => "a.token",
            "is_active" => "a.is_active",
            "created_at" => "a.created_at",
        ];

        $dataFetch->table("webhook_integrations AS a")
            ->columns($columns)
            ->where("a.company_id = ?", [$companyId]);

        $results = $dataFetch->fetch();

        return response($results)->sendJson();
    }


    private function handleSave(TinyPHP_Request $request, int $id) {

        $inputs = $request->getInputs();

        $service = new $this->serviceWebhookIntegeration();
        
        if( $id > 0 ) {
            $response = $service->update($id, $inputs);            
        } else {
            $response  = $service->create($inputs);            
        }

        if( $response["success"] ) {
            
            $responseMessage = $id ? "Integration updated successfully" : "Integration created successfully";
            $responseCode = $id ? 200 : 201;
            
            return response($response["data"], $responseMessage, $responseCode)->sendJson();
        } else {
            
            $responseMessage = $id ? "Failed to update integration" : "Failed to create integration";
            return response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
        }

    }


    private function handleDelete(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        
        $service = $this->serviceWebhookIntegeration();
        $service->delete($id);

        response([], "Integration deleted successfully", 200)->sendJson();
    }
}
?>
