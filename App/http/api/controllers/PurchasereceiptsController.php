<?php
class Api_PurchaseReceiptsController extends TinyPHP_Controller {

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

        $receiptId = $request->getInput("id", "Int", 0);
        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;
        
        try {

            $poGrnService = new Service_Po_Grn(new Service_TenantContext($companyId, $userId));
            $data = $poGrnService->getEditFormContext($receiptId);
            
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


    private function list(TinyPHP_Request $request) {

        $companyId = auth()->getCompanyId();
        $poId = $request->getInput("po_id", "Int", 0);

        $whereClause = "grn.company_id = ?";
        $whereBind = [$companyId];
        if( $poId ) {
            $whereClause .= " AND grn.purchase_order_id = ?";
            $whereBind[] = $poId;
        }

        
        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = ["id" => "grn.id", "receipt_number" => "grn.grn_number", "create_date" => "grn.created_at", "received_date" => "grn.received_date", "vendor" => "v.display_name", "purchase_order_id" => "po.id", "po_number" => "po.po_number", "status" => "grn.status", "items_count" => "count(grni.id)"];

        $results = $dataFetch
        ->table("purchase_order_grns AS grn")
        ->joins("
        LEFT JOIN purchase_orders AS po ON grn.purchase_order_id=po.id
        LEFT JOIN vendors AS v ON po.vendor_id=v.id
        LEFT JOIN purchase_order_grn_items AS grni ON grn.id=grni.purchase_order_grn_id")
        ->columns($columns)
        ->where($whereClause, $whereBind)
        ->groupBy("grn.id")
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
            $userId = auth()->user()->id;
            $inputs = $request->getInputs();

            $grnService = new Service_Po_Grn(new Service_TenantContext($companyId, $userId));
            if( $action === "update" ) {
                $response = $grnService->update($id, $inputs);

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


    private function show(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;
        
        try {

            $grnService = new Service_Po_Grn(new Service_TenantContext($companyId, $userId));
            $data = $grnService->getDetails($id);
            
            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode())->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to fetch purchase receipt details", 500)->sendJson();
        }
    }


    private function updateStatus(TinyPHP_Request $request) {

        try {
            
            $id = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;
            
            $inputs = $request->getInputs();
            
            $grnService = new Service_Po_Grn(new Service_TenantContext($companyId, $userId));
            $response = $grnService->updateStatus($id, $inputs);            

            if( $response["success"] ) {
                response($response["data"], "Status updated successfully", 200)->sendJson();
            }
            else {
                response([], "Failed to update status", 422)->errors($response["errors"])->sendJson();
            }

        } 
        catch(Service_Exception $e) {

            $error = $e->getMessage() ?: "Failed to update status";
            $statusCode = $e->getStatusCode() ?: 500;
            response([], $error, $statusCode)->sendJson();
        } 
        catch(Exception $e) {

            $error = $e->getMessage() ?: "Failed to update status";
            response([], $error, 500)->sendJson();
        }

    }


    public function historyAction(TinyPHP_Request $request) {
        
        if( !$request->isMethod("get") ) {
            response([], "Method not allowed", 405)->sendJson();
        }
        
        $id = $request->getInput("id", "Int", 0);
        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;
        
        try {

            $grnService = new Service_Po_Grn(new Service_TenantContext($companyId, $userId));
            $data = $grnService->getHistory($id);
            
            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode())->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to fetch receive history", 500)->sendJson();
        }
    }

}
