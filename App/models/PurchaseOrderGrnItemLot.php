<?php
class Models_PurchaseOrderGrnItemLot extends TinyPHP_ActiveRecord
{
    public $tableName = "purchase_order_grn_item_lots";

    public $purchase_order_grn_item_id = 0;

    public $lot_number = "";
    public $vendor_lot_number = null;

    public $received_qty = 0.00;

    public $expiry_date = null;
    public $manufactured_date = null;

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