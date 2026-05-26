<?php
class Models_Company extends TinyPHP_ActiveRecord
{
    public $tableName = "companies";
    //protected $dbConnectionName = "platform_db";

    public $name = "";
    public $legal_name = null;
    public $email = null;
    public $phone = null;
    public $website = null;
    public $address = null;
    public $city = null;
    public $state = null;
    public $country = null;
    public $zipcode = null;
    public $gstin = null;
    public $pan = null;
    public $tan = null;
    public $cin = null;
    public $logo_path = null;
    public $signature_path = null;
    public $contact_name = null;
    public $contact_email = null;
    public $contact_phone = null;
    public $status = "active";
    public $timezone = "UTC";
    public $currency = "INR";
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
