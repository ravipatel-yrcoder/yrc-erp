<?php
class Models_SubscriptionSeatEvent extends TinyPHP_ActiveRecord
{
    public $tableName = "subscription_seat_events";
    //protected $dbConnectionName = "platform_db";

    public $company_id = 0;
    public $subscription_id = 0;
    public $event_type = "";
    public $seats_before = 0;
    public $seats_after = 0;
    public $effective_at = null;
    public $period_start = null;
    public $period_end = null;
    public $prorated_amount = null;
    public $triggered_by = 0;
    public $created_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', array($this, 'doBeforeCreate'));
    }

    protected function doBeforeCreate() {
        $this->created_at = date("Y-m-d H:i:s");
        return !$this->hasErrors();
    }
}
?>
