<?php
class Api_ProductMastersController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    public function indexAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            
            $companyId = auth()->getCompanyId();

            $columns = [
                "id" => "p.id","name" => "p.name","sale_price" =>"p.sale_price","status" =>"p.status","created_at" => "p.created_at",
                "category" => "c.name",            
            ];
            
            $dataFetch = new TinyPHP_DataFetch($request);
            $results = $dataFetch
            ->table("product_masters AS p")
            ->joins("LEFT JOIN product_categories c ON p.category_id=c.id")
            ->columns($columns)        
            ->where("p.company_id = ? AND p.status <> ?", [$companyId, "archived"])
            ->fetch();
            
            response($results)->sendJson();

        }
        else if( $request->isMethod("delete") ) {

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

        response([], "Method not allowed", 405)->sendJson();
    }


}