<?php
class Models_InvAdjustment extends TinyPHP_ActiveRecord
{
    public $tableName = "inv_adjustments";

    public $company_id = 0;
    public $location_id = 0;
    public $product_id = 0;    
    public $quantity = 0;
    public $adjustment_type = null;
    public $reason = null;
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