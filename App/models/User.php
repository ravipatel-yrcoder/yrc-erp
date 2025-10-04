<?php
class Models_User extends TinyPHP_ActiveRecord
{
    public $tableName = "users";
    
    public $name = "";
    public $email = "";
    public $role = "user";
    public $password = "";
    public $status = "active";
    public $email_verified_at = NULL;
    public $last_login_at = NULL;
    public $created_at = NULL;
    public $updated_at = NULL;
    
    public function init()
    {
        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this,'doBeforeUpdate'));
    }


    protected function doBeforeCreate() {
        return $this->validate();
    }


    protected function doBeforeUpdate() {
        return $this->validate();
    }

    public function validate() {
        
        $this->validateUserInfo();
        return !$this->hasErrors();
    }   

    public function validateUserInfo() {
        
        if($this->firstName == "")
        {
            $this->addError("First name is required");
        }

        if($this->lastName == "")
        {
            $this->addError("Last name is required");
        }

        if($this->email == "")
        {
            $this->addError("Email is required");
        }

        return !$this->hasErrors();
    }    
}
?>