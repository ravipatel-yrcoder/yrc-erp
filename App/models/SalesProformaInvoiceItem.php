<?php
class Models_SalesProformaInvoiceItem extends TinyPHP_ActiveRecord
{
    public $tableName = "sales_proforma_invoice_items";

    public $sales_proforma_invoice_id = 0;
    public $sales_order_item_id = null;
    public $product_id = 0;
    public $product_name = null;
    public $sku = null;
    public $description = null;
    public $quantity = 0;
    public $product_uom_id = null;
    public $uom_code = null;
    public $unit_price = 0;
    public $discount_amount = 0;
    public $discount_info = null;
    public $taxable_amount = 0;
    public $tax_amount = 0;
    public $tax_info = null;
    public $line_total = 0;
    public $sort_order = 0;
    public $created_at = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', array($this, 'doBeforeCreate'));
    }

    protected function doBeforeCreate() {
        $this->created_at = date("Y-m-d H:i:s");
        return !$this->hasErrors();
    }
}
?>
