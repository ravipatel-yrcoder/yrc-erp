<?php
class Plugins_AppSessionValidator {

	private $exemptedProperties = array();

	public function preDispatch(TinyPHP_Request $request)
	{
		$this->exemptedProperties['login'] = array('index', 'dologin');
		$this->exemptedProperties['resetpassword'] = array('index', 'sendresetlink', 'changepassword', 'updatepassword');
		$this->exemptedProperties['logout'] = array('index', 'success');
                $this->exemptedProperties['unsubscribe'] = array('index');

		if($request->getModuleName() == "app")
		{
			$this->setVars();
				
			$currentController = $request->getControllerName();
			$currentAction = $request->getActionName();

			if(array_key_exists($currentController,$this->exemptedProperties))
			{
				if(in_array($currentAction,$this->exemptedProperties[$currentController]))
				{
					return;
				}
				else
				{
					$this->validateSession();
				}

			}
			else
			{
				$this->validateSession();
			}
		}
	}


	private function validateSession()
	{
	    $id = getLoggedInCustomerId();
		if( empty($id) )
		{
	        $this->setVars();
	        $retUrl = $_SERVER['REQUEST_URI'];
	        header("Location: /app/login/?retURL=$retUrl");
	        exit();
		}
	}
	
	private function setVars()
	{
	    $loggedInCustomer = getLoggedInCustomer();
		
	    TinyPHP_Front::getInstance()->setPreDispatchVar('sessionCustomerId',$loggedInCustomer->id);
	    TinyPHP_Front::getInstance()->setPreDispatchVar('sessionCustomerName',$loggedInCustomer->firstName);
	}
}
?>