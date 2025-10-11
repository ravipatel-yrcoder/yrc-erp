<?php
class AuthController extends TinyPHP_Controller {
	
    public function loginAction() {
	}

	
	public function registerAction()
	{
		$this->setTitle("Register");
	}

	public function forgotpasswordAction()
	{
		$this->setTitle("Forgot password");
	}

	public function resetpasswordAction()
	{
		$this->setTitle("Reset password");
	}
	
}
?> 