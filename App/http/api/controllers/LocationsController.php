<?php
class Api_LocationsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    public function indexAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            return $this->handleGet($request);
        }
        else if( $request->isMethod("post") ) {
            return $this->handlePost($request);
        }
        else if( $request->isMethod("delete") ) {
            return $this->handleDelete($request);
        }        
    }



    private function handleGet(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;
        
        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = ["id" => "l.id","name" => "l.name","code" => "l.code","type" => "l.type","address" => "l.address_line1", "address_line2" => "l.address_line2", "city" => "l.city", "state" => "l.state", "country" => "l.country", "zip" => "l.zip", "status" => "l.status"];

        $results = $dataFetch
        ->table("company_locations AS l")
        ->columns($columns)
        ->virtualColumns(['address' => ['address_line1', 'address_line2', 'city', 'state', 'country', 'zip']])
        ->where("l.company_id = ?", [$companyId])
        ->fetch();

        return response($results)->sendJson();
    }


    private function handlePost(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);

        $companyId = tenantContext()->companyId;

        $action = "create";
        if( $id ) {
            $action = "update";
        }

        $location = new Models_Location($id);
        $location->fillFromRequest($request);        
        $location->is_main = $request->getInput("is_main", "int", 0);
        $location->status = $request->getInput("status", "string", "inactive");

        if( $action === "update" ) {
            $id = $location->update();
        } else {
            $location->company_id = $companyId;
            $id = $location->create();
        }


        if( $id )
        {
            $responseMessage = $action === "update" ? "Location updated successfully" : "Location added successfully";
            $responseCode = $action === "update" ? 200 : 201;
            
            return response([], $responseMessage, $responseCode)->sendJson();
        }
        else
        {
            $errorCode = $location->getErrorCode();
            $errorMessage = $location->getErrorMessage();
            $errors = $location->getErrors();

            $responseCode = $errorCode ?: 422;
            $responseMessage = $action === "update" ? ($errorMessage ?: "Location updated successfully") : ( $errorMessage ?: "Location added successfully");
            return response([], $responseMessage, $responseCode)->errors($errors)->sendJson();
        }
    }



    private function handleDelete(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);

        $companyId = tenantContext()->companyId;

        $location = new Models_Location($id);
        if( $location->isEmpty ) {
            return response([], "The requested resource could not be found", 404)->sendJson();
        }

        if( $location->company_id !== $companyId ) {
            return response([], "You do not have permission to perform this action", 403)->sendJson();
        }

        $location->delete();


        if( $location->getDeletedRows() > 0 ) {

            return response([], "Location deleted successfully", 200)->sendJson();
        }
        else
        {
            $errorCode = $location->getErrorCode();
            $errorMessage = $location->getErrorMessage();
            $errors = $location->getErrors();

            $responseCode = $errorCode ?: 422;
            $responseMessage = $errorMessage ?: "Failed to delete location";
            
            return response([], $responseMessage, $responseCode)->errors($errors)->sendJson();
        }
    }


    public function formContextAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);

        $companyId = tenantContext()->companyId;

        $locationDetails = [];
        if( $id )
        {
            $location = new Models_Location($id);
            if( $location->isEmpty ) {
                return response([], "The requested resource could not be found", 404)->sendJson();
            }

            if( $location->company_id != $companyId ) {
                return response([], "You do not have permission to access this resource", 403)->sendJson();
            }

            $locationDetails = $location->toArray();
        }

        $data = ['location_details' => $locationDetails];

        return response($data)->sendJson();
    }
}