<?php
class Models_ExtraCharge extends TinyPHP_ActiveRecord
{
    public $tableName = 'extra_charges';

    public $company_id          = 0;
    public $name                = '';
    public $applies_to          = 'both';
    public $default_tax_id      = null;
    public $sac_code            = null;
    public $refund_policy       = 'manual';
    public $affects_landed_cost = 0;
    public $is_active           = 1;
    public $sort_order          = 0;
    public $created_at          = null;
    public $updated_at          = null;

    protected $dbIgnoreFields = ['id'];

    public function init() {
        $this->addListener('beforeCreate', [$this, 'doBeforeCreate']);
        $this->addListener('beforeUpdate', [$this, 'doBeforeUpdate']);
    }

    protected function doBeforeCreate() {
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
        return !$this->hasErrors();
    }

    protected function doBeforeUpdate() {
        $this->updated_at = date('Y-m-d H:i:s');
        return !$this->hasErrors();
    }
}
?>
