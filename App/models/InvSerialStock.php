<?php
class Models_InvSerialStock extends TinyPHP_ActiveRecord
{
    public $tableName = "inv_serial_stock";

    public $company_id = 0;
    public $location_id = 0;
    public $product_id = 0;
    public $serial_id = 0;
    public $reserved_for_document = null;
    public $created_at = null;
    public $updated_at = null;
    
    protected $dbIgnoreFields = ["id"];

    public function init()
    {
        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this,'doBeforeUpdate'));
    }

    protected function doBeforeCreate() {        

        $companyId = auth()->getCompanyId();
        $date = date("Y-m-d H:i:s");

        $this->company_id = $companyId;
        $this->created_at = $date;
        $this->updated_at = $date;
        
        return $this->validate();
    }

    protected function doBeforeUpdate() {

        $date = date("Y-m-d H:i:s");        
        $this->updated_at = $date;

        return $this->validate();
    }

    public function validate() {

        return !$this->hasErrors();
    }
       
}
?>