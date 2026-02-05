<?php
class Models_PurchaseOrderGrnItemSerial extends TinyPHP_ActiveRecord
{
    public $tableName = "purchase_order_grn_item_serials";

    public $purchase_order_grn_item_id = 0;
    public $company_id = 0;

    public $serial_number = "";
    public $status = "available";

    public $created_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init()
    {
        $this->addListener('beforeCreate', [$this, 'doBeforeCreate']);
    }

    protected function doBeforeCreate()
    {
        $this->created_at = date("Y-m-d H:i:s");
        return !$this->hasErrors();
    }
}