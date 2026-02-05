<?php
class Api_PurchaseOrdersController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    public function indexAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            $this->list($request);
        }
        else if( $request->isMethod("post") ) {
            $this->save($request);
        }
        /*else if( $request->isMethod("delete") ) {
            $this->handleDelete($request);
        }*/

        response([], "Method not allowed", 405)->sendJson();
    }

    
    public function formContextAction(TinyPHP_Request $request) {

        if( !$request->isMethod("get") ) {
            response([], "Method not allowed", 405)->sendJson();    
        }
        

        $id = $request->getInput("id", "Int", 0);
        $companyId = auth()->getCompanyId();
        
        try {

            $poService = new Service_Po_Order($companyId);
            $data = $poService->getFormContext($id);
            
            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode())->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to fetch form context", 500)->sendJson();
        }        
    }


    public function entityAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            $this->show($request);
        }
        else if( $request->isMethod("post") ) {
            $this->save($request);
        }

        response([], "Method not allowed", 405)->sendJson();
    }

    
    public function statusAction(TinyPHP_Request $request) {

        if( $request->isMethod("get") ) {
            // yet to implement logic
        }
        else if( $request->isMethod("post") ) {
            $this->updateStatus($request);
        }

        response([], "Method not allowed", 405)->sendJson();
    }


    // Create Purchase Receive Form
    public function receiveFormContextAction(TinyPHP_Request $request) {

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



    private function list(TinyPHP_Request $request) {

        $companyId = auth()->getCompanyId();        
        
        $dataFetch = new TinyPHP_DataFetch($request);
        
        $columns = ["id" => "po.id", "po_number" => "po.po_number", "order_date" => "po.order_date", "vendor" => "v.display_name", "reference" => "po.reference", "status" => "po.reference", "exp_delivery_date" => "po.expected_delivery_date", "amount" => "SUM(poi.line_total)"];

        $results = $dataFetch
        ->table("purchase_orders AS po")
        ->joins("LEFT JOIN vendors AS v ON po.vendor_id=v.id
        LEFT JOIN purchase_order_items AS poi ON po.id=poi.purchase_order_id")
        ->columns($columns)
        ->where("po.company_id = ?", [$companyId])
        ->groupBy("po.id")
        ->fetch();

        response($results)->sendJson();
    }


    private function save(TinyPHP_Request $request) {

        try {
            
            $id = $request->getInput("id", "Int", 0);
            $action = "create";
            if( $id ) {
                $action = "update";
            }

            $companyId = auth()->getCompanyId();
            $inputs = $request->getInputs();
            
            $poService = new Service_Po_Order($companyId);
            if( $action === "update" ) {                                
                $response = $poService->update($id, $inputs);

            } else {                
                $response = $poService->create($inputs);
            }
            
            if( $response["success"] )
            {
                $responseMessage = $action === "update" ? "Purchase order updated successfully" : "Purchase order created successfully";
                $responseCode = $action === "update" ? 200 : 201;
                response($response["data"], $responseMessage, $responseCode)->sendJson();
            }
            else
            {
                $responseMessage = $action === "update" ? "Failed to update purchase order" : "Failed to create purchase order";
                response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
            }
        }
        catch(Service_Exception $e) {

            $error = $e->getMessage();
            $statusCode = $e->getStatusCode() ?: 500;
            response([], "Failed to save purchase order", $statusCode)->errors([$error])->sendJson();
        } 
        catch(Exception $e) {

            $error = $e->getMessage();
            response([], "Failed to save purchase order", 500)->errors([$error])->sendJson();
        }

    }


    private function show(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $companyId = auth()->getCompanyId();
        
        try {

            $poService = new Service_Po_Order($companyId);
            $data = $poService->getDetails($id);
            
            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode())->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to fetch purchase order details", 500)->sendJson();
        }
    }


    private function updateStatus(TinyPHP_Request $request) {

        try {
            
            $id = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            
            $inputs = $request->getInputs();
            
            $poService = new Service_Po_Order($companyId);
            $response = $poService->updateStatus($id, $inputs);            

            if( $response["success"] ) {
                response($response["data"], "Status updated successfully", 200)->sendJson();
            }
            else {                
                response([], "Failed to update status", 422)->errors($response["errors"])->sendJson();
            }

        } 
        catch(Service_Exception $e) {

            $error = $e->getMessage();
            $statusCode = $e->getStatusCode() ?: 500;
            response([], "Failed to update status", $statusCode)->errors([$error])->sendJson();
        } 
        catch(Exception $e) {

            $error = $e->getMessage();
            response([], "Failed to update status", 500)->errors([$error])->sendJson();
        }

    }

}