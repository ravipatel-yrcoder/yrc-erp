<?php
class Models_VendorProductPrice extends TinyPHP_ActiveRecord
{
    public $tableName = "vendor_product_prices";

    public $company_id          = 0;
    public $vendor_id           = 0;
    public $product_id          = 0;
    public $vendor_product_name = null;
    public $vendor_product_code = null;
    public $min_qty             = '1.0000';
    public $unit_price          = '0.0000';
    public $discount_type       = 'percentage';
    public $discount_amount     = '0.0000';
    public $lead_time_days      = null;
    public $start_date          = null;
    public $end_date            = null;
    public $status              = 'active';
    public $created_by          = null;
    public $created_at          = null;
    public $updated_at          = null;

    protected $dbIgnoreFields = ["id"];

    public function init() {
        $this->addListener('beforeCreate', [$this, 'doBeforeCreate']);
        $this->addListener('beforeUpdate', [$this, 'doBeforeUpdate']);
    }

    protected function doBeforeCreate() {
        $now = date("Y-m-d H:i:s");
        $this->created_at = $now;
        $this->updated_at = $now;
        return !$this->hasErrors();
    }

    protected function doBeforeUpdate() {
        $this->updated_at = date("Y-m-d H:i:s");
        return !$this->hasErrors();
    }
}
?>
