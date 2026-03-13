<?php
class Models_SalesOrderHistory extends TinyPHP_ActiveRecord
{
    public $tableName = "sales_order_history";

    public $company_id = 0;
    public $sales_order_id = 0;
    public $activity_type = "";
    public $title = null;
    public $meta = null;
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
