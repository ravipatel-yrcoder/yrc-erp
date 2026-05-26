<?php
class Api_ProductsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function serviceProduct(): Service_Product {
        return new Service_Product(tenantContext());
    }

    // Products (SKU)
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

        $columns = [
            "id" => "p.id", "master_id" => "p.master_id", "name" => "p.name", "description" => "p.description", "image_url" => "p.image_url", "sale_price" =>"p.sale_price","status" =>"p.status","stock_tracking_method" => "p.stock_tracking_method","created_at" => "p.created_at",
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

        return response($results)->sendJson();
    }


    private function handlePost(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $action = "create";
        if( $id ) {
            $action = "update";
        }

        $inputs = $request->getInputs();

        $service = $this->serviceProduct();
        if( $action === "update" ) {
            $response = $service->update($id, $inputs);
        } else {
            $response = $service->create($inputs);
        }

        if( $response["success"] ) {
            
            $responseMessage = $action === "update" ? "Product updated successfully" : "Product created successfully";
            $responseCode = $action === "update" ? 200 : 201;
            
            return response($response["data"], $responseMessage, $responseCode)->sendJson();
        } else {
            
            $responseMessage = $action === "update" ? "Failed to update product" : "Failed to create product";
            
            return response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
        }
    }


    private function handleDelete(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
            
        $service = $this->serviceProduct();
        $response = $service->delete($id);

        if( $response["success"] ) {
            return response($response["data"], "Product deleted successfully", 200)->sendJson();
        } else {
            return response([], "Failed to delete product", 422)->errors($response["errors"])->sendJson();
        }
    }


    public function searchAction(TinyPHP_Request $request) {
        $q = trim($request->getInput("q", "String", ""));
        $service = $this->serviceProduct();
        $results = $service->search($q);
        return response($results)->sendJson();
    }

    public function formContextAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);
        
        $service = $this->serviceProduct();
        $data = $service->getFormContext($id);

        return response($data)->sendJson();
    }
}
