<?php
class Models_PurchaseOrderGrnHistory extends TinyPHP_ActiveRecord
{
    public $tableName = "purchase_order_grn_history";

    public $company_id = 0;
    public $purchase_order_grn_id = 0;

    public $activity_type = "";
    public $title = "";
    public $description = null;
    public $reference_type = null;
    public $reference_id = null;
    public $meta = null;

    public $created_by = 0;
    public $created_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', [$this, 'doBeforeCreate']);
    }

    protected function doBeforeCreate()
    {
        $this->created_at = date("Y-m-d H:i:s");                
        return !$this->hasErrors();
    }
}