<?php
class Models_PurchaseInquiryItem extends TinyPHP_ActiveRecord
{
    public $tableName = "purchase_inquiry_items";

    public $inquiry_id     = 0;
    public $product_id     = 0;
    public $product_name   = "";
    public $product_sku    = "";
    public $description    = null;
    public $required_qty   = 0;
    public $product_uom_id = 0;
    public $uom_code       = "";
    public $sort_order     = 0;
    public $notes          = null;
    public $created_at     = null;
    public $updated_at     = null;

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
