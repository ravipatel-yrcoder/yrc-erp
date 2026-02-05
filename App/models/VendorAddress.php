<?php
class Models_VendorAddress extends TinyPHP_ActiveRecord
{
    public $tableName = "vendor_addresses";

    public $company_id = 0;
    public $vendor_id = 0;
    public $address_type = null;
    public $attention = null;
    public $phone = null;
    public $address_line1 = null;
    public $address_line2 = null;
    public $city = null;
    public $state = null;
    public $postal_code = null;
    public $country = 'INR';
    public $is_default = 0;
    public $created_by = null;
    public $created_at = null;
    public $updated_at = null;
    
    protected $dbIgnoreFields = ["id"];

    public function init(){
        
        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this,'doBeforeUpdate'));
    }


    protected function doBeforeCreate() {

        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;
        $date = date("Y-m-d H:i:s");

        $this->company_id = $companyId;
        $this->created_by = $userId;
        $this->created_at = $date;
        $this->updated_at = $date;

        return !$this->hasErrors();
    }


    protected function doBeforeUpdate() {

        $date = date("Y-m-d H:i:s");
        $this->updated_at = $date;

        return !$this->hasErrors();
    }


}
?>