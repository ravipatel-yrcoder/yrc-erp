<?php
class Models_ReturnDisposition extends TinyPHP_ActiveRecord
{
    public $tableName = "return_dispositions";

    public $company_id = 0;
    public $name = "";
    public $description = null;
    public $bucket = "";
    public $is_default = 0;
    public $is_active = 1;
    public $sort_order = 0;
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
