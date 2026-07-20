<?php
class Models_PurchaseVendorQuote extends TinyPHP_ActiveRecord
{
    public $tableName = "purchase_vendor_quotes";

    public $vendor_id              = 0;
    public $inquiry_id             = null;
    public $quote_status           = "submitted";
    public $vendor_quote_number    = null;
    public $vendor_quote_date      = null;
    public $quote_validity_date    = null;
    public $payment_term_id        = null;
    public $payment_terms_snapshot = null;
    public $delivery_terms         = null;
    public $lead_time_days         = null;
    public $freight_charges        = 0;
    public $other_charges_label    = null;
    public $other_charges          = 0;
    public $subtotal               = 0;
    public $tax_total              = 0;
    public $grand_total            = 0;
    public $vendor_quote_notes     = null;
    public $created_by             = 0;
    public $created_at             = null;
    public $updated_at             = null;

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
