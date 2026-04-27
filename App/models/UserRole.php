<?php
class Models_UserRole extends TinyPHP_ActiveRecord
{
    public $tableName = "user_roles";
    
    public $company_id = 0;
    public $user_id = 0;
    public $role_id = 0;
    public $created_by = null;
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
