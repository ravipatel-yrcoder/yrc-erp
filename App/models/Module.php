<?php
class Models_Module extends TinyPHP_ActiveRecord
{
    public $tableName = "modules";
    //protected $dbConnectionName = "platform_db";

    public $key = "";
    public $name = "";
    public $description = null;
    public $icon = null;
    public $sort_order = 0;
    public $is_active = 1;
    public $created_by = null;
    public $updated_by = null;
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
