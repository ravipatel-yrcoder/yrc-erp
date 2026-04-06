<?php
class Models_SalesDeliveryHistory extends TinyPHP_ActiveRecord
{
    public $tableName = "sales_delivery_history";

    public $company_id = 0;
    public $sales_delivery_id = 0;
    public $log_type = "";
    public $title = null;
    public $reference_type = null;
    public $reference_id = null;
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
