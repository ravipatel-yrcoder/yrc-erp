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
            "id" => "p.id","master_id" => "p.master_id","name" => "p.name","sale_price" =>"p.sale_price","status" =>"p.status","created_at" => "p.created_at",
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
        
        $id = $request->getInput("id", "Int", 0);

        $action = "create";
        if( $id ) {
            $action = "update";
        }

        $companyId = auth()->getCompanyId();

        $productMaster = new Models_ProductMaster($id);
        if( $action === "update" ) {
            if( $productMaster->isEmpty ) {
                response([], "The requested resource could not be found", 404)->sendJson();
            }

            if( $productMaster->company_id !== $companyId ) {
                response([], "You do not have permission to perform this action", 403)->sendJson();
            }
        }

        $productMaster->fillFromRequest($request);
        $productMaster->status = $request->getInput("status", "string", "inactive");
        $productMaster->category_id = $request->getInput("category_id", "int", null) ?: null;

        if( $action === "update" ) {
            $id = $productMaster->update();
        } else {
            $id = $productMaster->create();
        }

        if( $id )
        {
            $responseMessage = $action === "update" ? "Product updated successfully" : "Product added successfully";
            $responseCode = $action === "update" ? 200 : 201;
            response([], $responseMessage, $responseCode)->sendJson();
        }
        else
        {
            $errorCode = $productMaster->getErrorCode();
            $errorMessage = $productMaster->getErrorMessage();
            $errors = $productMaster->getErrors();

            $responseCode = $errorCode ?: 422;
            $responseMessage = $action === "update" ? ($errorMessage ?: "Failed to update product") : ( $errorMessage ?: "Failed to add product");
            response([], $responseMessage, $responseCode)->errors($errors)->sendJson();
        }
    }



    private function handleDelete(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);

        $companyId = auth()->getCompanyId();

        $productMaster = new Models_ProductMaster($id);
        if( $productMaster->isEmpty ) {
            response([], "The requested resource could not be found", 404)->sendJson();
        }

        if( $productMaster->company_id !== $companyId ) {
            response([], "You do not have permission to perform this action", 403)->sendJson();
        }
        $productMaster->delete();


        if( $productMaster->getDeletedRows() > 0 )
        {
            response([], "Product deleted successfully", 200)->sendJson();
        }
        else
        {
            $errorCode = $productMaster->getErrorCode();
            $errorMessage = $productMaster->getErrorMessage();
            $errors = $productMaster->getErrors();

            $responseCode = $errorCode ?: 422;
            $responseMessage = $errorMessage ?: "Failed to delete product";
            response([], $responseMessage, $responseCode)->errors($errors)->sendJson();
        }
    }


    public function formContextAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);

        $companyId = auth()->getCompanyId();

        $categories = Models_ProdCategory::getCategories($companyId, "tree");

        $forbidden = false;

        $productDetails = [];
        if( $id )
        {
            $productMaster = new Models_ProductMaster($id);
            if( !$productMaster->isEmpty )
            {
                if( $productMaster->company_id === $companyId )
                {
                    $productDetails = array_merge(['id' => $id], $productMaster->toArray());                    
                }
                else
                {
                    $forbidden = true;
                }
            }
        }

        if( $forbidden === true )
        {
            response([], "You do not have permission to access this resource", 403)->sendJson();
        }


        $data = [
            'categories' => $categories,
            'product_details' => $productDetails,
        ];

        response($data)->sendJson();
    }
}