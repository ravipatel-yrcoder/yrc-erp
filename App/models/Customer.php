<?php
class Models_Customer extends TinyPHP_ActiveRecord
{
    public $tableName = "customers";

    public $company_id = 0;
    public $customer_code = null;
    public $customer_group_id = null;
    public $customer_type = "company";
    public $salutation = null;
    public $first_name = null;
    public $last_name = null;
    public $company_name = null;
    public $display_name = null;
    public $email = null;
    public $phone = null;
    public $website = null;
    public $pan = null;
    public $gstin = null;
    public $gst_treatment = "b2b";
    public $currency_code = 'INR';
    public $payment_term_id = null;
    public $credit_limit = null;
    public $price_list_id = null;
    public $notes = null;
    public $status = "active";
    public $created_by = null;
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

    public function getBillingAddress() {
        $address = [];
        if ($this->id) {
            $addr = new Models_CustomerAddress();
            $addr->fetchByProperty(["customer_id", "address_type"], [$this->id, "billing"]);
            if (!$addr->isEmpty) {
                $address = array_merge(['id' => $addr->id], $addr->toArray());
            }
        }
        return $address;
    }

    public function getShippingAddress() {
        $address = [];
        if ($this->id) {
            $addr = new Models_CustomerAddress();
            $addr->fetchByProperty(["customer_id", "address_type"], [$this->id, "shipping"]);
            if (!$addr->isEmpty) {
                $address = array_merge(['id' => $addr->id], $addr->toArray());
            }
        }
        return $address;
    }
}
?>
