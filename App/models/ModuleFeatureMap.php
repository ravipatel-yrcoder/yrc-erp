<?php
class Models_ModuleFeatureMap extends TinyPHP_ActiveRecord
{
    public $tableName = "module_feature_map";
    //protected $dbConnectionName = "platform_db";

    public $module_id = 0;
    public $feature_id = 0;
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
