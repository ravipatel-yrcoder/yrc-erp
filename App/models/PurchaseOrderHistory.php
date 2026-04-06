<?php
class Models_PurchaseOrderHistory extends TinyPHP_ActiveRecord
{
    public $tableName = "purchase_order_history";

    public $company_id = 0;
    public $purchase_order_id = 0;
    public $log_type = "";
    public $title = "";
    public $reference_type = null;
    public $reference_id = null;
    public $meta = null;
    public $created_by = 0;
    public $created_at = null;
    
    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
    }

    protected function doBeforeCreate() {        

        //$companyId = auth()->getCompanyId();
        //$userId = auth()->user()->id;
        $date = date("Y-m-d H:i:s");

        //$this->company_id = $companyId;
        //$this->created_by = $userId;
        $this->created_at = $date;        
        
        return !$this->hasErrors();
    }
}
?>