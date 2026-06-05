<?php
class Models_ManufacturingOrderOutput extends TinyPHP_ActiveRecord
{
    public $tableName = "manufacturing_order_outputs";

    public $company_id = 0;
    public $manufacturing_order_id = 0;
    public $output_qty = 0;
    public $destination_location_id = 0;
    public $notes = null;
    public $created_by = 0;
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
