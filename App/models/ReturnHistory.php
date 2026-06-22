<?php
class Models_ReturnHistory extends TinyPHP_ActiveRecord
{
    public $tableName = "return_history";

    public $company_id = 0;
    public $return_id = 0;
    public $log_type = "";
    public $title = "";
    public $reference_type = null;
    public $reference_id = null;
    public $meta = null;
    public $created_by = 0;
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
