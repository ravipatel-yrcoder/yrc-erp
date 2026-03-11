<?php
class Models_ProductMaster extends TinyPHP_ActiveRecord
{
    public $tableName = "product_masters";

    public $company_id = 0;
    public $name = "";
    public $description = null;
    public $category_id = null;
    public $type = "goods";
    public $structure_type = "simple";
    public $image_url = null;
    public $status = "active";
    public $created_by = null;
    public $created_at = null;
    public $updated_at = null;
    
    protected $dbIgnoreFields = ["id"];

    public function init()
    {
        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
        $this->addListener('beforeUpdate', array($this,'doBeforeUpdate'));            
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
    

    /*
    protected function doBeforeDelete() {

        $this->startTransaction();

        $companyId = auth()->getCompanyId();

        // get sku products
        $product = new Models_Product();
        $skuProducts = $product->getAll(["id"], ["company_id" => $companyId, "master_id" => $this->id]);        

        $rollback = false;

        // Delete sku products from `products` table        
        $product->delete("company_id={$companyId} AND master_id={$this->id}");
        if( !$product->getDeletedRows() ) {
            $this->addError("Failed to delete products");
            $rollback = true;
        }


        // Delete product taxes
        $skuProductIds = array_column($skuProducts, 'id');
        $prodTax = new Models_ProductTax();        
        $prodTax->delete("company_id={$companyId} AND product_id IN(".implode(",", $skuProductIds).")");
        if( !$prodTax->getDeletedRows() ) {
            $this->addError("Failed to delete product taxes");
            $rollback = true;
        }

        if( $rollback === true  ) {
            // rollback db transaction
            $this->rollback();
        }

        return !$this->hasErrors();
    }

    protected function doAfterDelete() {        
        $this->commit();
    }
    

    public function validate() {

        $this->validateProductInfo();

        return !$this->hasErrors();
    }

    
    private function isValidProdCategory(int $categoryId) {
        
        $prodCategory = new Models_ProdCategory($categoryId);
        if( !$prodCategory->isEmpty && $prodCategory->company_id == auth()->getCompanyId() ) {
            return true;
        }

        return false;
    }


    // Currently this is only handled for Simple Product, must handle variants whenever enabled variants support
    private function isUniqueSku($sku, $id=0) {
        
        $sku = strtolower(trim($sku));
        $companyId = auth()->getCompanyId();

        $bind = [$sku, $companyId, "archived"];
        $sql = "SELECT COUNT(id) FROM products
                WHERE lower(sku)=? AND company_id=? AND status <> ?";
        if( $id ) {
            $sql .=" AND id!=?";
            $bind[] = $id;
        }
        
        $count = self::getVar($sql, $bind);

        return !$count == 1;
    }

    private function isValidUom($base_uom_id) {

        $bind = [$base_uom_id, "active"];
        $sql = "SELECT COUNT(id) FROM uoms
                WHERE id=? AND status=?";
        
        $count = self::getVar($sql, $bind);

        return $count == 1;
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
        }

        if( !empty($this->category_id) ) {
            if( !$this->isValidProdCategory($this->category_id) ) {
                $this->addError(validationErrMsg("invalid", "Category"), "category_id");
            }
        }

        if(!empty($this->type) && !in_array($this->type, ['goods','service','combo'])) {
            $this->addError(validationErrMsg("invalid", "Product type"), "type");
        }

        if(!empty($this->structure_type) && !in_array($this->structure_type, ['simple','variable'])) {
            $this->addError(validationErrMsg("invalid", "Product structure type"), "structure_type");
        }

        if(!empty($this->stock_tracking_method) && !in_array($this->stock_tracking_method, ['none','quantity','lot','serial'])) {
            $this->addError(validationErrMsg("invalid", "Stock tracking method"), "stock_tracking_method");
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