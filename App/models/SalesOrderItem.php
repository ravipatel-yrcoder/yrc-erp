<?php
class Models_SalesOrderItem extends TinyPHP_ActiveRecord
{
    public $tableName = "sales_order_items";

    public $sales_order_id = 0;
    public $product_id = 0;
    public $product_name = null;
    public $product_sku = null;
    public $tax_classification_type = null;
    public $tax_classification_code = null;
    public $description = null;
    public $ordered_qty = 0;
    public $delivered_qty = 0;
    public $returned_qty = 0;
    public $product_uom_id = null;
    public $uom_code = null;
    public $unit_price = 0;
    public $discount_amount = 0;
    public $discount_info = null;
    public $order_discount_allocated = 0;
    public $taxable_amount = 0;
    public $tax_amount = 0;
    public $tax_info = null;
    public $line_total = 0;
    public $line_status = "pending";
    public $created_by = null;
    public $created_at = null;
    public $updated_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', array($this, 'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this, 'doBeforeUpdate'));
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
