<?php
class Models_PurchaseOrderGrn extends TinyPHP_ActiveRecord
{
    public $tableName = "purchase_order_grns";

    public $company_id = 0;
    public $purchase_order_id = 0;
    public $grn_number = "";
    public $status = "draft";

    public $posted_date = null;
    public $posted_by = null;
    public $in_transit_date = null;

    public $location_id = 0;

    public $vendor_document_number = null;
    public $vendor_document_date = null;

    public $notes = null;

    public $created_by = 0;
    public $created_at = null;
    public $updated_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init()
    {
        $this->addListener('beforeCreate', [$this, 'doBeforeCreate']);
        $this->addListener('beforeUpdate', [$this, 'doBeforeUpdate']);
    }

    protected function doBeforeCreate()
    {
        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;
        $date = date("Y-m-d H:i:s");

        $this->company_id = $companyId;
        $this->created_by = $userId;
        $this->created_at = $date;
        $this->updated_at = $date;
        
        return !$this->hasErrors();
    }

    protected function doBeforeUpdate()
    {
        $this->updated_at = date("Y-m-d H:i:s");
        return !$this->hasErrors();
    }
}