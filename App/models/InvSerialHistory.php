<?php
class Models_InvSerialHistory extends TinyPHP_ActiveRecord
{
    public $tableName = "inv_serial_history";

    public $company_id = 0;
    public $product_id = 0;
    public $serial_id = 0;
    public $changed_field = "";
    public $old_value = "";
    public $new_value = "";
    public $reason = "";
    public $reference_type = null;
    public $reference_id = null;
    public $changed_by = null;
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
        $this->changed_by = auth()->user()->id;
        $this->created_at = $date;
        
        return $this->validate();
    }

    public function validate() {

        return !$this->hasErrors();
    }
       
}
?>