<?php
class Models_CustomerContact extends TinyPHP_ActiveRecord
{
    public $tableName = "customer_contacts";

    public $company_id = 0;
    public $customer_id = 0;
    public $first_name = null;
    public $last_name = null;
    public $title = null;
    public $email = null;
    public $phone = null;
    public $is_primary = 0;
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
