<?php
class Service_Exception extends Exception
{
    protected int $statusCode;
    protected array $errors;
    protected string $errorCode;

    public function __construct(string $message, int $statusCode = 422, array $errors = [], string $errorCode = '') {
        
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
        $this->errorCode = $errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}