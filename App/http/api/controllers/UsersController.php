<?php
class Api_UsersController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }


    public function indexAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            $this->handleGet($request);
        }
        
        response([], "Method not allowed", 405)->sendJson();
    }



    public function handleGet(TinyPHP_Request $request) {
                
        $companyId = auth()->getCompanyId();

        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = ["id" => "u.id", "name" => "u.name", "email" => "u.email", "role" => "u.role", "status" => "u.status", "created_at" => "u.created_at"];
        $results = $dataFetch
        ->table("users AS u")
        ->columns($columns)
        ->where("u.company_id = ?", [$companyId])
        ->fetch();

        response($results)->sendJson();
    }

}