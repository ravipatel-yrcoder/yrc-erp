<?php
class Models_PurchaseOrderGrnMovement extends TinyPHP_ActiveRecord
{
    public $tableName = "purchase_order_grn_movements";

    public $company_id = 0;
    public $purchase_order_grn_id = 0;

    public $product_id = 0;
    public $location_id = 0;

    public $qty = 0.00;
    public $tracking_type = "quantity";

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