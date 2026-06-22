<?php
class Models_ReturnItem extends TinyPHP_ActiveRecord
{
    public $tableName = "return_items";

    public $company_id = 0;
    public $return_id = 0;
    public $reference_item_id = null;
    public $product_id = 0;
    public $product_uom_id = null;
    public $uom_code = null;
    public $unit_price = null;
    public $product_name = null;
    public $product_sku = null;
    public $taxable_amount = 0;
    public $tax_amount = 0;
    public $line_total = 0;
    public $return_qty = 0;
    public $return_disposition_id = 0;
    public $follow_up_status = "not_required";
    public $follow_up_processed_qty = 0;
    public $return_reason_id = null;
    public $notes = null;
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
