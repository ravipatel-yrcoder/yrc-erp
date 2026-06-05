<?php
class Models_ManufacturingOrderMaterialAllocation extends TinyPHP_ActiveRecord
{
    public $tableName = "manufacturing_order_material_allocations";

    public $company_id = 0;
    public $manufacturing_order_id = 0;
    public $status = "active";
    public $notes = null;
    public $created_by = 0;
    public $created_at = null;
    public $cancelled_by = null;
    public $cancelled_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', [$this, 'doBeforeCreate']);
    }

    protected function doBeforeCreate() {
        $this->created_at = date("Y-m-d H:i:s");
        return !$this->hasErrors();
    }
}
