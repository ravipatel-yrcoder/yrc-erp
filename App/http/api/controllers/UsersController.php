<?php
class Api_UsersController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    public function indexAction(TinyPHP_Request $request) {
        
        if( !$request->isMethod("get") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        global $db;

        $sql = "SELECT * FROM users";
        $results = $db->fetchAll($sql);
    }

}