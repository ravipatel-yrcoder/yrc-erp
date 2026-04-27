<?php
class Models_Company extends TinyPHP_ActiveRecord
{
    public $tableName = "companies";
    //protected $dbConnectionName = "platform_db";

    public $name = "";
    public $email = null;
    public $phone = null;
    public $address = null;
    public $city = null;
    public $state = null;
    public $country = null;
    public $zipcode = null;
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
