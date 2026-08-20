<?php
class Models_SalesProformaInvoice extends TinyPHP_ActiveRecord
{
    public $tableName = "sales_proforma_invoices";

    public $company_id = 0;
    public $proforma_number = "";
    public $sales_order_id = 0;
    public $customer_id = 0;
    public $proforma_date = null;
    public $valid_until = null;
    public $billing_address_snapshot = null;
    public $shipping_address_snapshot = null;
    public $payment_terms = null;
    public $notes = null;
    public $invoice_terms = null;
    public $invoice_declaration = null;
    public $subtotal = 0;
    public $item_discount_total = 0;
    public $subtotal_after_item_discount = 0;
    public $order_discount_amount = 0;
    public $discount_total = 0;
    public $discount_info = null;
    public $tax_amount = 0;
    public $round_off_amount = 0;
    public $adjustment_label = null;
    public $adjustment_amount = 0;
    public $grand_total = 0;
    public $status = "draft";
    public $is_outdated = 0;
    public $outdated_at = null;
    public $sent_at = null;
    public $created_by = 0;
    public $created_at = null;
    public $updated_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', array($this, 'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this, 'doBeforeUpdate'));
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
?>
