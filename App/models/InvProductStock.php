<?php
class Models_InvProductStock extends TinyPHP_ActiveRecord
{
    public $tableName = "inv_product_stock";

    public $company_id = 0;
    public $location_id = null;
    public $product_id = null;
    public $available_qty = 0;
    public $reserved_qty = 0;
    public $created_at = null;
    public $updated_at = null;

    protected $qty = 0;    
    
    protected $dbIgnoreFields = ["id", "qty"];

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

        $this->validateStockInfo();

        return !$this->hasErrors();
    }

    
    public function validateStockInfo() {

        $location = new Models_Location($this->location_id);
        if( $location->isEmpty || $location->company_id != auth()->getCompanyId() ) {
            $this->addError(validationErrMsg("missing_or_invalid", "Location"), "location_id");
        }

        $product = new Models_Product($this->product_id);
        if( $product->isEmpty || $product->company_id != auth()->getCompanyId() ) {
            $this->addError(validationErrMsg("missing_or_invalid", "Product"), "product_id");
        }

        if( !isNonNegativeNumeric($this->available_qty) ) {
            $this->addError(validationErrMsg("non_negative", "Available quantity"), "available_qty");
        }

        if( !isNonNegativeNumeric($this->reserved_qty) ) {
            $this->addError(validationErrMsg("non_negative", "Reserved quantity"), "reserved_qty");
        }

        return !$this->hasErrors();
    }   
}
?>