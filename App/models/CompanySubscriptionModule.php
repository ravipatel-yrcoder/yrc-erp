<?php
class Models_CompanySubscriptionModule extends TinyPHP_ActiveRecord
{
    public $tableName = "company_subscription_modules";
    //protected $dbConnectionName = "platform_db";

    public $company_id = 0;
    public $subscription_id = 0;
    public $module_id = 0;
    public $is_active = 1;
    public $activated_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', array($this, 'doBeforeCreate'));
    }

    protected function doBeforeCreate() {
        $this->activated_at = date("Y-m-d H:i:s");
        return !$this->hasErrors();
    }
}
?>
