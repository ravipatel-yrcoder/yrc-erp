<?php
abstract  class Service_Base {

	protected $db;
    protected Service_TenantContext $context;
    private $errors = [];

    public function __construct(Service_TenantContext $context) {
        $this->context = $context;
        $this->db = Service_TenantDBResolver::resolve($context->companyId);
    }
    
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