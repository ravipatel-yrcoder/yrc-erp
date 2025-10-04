<?php
class Service_Base {

	private $errors = array();
    
    public function addError($err)
    {
        if(is_array($err))
        {
            foreach($err as $msg)
            {
                array_push($this->errors, $msg);
            }
        }
        else
        {
            array_push($this->errors, $err);
        }
    }
    
    public function getErrors()
    {
        return $this->errors;
    }
	
    public function hasErrors()
    {
        $hasErrors = false;
        if( count($this->errors) > 0 )
        {
            $hasErrors = true;
        }
        
        return $hasErrors;
    }
    
    public function resetErrors()
    {
        $this->errors = array();
    }
}
?>