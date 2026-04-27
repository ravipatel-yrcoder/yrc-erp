<?php
class Api_ProductMastersController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    public function indexAction(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;

        if( $request->isMethod("get") ) {
            
            $columns = ["id" => "p.id", "name" => "p.name", "structure_type" => "p.structure_type", "image_url" => "p.image_url", "status" =>"p.status", "created_at" => "p.created_at", "category" => "c.name"];
            
            $dataFetch = new TinyPHP_DataFetch($request);
            $results = $dataFetch
            ->table("product_masters AS p")
            ->joins("LEFT JOIN product_categories c ON p.category_id=c.id")
            ->columns($columns)        
            ->where("p.company_id = ? AND p.status <> ?", [$companyId, "archived"])
            ->fetch();
            
            return response($results)->sendJson();

        }
        else if( $request->isMethod("delete") ) {

            $id = $request->getInput("id", "Int", 0);

            $productMaster = new Models_ProductMaster($id);
            if( $productMaster->isEmpty ) {
                return response([], "The requested resource could not be found", 404)->sendJson();
            }

            if( $productMaster->company_id !== $companyId ) {
                return response([], "You do not have permission to perform this action", 403)->sendJson();
            }
            $productMaster->delete();


            if( $productMaster->getDeletedRows() > 0 )
            {
                return response([], "Product deleted successfully", 200)->sendJson();
            }
            else
            {
                $errorCode = $productMaster->getErrorCode();
                $errorMessage = $productMaster->getErrorMessage();
                $errors = $productMaster->getErrors();

                $responseCode = $errorCode ?: 422;
                $responseMessage = $errorMessage ?: "Failed to delete product";
                return response([], $responseMessage, $responseCode)->errors($errors)->sendJson();
            }
        }        
    }
}