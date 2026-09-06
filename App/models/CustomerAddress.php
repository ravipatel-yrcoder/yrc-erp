<?php
class Models_CustomerAddress extends TinyPHP_ActiveRecord
{
    public $tableName = "customer_addresses";

    public $company_id = 0;
    public $customer_id = 0;
    public $address_type = null;
    public $label = null;
    public $attention = null;
    public $phone = null;
    public $address_line1 = null;
    public $address_line2 = null;
    public $city = null;
    public $state = null;
    public $postal_code = null;
    public $country = 'IN';
    public $gstin = null;
    public $is_default = 0;
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
}
?>
