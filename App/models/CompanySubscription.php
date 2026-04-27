<?php
class Models_CompanySubscription extends TinyPHP_ActiveRecord
{
    public $tableName = "company_subscriptions";
    //protected $dbConnectionName = "platform_db";

    public $company_id = 0;
    public $plan_id = 0;
    public $is_current = 1;
    public $status = "trial";
    public $billing_cycle = "monthly";
    public $agreed_base_price = "0.0000";
    public $agreed_extra_user_price = "0.0000";
    public $free_users_included = 3;
    public $purchased_extra_seats = 0;
    public $razorpay_customer_id = null;
    public $razorpay_subscription_id = null;
    public $trial_ends_at = null;
    public $pilot_until = null;
    public $current_period_start = null;
    public $current_period_end = null;
    public $notes = null;
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
