<?php
class Service_Base {

	private $errors = [];
    
    public function addError($err, $idx=null)
    {
        if(is_array($err))
        {
            foreach($err as $key => $msg)
            {
                if( is_numeric($key) ) {
                    $this->errors[] = $msg;
                } else {
                    $this->errors[$key] = $msg;
                }
            }
        }
        else
        {
            if (empty($idx)) {
                $this->errors[] = $err;
            } else {
                $this->errors[$idx] = $err;
            } 
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
        $this->errors = [];
    }
}
?>