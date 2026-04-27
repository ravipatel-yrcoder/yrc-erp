<?php
class Service_Exception extends TinyPHP_Exception
{
    protected int $statusCode;
    protected array $errors;
    protected string $errorCode;

    public function __construct(string $message, int $statusCode = 422, array $errors = [], string $errorCode = '') {
        
        parent::__construct($message, $statusCode, $errorCode, $errors);
    }    
}