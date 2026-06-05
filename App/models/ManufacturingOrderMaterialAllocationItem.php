<?php
class Models_ManufacturingOrderMaterialAllocationItem extends TinyPHP_ActiveRecord
{
    public $tableName = "manufacturing_order_material_allocation_items";

    public $company_id = 0;
    public $allocation_id = 0;
    public $manufacturing_order_id = 0;
    public $material_item_id = 0;
    public $product_id = 0;
    public $allocated_qty = 0;
    public $created_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', [$this, 'doBeforeCreate']);
    }

    protected function doBeforeCreate() {
        $this->created_at = date("Y-m-d H:i:s");
        return !$this->hasErrors();
    }
}
