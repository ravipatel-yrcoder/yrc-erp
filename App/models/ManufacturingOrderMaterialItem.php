<?php
class Models_ManufacturingOrderMaterialItem extends TinyPHP_ActiveRecord
{
    public $tableName = "manufacturing_order_material_items";

    public $company_id = 0;
    public $manufacturing_order_id = 0;
    public $product_id = 0;
    public $product_name = null;
    public $product_sku = null;
    public $product_uom_id = null;
    public $uom_code = null;
    public $planned_qty = 0;
    public $actual_qty = 0;
    public $notes = null;
    public $sort_order = 0;
    public $created_at = null;
    public $updated_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', [$this, 'doBeforeCreate']);
        $this->addListener('beforeUpdate', [$this, 'doBeforeUpdate']);
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
