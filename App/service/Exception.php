<?php
class Service_Exception extends Exception {

    public $field;
    
    public function __construct($message, $field = null)
    {
        parent::__construct($message);
        $this->field = $field;
    }

}
?>