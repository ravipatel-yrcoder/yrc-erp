<?php
class Models_WebhookIntegration extends TinyPHP_ActiveRecord
{
    public $tableName = "webhook_integrations";

    public $company_id = 0;
    public $name = "";
    public $source = "";
    public $token = "";
    public $is_active = 1;
    public $created_by = 0;
    public $created_at = null;
    public $updated_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', [$this, 'doBeforeCreate']);
        $this->addListener('beforeUpdate', [$this, 'doBeforeUpdate']);
    }

    protected function doBeforeCreate() {
        $date = date("Y-m-d H:i:s");
        $this->created_at = $date;
        $this->updated_at = $date;
        return !$this->hasErrors();
    }

    protected function doBeforeUpdate() {
        $this->updated_at = date("Y-m-d H:i:s");
        return !$this->hasErrors();
    }
}
?>
