<?php
abstract class TinyPHP_Controller
{
	private $_view;
	private $pageTitleKey = "pageTitle";
	private $metaDescKey = "metaDesc";
	private $_actionErrors;
	
	private $properties = array();

	final public function __construct()  {

		$this->_view = TinyPHP_View::getInstance();
		$this->_actionErrors = array();
	}

	public function __set($name, $value) {

		$this->properties[$name] = $value;
	}

	public function __get($name) {
		
		if (array_key_exists($name, $this->properties)) {
			return $this->properties[$name];
		}

		$trace = debug_backtrace();
		trigger_error(
		'Undefined property via __get(): ' . $name .
		' in ' . $trace[0]['file'] .
		' on line ' . $trace[0]['line'],
		E_USER_NOTICE);
		
		return null;
	}

	final public function setTitle($_title) {

		$this->_view->setViewVar($this->pageTitleKey, $_title);
	}
	
	final public function setBreadcrumb(array $breadcrumbs) {

	    $this->_view->setViewVar('breadcrumbs', $breadcrumbs);
	}
	
	final public function setPageHeading($heading) {

	    $this->_view->setViewVar('pageHeading', $heading);
	}
	
	final public function setPageHeadingSmall($headingSmall) {

	    $this->_view->setViewVar('pageHeadingSmall', $headingSmall);
	}
	
	final public function setMetaDescription($_description){

		$this->_view->setViewVar($this->metaDescKey, $_description);
	}
	
	final public function setBodyClasses($classes) {

	    $_classes = $classes;
	    if( is_array($classes) && count($classes) > 0 )
	    {
	        $_classes = implode(" ", $classes);
	    }
	    
	    if( $_classes ) {
	        $this->_view->setViewVar('bodyClasses', $_classes);
	    }
	    
	}
	
	final public function setNoRenderer($_noRenderer) {

		TinyPHP_Front::getInstance()->setNoRenderer($_noRenderer);
	}
	
	final public function hideBreadcrumb() {

	    $this->_view->setViewVar('hideBreadcrumbs', true);
	}

	final public function disableHeader() {

	    $this->_view->setViewVar('showHeader', false);
	}
	
	final public function disableFooter() {

	    $this->_view->setViewVar('showFooter', false);
	}
	
	final public function getTitle() {

		$this->_view->getViewVars($this->pageTitleKey);
	}

	final public function  isPost() {

		return TinyPHP_Request::getInstance()->isPost();
	}
	
	final public function  isDelete() {

	    return TinyPHP_Request::getInstance()->isDelete();
	}

	final public function getRequest() {

		return TinyPHP_Request::getInstance();
	}

	final public function setViewVar($_varName, $_val) {

		$this->_view->setViewVar($_varName, $_val);
	}

	final public function setViewVars($_arrVars) {
		foreach($_arrVars as $key=> $value)
		{
			$this->_view->setViewVar($key,$value);
		}
	}

	final public function registerStylesheet($stylesheet) {

		$this->_view->registerStylesheet($stylesheet);
	}

	final public function registerHeaderScript($script) {

		$this->_view->registerHeaderScript($script);
	}
	
	final public function registerFooterScript($script) {

		$this->_view->registerFooterScript($script);
	}

	final public function addError($err_msg) {

		if(is_array($err_msg))
		{
			foreach($err_msg as $msg)
			{
				array_push($this->_actionErrors, $msg);
			}

		}
		else
		{
			array_push($this->_actionErrors,$err_msg);
		}

	}

	final public function hasErrors() {

		if(count($this->_actionErrors)>0) {
			return true;
		} else {
			return false;
		}
	}

	final public function getErrors($index = null) {

		if ($index !== 0 && empty($index)) {
			return $this->_actionErrors;
		} else {
			if (!isset($this->_actionErrors[$index])) {
				trigger_error("No error message exists at the specified $index", E_USER_WARNING);
			} else {
				return $this->_actionErrors[$index];
			}
		}
	}

    final public function resetErrors() {
        $this->_actionErrors = [];
    }
	

	final public function jsonResponse(array|object $data = [], string $message = '', int $code = 200, mixed $errors = null, mixed $meta = null, array $headers = [], int $jsonOptions = 0, bool $exitAfterSend = true): void {
        
		$resp = response($data, $message, $code, $errors, $meta)
                ->headers($headers)
                ->jsonOptions($jsonOptions);				
    	$resp->sendJson();
    }

}
?>