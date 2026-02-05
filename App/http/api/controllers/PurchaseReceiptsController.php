<?php
class Api_PurchaseReceiptsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    public function indexAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            //$this->list($request);
        }
        else if( $request->isMethod("post") ) {
            $this->save($request);
        }
        /*else if( $request->isMethod("delete") ) {
            $this->handleDelete($request);
        }*/

        response([], "Method not allowed", 405)->sendJson();
    }

    
    /*
    public function formContextAction(TinyPHP_Request $request) {

        if( !$request->isMethod("get") ) {
            response([], "Method not allowed", 405)->sendJson();    
        }

        $poId = $request->getInput("id", "Int", 0);
        $companyId = auth()->getCompanyId();
        
        try {

            $poGrnService = new Service_Po_Grn($companyId);
            $data = $poGrnService->getCreateFormContext($poId);
            
            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode())->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to fetch form context", 500)->sendJson();
        }        
    }
    */



    private function save(TinyPHP_Request $request) {

        try {
            
            $id = $request->getInput("id", "Int", 0);
            $action = "create";
            if( $id ) {
                $action = "update";
            }

            $companyId = auth()->getCompanyId();
            $inputs = $request->getInputs();

            $grnService = new Service_Po_Grn($companyId);
            if( $action === "update" ) {                                
                //$response = $grnService->update($id, $inputs);

            } else {                
                $poId = $request->getInput("purchase_order_id", "Int", 0);
                $response = $grnService->create($poId, $inputs);
            }
            
            if( $response["success"] )
            {
                $responseMessage = $action === "update" ? "Purchase receipt updated successfully" : "Purchase receipt created successfully";
                $responseCode = $action === "update" ? 200 : 201;
                response($response["data"], $responseMessage, $responseCode)->sendJson();
            }
            else
            {
                $responseMessage = $action === "update" ? "Failed to update purchase receipt" : "Failed to create purchase receipt";
                response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
            }            
        }
        catch(Service_Exception $e) {

            $error = $e->getMessage();
            $statusCode = $e->getStatusCode() ?: 500;
            response([], "Failed to save purchase receipt", $statusCode)->errors([$error])->sendJson();
        } 
        catch(Exception $e) {

            $error = $e->getMessage();
            response([], "Failed to save purchase receipt", 500)->errors([$error])->sendJson();
        }

    }

}