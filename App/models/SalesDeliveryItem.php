<?php
class Models_SalesDeliveryItem extends TinyPHP_ActiveRecord
{
    public $tableName = "sales_delivery_items";

    public $sales_delivery_id = 0;
    public $sales_order_item_id = 0;
    public $product_id = 0;
    public $description = null;
    public $dispatched_qty = 0;
    public $uom_code = null;
    public $unit_cost = null;
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
