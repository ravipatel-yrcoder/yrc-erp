<?php
class Models_CrmLead extends TinyPHP_ActiveRecord
{
    public $tableName = "crm_leads";

    public $company_id = 0;
    public $lead_code = "";
    public $title = null;
    public $stage_id = null;
    public $status = "active";
    public $probability = 10;
    public $salutation = null;
    public $first_name = "";
    public $last_name = null;
    public $company_name = null;
    public $display_name = "";
    public $job_title = null;
    public $email = null;
    public $phone = null;
    public $website = null;
    public $address_line1 = null;
    public $address_line2 = null;
    public $city = null;
    public $state = null;
    public $postal_code = null;
    public $country = "IN";
    public $expected_revenue = null;
    public $won_revenue = null;
    public $expected_close_date = null;
    public $source = null;
    public $priority = "medium";
    public $tags = null;
    public $assigned_to = null;
    public $customer_id = null;
    public $converted_at = null;
    public $lost_reason = null;
    public $closed_at = null;
    public $notes = null;
    public $created_by = null;
    public $updated_by = null;
    public $created_at = null;
    public $updated_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this,'doBeforeUpdate'));
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
