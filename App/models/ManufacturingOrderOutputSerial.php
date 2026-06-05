<?php
class Models_ManufacturingOrderOutputSerial extends TinyPHP_ActiveRecord
{
    public $tableName = "manufacturing_order_output_serials";

    public $company_id = 0;
    public $output_id = 0;
    public $manufacturing_order_id = 0;
    public $serial_id = 0;

    protected $dbIgnoreFields = ["id"];

    public function init() {}
}
