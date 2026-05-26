<?php
class AuthController extends TinyPHP_Controller {

    public function loginAction() {
    }

    public function registerAction() {
    }

    public function forgotpasswordAction() {
    }

    public function resetpasswordAction() {
        $request = TinyPHP_Request::getInstance();
        $this->setViewVar('token', $request->getInput("token", "String", ""));
        $this->setViewVar('email', $request->getInput("email", "String", ""));
    }
}
