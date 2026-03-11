<?php
class Api_ProductsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    // Products (SKU)
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

        $columns = [
            "id" => "p.id", "master_id" => "p.master_id", "name" => "p.name", "description" => "p.description", "image_url" => "p.image_url", "sale_price" =>"p.sale_price","status" =>"p.status","created_at" => "p.created_at",
            "category" => "c.name",            
        ];
        
        $dataFetch = new TinyPHP_DataFetch($request);
        $results = $dataFetch
        ->table("products AS p")
        ->joins("INNER JOIN product_masters AS pm ON pm.id=p.master_id
        LEFT JOIN product_categories c ON pm.category_id=c.id")
        ->columns($columns)        
        ->where("pm.company_id = ? AND pm.status <> ?", [$companyId, "archived"])
        ->fetch();
        
        response($results)->sendJson();
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
            
            $productService = new Service_Product(new Service_TenantContext($companyId, $userId));
            if( $action === "update" ) {                                
                $response = $productService->update($id, $inputs);
            } else {                
                $response = $productService->create($inputs);
            }
            
            if( $response["success"] )
            {
                $responseMessage = $action === "update" ? "Product updated successfully" : "Product created successfully";
                $responseCode = $action === "update" ? 200 : 201;
                response($response["data"], $responseMessage, $responseCode)->sendJson();
            }
            else
            {
                $responseMessage = $action === "update" ? "Failed to update product" : "Failed to create product";
                response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
            }
        }
        catch(Service_Exception $e) {

            $error = $e->getMessage();
            $statusCode = $e->getStatusCode() ?: 500;
            response([], "Failed to save product", $statusCode)->errors([$error])->sendJson();
        } 
        catch(Exception $e) {

            $error = $e->getMessage();
            response([], "Failed to save product", 500)->errors([$error])->sendJson();
        }
    }



    private function handleDelete(TinyPHP_Request $request) {
        
        try {

            $id = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;

            $product = new Service_Product(new Service_TenantContext($companyId, $userId));
            $response = $product->delete($id);

            if( $response["success"] ) {
                response($response["data"], "Product deleted successfully", 200)->sendJson();
            } else {                
                response([], "Failed to delete product", 422)->errors($response["errors"])->sendJson();
            }
        }
        catch(Service_Exception $e) {

            $error = $e->getMessage() ?: "Failed to delete product";
            $statusCode = $e->getStatusCode() ?: 500;
            response([], $error, $statusCode)->sendJson();
        } 
        catch(Exception $e) {

            $error = $e->getMessage() ?: "Failed to delete product";
            response([], $error, 500)->sendJson();
        }        
    }


    public function formContextAction(TinyPHP_Request $request) {

        if( !$request->isMethod("get") ) {
            response([], "Method not allowed", 405)->sendJson();    
        }

        $id = $request->getInput("id", "Int", 0);
        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;
        
        try {

            $productService = new Service_Product(new Service_TenantContext($companyId, $userId));            
            $data = $productService->getFormContext($id);
            
            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode())->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to fetch form context", 500)->sendJson();
        }
    }
}