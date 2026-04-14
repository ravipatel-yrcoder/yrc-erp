<?php
class Models_WebhookLog extends TinyPHP_ActiveRecord
{
    public $tableName = "webhook_logs";

    public $integration_id = null;
    public $company_id = null;
    public $source = "";
    public $token = "";
    public $http_method = "";
    public $headers = null;
    public $raw_payload = null;
    public $parsed_payload = null;
    public $status = "received";
    public $failure_reason = null;
    public $ip_address = null;
    public $received_at = null;
    public $processed_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', [$this, 'doBeforeCreate']);
    }

    protected function doBeforeCreate() {
        if( empty($this->received_at) ) {
            $this->received_at = date("Y-m-d H:i:s");
        }
        return !$this->hasErrors();
    }
}
?>
