<?php
class Models_SalesDeliveryItemSerial extends TinyPHP_ActiveRecord
{
    public $tableName = "sales_delivery_item_serials";

    public $company_id = 0;
    public $sales_delivery_id = 0;
    public $sales_delivery_item_id = 0;
    public $serial_id = 0;
    public $serial_number = "";
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
