<?php
class Models_Feature extends TinyPHP_ActiveRecord
{
    public $tableName = "features";
    //protected $dbConnectionName = "platform_db";

    public $module_id = 0;
    public $key = "";
    public $name = "";
    public $description = null;
    public $is_active = 1;
    public $access_level = "subscription";
    public $is_scopeable = 0;
    public $feature_category = null;
    public $sort_order = 0;
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
