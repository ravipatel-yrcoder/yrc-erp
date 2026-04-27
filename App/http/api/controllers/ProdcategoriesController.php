<?php
class Api_ProdCategoriesController extends TinyPHP_Controller {

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

        $format = $request->getInput("format", "String", "list");

        $companyId = tenantContext()->companyId;
        $categories = Models_ProdCategory::getCategories($companyId, $format);

        return response($categories)->sendJson();
    }


    private function handlePost(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;
        $id = $request->getInput("id", "Int", 0);

        $action = "create";
        if( $id ) {
            $action = "update";
        }

        $prodCategory = new Models_ProdCategory($id);
        $prodCategory->fillFromRequest($request);
        $prodCategory->status = $request->getInput("status", "string", "inactive");

        if( $action === "update" ) {
            $id = $prodCategory->update();
        } else {
            $prodCategory->company_id = $companyId;
            $id = $prodCategory->create();
        }

        if( $id ) {
            
            $responseMessage = $action === "update" ? "Category updated successfully" : "Category added successfully";
            $responseCode = $action === "update" ? 200 : 201;
            
            return response([], $responseMessage, $responseCode)->sendJson();
        } else {
            
            $errorCode = $prodCategory->getErrorCode();
            $errorMessage = $prodCategory->getErrorMessage();
            $errors = $prodCategory->getErrors();

            $responseCode = $errorCode ?: 422;
            $responseMessage = $action === "update" ? ($errorMessage ?: "Failed to update category") : ( $errorMessage ?: "Failed to add category");
            
            return response([], $responseMessage, $responseCode)->errors($errors)->sendJson();
        }
    }


    private function handleDelete(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);

        $companyId = tenantContext()->companyId;

        $prodCategory = new Models_ProdCategory($id);
        if( $prodCategory->isEmpty ) {
            return response([], "The requested resource could not be found", 404)->sendJson();
        }

        if( $prodCategory->company_id !== $companyId ) {
            return response([], "You do not have permission to perform this action", 403)->sendJson();
        }

        $prodCategory->delete();

        if( $prodCategory->getDeletedRows() > 0 ) {
            return  response([], "Category deleted successfully", 200)->sendJson();
        } else {
            
            $errorCode = $prodCategory->getErrorCode();
            $errorMessage = $prodCategory->getErrorMessage();
            $errors = $prodCategory->getErrors();

            $responseCode = $errorCode ?: 422;
            $responseMessage = $errorMessage ?: "Failed to delete category";
            
            return response([], $responseMessage, $responseCode)->errors($errors)->sendJson();
        }
    }


    public function formContextAction(TinyPHP_Request $request) {        

        $companyId = tenantContext()->companyId;
        $id = $request->getInput("id", "Int", 0);

        $categories = Models_ProdCategory::getCategories($companyId, "tree");

        $categoryDetails = [];
        if( $id ) {
            $prodCategory = new Models_ProdCategory($id);
            if( $prodCategory->isEmpty ) {
                return response([], "The requested resource could not be found", 404)->sendJson();
            }

            if( $prodCategory->company_id != $companyId ) {
                return response([], "You do not have permission to access this resource", 403)->sendJson();
            }

            $categoryDetails = $prodCategory->toArray();
        }

        $data = [
            'categories' => $categories,
            'category_details' => $categoryDetails,
        ];

        return response($data)->sendJson();
    }
}
