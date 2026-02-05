<?php
class Models_PurchaseOrderGrnHistory extends TinyPHP_ActiveRecord
{
    public $tableName = "purchase_order_grn_history";

    public $company_id = 0;
    public $purchase_order_grn_id = 0;

    public $event_type = "";
    public $changed_field = null;
    public $old_value = null;
    public $new_value = null;
    public $notes = null;

    public $created_by = 0;
    public $created_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init()
    {
        $this->addListener('beforeCreate', [$this, 'doBeforeCreate']);
    }

    protected function doBeforeCreate()
    {
        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;
        
        $this->company_id = $companyId;
        $this->created_by = $userId;
        $this->created_at = date("Y-m-d H:i:s");
                
        return !$this->hasErrors();
    }
}