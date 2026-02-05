<?php
class Models_PurchaseOrderItem extends TinyPHP_ActiveRecord
{
    public $tableName = "purchase_order_items";

    public $purchase_order_id = 0;
    public $product_id = 0;
    public $description = null;
    public $ordered_qty = 1;
    public $received_qty = 0;
    public $unit_price = 0;
    public $discount_amount = 0;
    public $discount_info = NULL;
    public $tax_amount = 0;
    public $tax_info = null;
    public $line_total = 0;
    public $expense_account_id = null;
    public $created_by = null;
    public $created_at = null;
    public $updated_at = null;
    
    protected $dbIgnoreFields = ["id"];

    public function init() {

        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this,'doBeforeUpdate'));
    }

    protected function doBeforeCreate() {        

        $userId = auth()->user()->id;
        $date = date("Y-m-d H:i:s");

        $this->created_by = $userId;
        $this->created_at = $date;
        $this->updated_at = $date;
        
        return !$this->hasErrors();
    }

    protected function doBeforeUpdate() {

        $date = date("Y-m-d H:i:s");        
        $this->updated_at = $date;

        return !$this->hasErrors();
    }
}
?>