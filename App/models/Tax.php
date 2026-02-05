<?php
class Models_Tax extends TinyPHP_ActiveRecord
{
    public $tableName = "taxes";

    public $company_id = 0;
    public $name = "";
    public $code = null;
    public $tax_type = "percentage";
    public $rate = 0;
    public $price_included = 0;
    public $apply_on = "both";
    public $status = "active";
    public $description = null;
    public $created_by = 0;
    public $created_at = null;
    public $updated_at = null;
    
    protected $dbIgnoreFields = ["id"];

    public function init(){
        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
    }

    protected function doBeforeCreate() {

        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;
        $date = date("Y-m-d H:i:s");

        $this->company_id = $companyId;
        $this->created_by = $userId;
        $this->created_at = $date;
        $this->updated_at = $date;
        
        return !$this->hasErrors();
    }

}
?>