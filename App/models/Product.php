<?php
class Models_Product extends TinyPHP_ActiveRecord
{
    public $tableName = "products";

    public $company_id = 0;
    public $master_id = 0;
    public $name = "";
    public $sku = null;
    public $description = null;
    public $base_uom_id = null;
    public $cost_price = null;
    public $current_cost = null;
    public $sale_price = null;
    public $stock_tracking_method = null;
    public $barcode = null;
    public $image_url = null;
    public $status = "active";
    public $created_by = null;
    public $created_at = null;
    public $updated_at = null;
    
    private $_master = null;
    private $_base_uom = null;
    
    protected $dbIgnoreFields = ["id"];

    public function init()
    {
        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this,'doBeforeUpdate'));

        $this->addLazyLoadProperty('master');
        $this->addLazyLoadProperty('base_uom');
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
        else if( $property === 'base_uom' )
        {
            if( is_null($this->_base_uom) ) {
                $this->_base_uom = new Models_Uom($this->base_uom_id);
            }
            return $this->_base_uom;
        }
    }

    protected function doBeforeCreate() {        

        $date = date("Y-m-d H:i:s");

        $this->created_at = $date;
        $this->updated_at = $date;
        
        return !$this->hasErrors();
    }

    protected function doBeforeUpdate() {

        $date = date("Y-m-d H:i:s");        
        $this->updated_at = $date;

        return !$this->hasErrors();
    }


    public function getTaxes($type) {

        if( !$this->id ) {
            return [];
        }

        $prodTax = new Models_ProductDefaultTax();
        return $prodTax->getAll([], ["product_id" => $this->id, "apply_on" => $type]);
    }


    /*
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

    private function isValidUom($uom_id) {

        $bind = [$uom_id, "active"];
        $sql = "SELECT COUNT(id) FROM uoms
                WHERE id=? AND status=?";
        
        $count = self::getVar($sql, $bind);

        return $count == 1;
    }


    private function canChangeUom() {

        if( !$this->id ) {return true;}

        $companyId = auth()->getCompanyId();

        $bind = [$companyId, $this->id];
        $sql = "SELECT COUNT(id) FROM inv_product_stock
                WHERE company_id=? AND product_id=?";
        
        $count = self::getVar($sql, $bind);

        return !$count >= 1;
    }


    private function isValidPurchaseTaxes($purchase_tax_ids) {

        $companyId = auth()->getCompanyId();

        $placeholderIds = implode(',', array_fill(0, count($purchase_tax_ids), '?'));
        $bind = array_merge($purchase_tax_ids, [$companyId, "purchase", "both", "active"]);
        
        $sql = "SELECT COUNT(id) FROM taxes
                WHERE id IN ($placeholderIds) AND company_id=? AND (apply_on=? OR apply_on=?) AND status=?";        
        $count = self::getVar($sql, $bind);

        return $count == count($purchase_tax_ids);
    }

    
    private function isValidSalesTaxes($sales_tax_ids) {

        $companyId = auth()->getCompanyId();

        $placeholderIds = implode(',', array_fill(0, count($sales_tax_ids), '?'));
        $bind = array_merge($sales_tax_ids, [$companyId, "sale", "both", "active"]);
        
        $sql = "SELECT COUNT(id) FROM taxes
                WHERE id IN ($placeholderIds) AND company_id=? AND (apply_on=? OR apply_on=?) AND status=?";        
        $count = self::getVar($sql, $bind);

        return $count == count($sales_tax_ids);
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

        if( empty($this->base_uom_id) || !$this->isValidUom($this->base_uom_id) ) {
            $this->addError(validationErrMsg("missing_or_invalid", "UOM"), "base_uom_id");
        } else {
            if( !$this->canChangeUom() ) {
                $this->addError(validationErrMsg("can_not_change_stock_exist", "UOM"), "base_uom_id");
            }
        }
    
        if( $this->sale_price && !isValidPrice($this->sale_price) ) {
            $this->addError(validationErrMsg("invalid_price", "Sale price"), "sale_price");
        }

        if( $this->cost_price && !isValidPrice($this->cost_price) ) {
            $this->addError(validationErrMsg("invalid_price", "Cost"), "cost_price");
        }

        if( !empty($this->purchase_taxes) && !$this->isValidPurchaseTaxes($this->purchase_taxes) ) {
            $this->addError(validationErrMsg("invalid", "Purchase Tax"), "purchase_taxes[]");
        }

        if( !empty($this->sales_taxes) && !$this->isValidSalesTaxes($this->sales_taxes) ) {
            $this->addError(validationErrMsg("invalid", "Sales Tax"), "sales_taxes[]");
        }        

        // Optionally, validate status
        if(!in_array($this->status, ['active','inactive','archived'])) {
            $this->addError(validationErrMsg("missing_or_invalid", "Status"), "status");
        }

        return !$this->hasErrors();
    }
    */

}
?>