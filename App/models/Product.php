<?php
class Models_Product extends TinyPHP_ActiveRecord
{
    public $tableName = "products";

    public $company_id = 0;
    public $master_id = 0;
    public $name = "";
    public $sku = null;
    public $description = null;
    public $cost_price = null;
    public $sale_price = null;
    public $barcode = null;
    public $image_url = null;
    public $status = "active";
    public $created_at = null;
    public $updated_at = null;
    
    private $_master = null;
    private $_stock_tracking_method = null;

    protected $dbIgnoreFields = ["id"];

    public function init()
    {
        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this,'doBeforeUpdate'));

        $this->addLazyLoadProperty('master');
        $this->addLazyLoadProperty('stock_tracking_method');
    }

    protected function lazyLoadProperty($property)
    {
        if( $property === 'master' )
        {
            if( is_null($this->_master) ) {
                $this->_master = new Models_ProductMaster($this->master_id);
            }
            return $this->_master;
        }
        else if( $property === 'stock_tracking_method' ) {
            
            if( is_null($this->_stock_tracking_method) ) {

                if( is_null($this->_master) ) {
                    $this->_master = new Models_ProductMaster($this->master_id);
                }

                $this->_stock_tracking_method = $this->_master->stock_tracking_method;
            }

            return $this->_stock_tracking_method;
        }
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

        $this->validateProductInfo();

        return !$this->hasErrors();
    }


    private function isUniqueSku($sku, $id=0) {
        
        $sku = strtolower(trim($sku));
        $companyId = auth()->getCompanyId();

        $bind = [$sku, $companyId, "archived"];
        $sql = "SELECT COUNT(id) FROM products
                WHERE lower(sku)=? AND company_id=? AND status<>?";
        if( $id ) {
            $sql .=" AND id!=?";
            $bind[] = $id;
        }
        
        $count = self::getVar($sql, $bind);

        return !$count == 1;
    }

    
    public function validateProductInfo() {

        if(empty($this->name)) {
            $this->addError(validationErrMsg("required", "Name"), "name");
        }

        if( !empty($this->sku) ) {
            if( !$this->isUniqueSku($this->sku, $this->id) ) {
                $this->addError(validationErrMsg("duplicate", "SKU"), "sku");
            }
        }
    
        if( $this->sale_price && !isValidPrice($this->sale_price) ) {
            $this->addError(validationErrMsg("invalid_price", "Sale price"), "sale_price");
        }

        if( $this->cost_price && !isValidPrice($this->cost_price) ) {
            $this->addError(validationErrMsg("invalid_price", "Cost"), "cost_price");
        }

        // Optionally, validate status
        if(!in_array($this->status, ['active','inactive','archived'])) {
            $this->addError(validationErrMsg("missing_or_invalid", "Status"), "status");
        }

        return !$this->hasErrors();
    }

}
?>