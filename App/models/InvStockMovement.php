<?php
class Models_InvStockMovement extends TinyPHP_ActiveRecord
{
    public $tableName = "inv_stock_movements";

    public $company_id = 0;
    public $location_id = null;
    public $product_id = null;
    public $movement_type = null;
    public $old_qty = 0;
    public $qty_change = 0;
    public $new_qty = 0;
    public $reference_type = null;
    public $reference_id = null;
    public $notes = null;
    public $created_by = null;
    public $created_at = null;
    
    protected $dbIgnoreFields = ["id"];

    public function init()
    {
        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
    }

    protected function doBeforeCreate() {        

        $companyId = auth()->getCompanyId();
        $date = date("Y-m-d H:i:s");

        $this->company_id = $companyId;
        $this->created_by = auth()->user()->id;
        $this->created_at = $date;
        
        return $this->validate();
    }

    public function validate() {
        
        return !$this->hasErrors();
    }   
}
?>