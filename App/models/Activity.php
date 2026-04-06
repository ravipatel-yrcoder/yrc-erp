<?php
class Models_Activity extends TinyPHP_ActiveRecord
{
    public $tableName = "activities";

    public $company_id = 0;
    public $related_type = "";
    public $related_id = 0;
    public $type = "";
    public $summary = "";
    public $due_date = null;
    public $due_time = null;
    public $assigned_to = null;
    public $note = null;
    public $is_done = 0;
    public $done_at = null;
    public $outcome = null;
    public $created_by = null;
    public $created_at = null;
    public $updated_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this,'doBeforeUpdate'));
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
