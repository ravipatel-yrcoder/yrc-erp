<?php
class Models_PurchaseVendorQuoteItem extends TinyPHP_ActiveRecord
{
    public $tableName = "purchase_vendor_quote_items";

    public $inquiry_id      = null;
    public $quote_id        = 0;
    public $inquiry_item_id = null;
    public $product_id      = 0;
    public $can_supply      = 1;
    public $unit_price      = 0;
    public $discount_amount = 0;
    public $discount_info   = null;
    public $tax_amount      = 0;
    public $tax_info        = null;
    public $line_total      = 0;
    public $notes           = null;
    public $created_at      = null;
    public $updated_at      = null;

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
