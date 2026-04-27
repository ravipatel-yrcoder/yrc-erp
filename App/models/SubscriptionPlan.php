<?php
class Models_SubscriptionPlan extends TinyPHP_ActiveRecord
{
    public $tableName = "subscription_plans";
    //protected $dbConnectionName = "platform_db";

    public $name = "";
    public $slug = "";
    public $description = null;
    public $max_modules = null;
    public $free_users_included = 3;
    public $base_price_monthly = "0.0000";
    public $extra_user_price_monthly = "0.0000";
    public $is_active = 1;
    public $created_by = null;
    public $updated_by = null;
    public $created_at = null;
    public $updated_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', array($this, 'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this, 'doBeforeUpdate'));
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
