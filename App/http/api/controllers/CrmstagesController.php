<?php
class Api_CrmStagesController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function serviceCrmStage(): Service_Crm_Stage {
        return new Service_Crm_Stage(tenantContext());
    }

    public function indexAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            return $this->handleList($request);
        }
        else if( $request->isMethod("post") ) {
            return $this->handlePost($request);
        }
        else if( $request->isMethod("delete") ) {
            return $this->handleDelete($request);
        }
    }


    private function handleList(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;

        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = [
            "id" => "a.id",
            "name" => "a.name",
            "probability" => "a.probability",
            "sort_order" => "a.sort_order",
            "is_won" => "a.is_won",
            "is_lost" => "a.is_lost",
            "color" => "a.color",
            "status" => "a.status",
            "created_at" => "a.created_at",
        ];

        $dataFetch->table("crm_stages AS a")
            ->columns($columns)
            ->where("a.company_id = ?", [$companyId]);

        $results = $dataFetch->fetch();

        return response($results)->sendJson();
    }


    private function handlePost(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);

        $action = "create";
        if( $id ) {
            $action = "update";
        }

        $inputs = $request->getInputs();

        $service = $this->serviceCrmStage();

        if( $action === "update" ) {
            $response = $service->update($id, $inputs);
        } else {
            $response = $service->create($inputs);
        }

        if( $response["success"] ) {
            
            $responseMessage = $action === "update" ? "Stage updated successfully" : "Stage created successfully";
            $responseCode = $action === "update" ? 200 : 201;
            
            return response($response["data"], $responseMessage, $responseCode)->sendJson();
        } else {
            
            $responseMessage = $action === "update" ? "Failed to update stage" : "Failed to create stage";
            
            return response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
        }
    }


    private function handleDelete(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);

        $service = $this->serviceCrmStage();
        $service->delete($id);

        return response([], "Stage deleted successfully", 200)->sendJson();
    }


    public function formContextAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);

        $service = $this->serviceCrmStage();
        $data = $service->getFormContext($id);

        return response($data)->sendJson();
    }
}