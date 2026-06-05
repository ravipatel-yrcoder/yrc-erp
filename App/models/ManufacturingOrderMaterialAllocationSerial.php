<?php
class Models_ManufacturingOrderMaterialAllocationSerial extends TinyPHP_ActiveRecord
{
    public $tableName = "manufacturing_order_material_allocation_serials";

    public $company_id = 0;
    public $allocation_item_id = 0;
    public $manufacturing_order_id = 0;
    public $product_id = 0;
    public $serial_id = 0;
    public $manufacturing_order_output_id = null;
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
