<?php
class Models_RolePermission extends TinyPHP_ActiveRecord
{
    public $tableName = "role_permissions";

    public $role_id = 0;
    public $permission_id = 0;
    public $data_scope = "all";
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
