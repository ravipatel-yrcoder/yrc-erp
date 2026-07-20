<?php
class Models_PurchaseInquiryVendor extends TinyPHP_ActiveRecord
{
    public $tableName = "purchase_inquiry_vendors";

    public $inquiry_id              = 0;
    public $vendor_id               = 0;
    public $vendor_name             = "";
    public $vendor_contact_id       = null;
    public $vendor_contact_name     = null;
    public $vendor_contact_email    = null;
    public $vendor_address_snapshot = null;
    public $po_id                   = null;
    public $status                  = "pending";
    public $sent_at                 = null;
    public $responded_at            = null;
    public $internal_notes          = null;
    public $created_at              = null;
    public $updated_at              = null;

    protected $dbIgnoreFields = ["id"];

    public function init()
    {
        $this->addListener('beforeCreate', [$this, 'doBeforeCreate']);
        $this->addListener('beforeUpdate', [$this, 'doBeforeUpdate']);
    }

    protected function doBeforeCreate()
    {
        $date = date("Y-m-d H:i:s");
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
?>
