<?php
class Models_PurchaseOrderGrnItem extends TinyPHP_ActiveRecord
{
    public $tableName = "purchase_order_grn_items";

    public $purchase_order_grn_id = 0;
    public $purchase_order_item_id = 0;
    public $product_id = 0;

    public $ordered_qty = 0.00;
    public $received_qty = 0.00;
    public $rejected_qty = 0.00;

    public $notes = null;
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