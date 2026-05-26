<?php
class Models_User extends TinyPHP_ActiveRecord
{
    public $tableName = "users";
    //protected $dbConnectionName = "platform_db";

    public $company_id = 0;
    public $first_name = "";
    public $last_name = null;
    public $name = "";
    public $email = "";
    public $phone = null;
    public $password = "";
    public $status = "active";
    public $is_company = 0;
    public $email_verified_at = null;
    public $email_verification_token = null;
    public $email_verification_expires_at = null;
    public $last_login_at = null;
    public $created_by = null;
    public $created_at = null;
    public $updated_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init()
    {
        $this->addListener('beforeCreate', array($this, 'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this, 'doBeforeUpdate'));
    }

    protected function doBeforeCreate() {
        $date = date("Y-m-d H:i:s");
        $this->created_at = $date;
        $this->updated_at = $date;
        return $this->validate();
    }

    protected function doBeforeUpdate() {
        $this->updated_at = date("Y-m-d H:i:s");
        return $this->validate();
    }

    public function validate() {
        return !$this->hasErrors();
    }
}
?>
