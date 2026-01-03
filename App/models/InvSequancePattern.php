<?php
class Models_InvSequancePatterm extends TinyPHP_ActiveRecord
{
    public $tableName = "inv_sequence_patterns";

    public $company_id = 0;
    public $product_id = null;
    public $name = null;
    public $pattern = "";
    public $last_number = 0;
    public $reset_period = 'none';
    public $sequence_type = 'both';
    public $padding = 6;
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

        if( $this->product_id )
        {
            $product = new Models_Product($this->product_id);
            if( $product->isEmpty || $product->company_id != auth()->getCompanyId() ) {
                $this->addError(validationErrMsg("missing_or_invalid", "Product"), "product_id");
            }
        }

        return !$this->hasErrors();
    }    
}
?>