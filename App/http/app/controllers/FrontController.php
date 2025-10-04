<?php
class FrontController extends TinyPHP_Controller {
	
    public function homeAction() {
		
		$this->setTitle("Home Page");
	}

	public function aboutusAction() {
	}

	public function contactusAction() {
		
		echo "Contact Us Page";
		die;
	}
	
}
?> 