<?php
class TinyPHP_Response {

    protected array|object $data = [];
    protected ?array $errors = null;
    protected ?array $meta = null;
    protected int $statusCode = 200;
    protected string $message = '';
    protected array $headers = [];
    protected int $jsonOptions = 0;
    
    public function __construct(array|object $data = [], string $message = '', int $statusCode = 200) {
        
        $this->data = $data;
        $this->message = $message;
        $this->statusCode = $statusCode;
    }

    // ===== Setters =====
    public function data(array|object $data): self {
        $this->data = $data;
        return $this;
    }

    public function message(string $message): self {
        $this->message = $message;
        return $this;
    }

    public function status(int $code): self {
        $this->statusCode = $code;
        return $this;
    }

    public function errors(array $errors): self {
        $this->errors = $errors;
        return $this;
    }

    public function meta(array $meta): self {
        $this->meta = $meta;
        return $this;
    }

    public function headers(array $headers): self {
        $this->headers = $headers;
        return $this;
    }

    public function jsonOptions(int $options): self {
        $this->jsonOptions = $options;
        return $this;
    }

    
    // ===== Helpers =====
    protected function isSuccess(): bool {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    protected function formatData(): array {
        $data = $this->data;

        // Convert objects to array
        if (is_object($data)) {
            $data = method_exists($data, 'toArray') ? $data->toArray() : (array) $data;
        }

        return $data;
    }

    // ===== Send Json Response =====
    public function sendJson(): void {
        
        // Set HTTP status code
        http_response_code($this->statusCode);

        // Set default JSON content type
        header('Content-Type: application/json');

        // Set custom headers
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        // Build response array
        $response = [
            'status' => $this->isSuccess() ? 'success' : 'error',
            'code' => $this->statusCode,
            'message' => $this->message,
            'data' => $this->formatData(),
        ];

        if ($this->errors) $response['errors'] = $this->errors;
        if ($this->meta) $response['meta'] = $this->meta;

        ob_clean();
        echo json_encode($response, $this->jsonOptions);
        exit;        
    }
}