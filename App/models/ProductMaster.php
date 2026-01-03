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
    public $cost_price = null;
    public $sale_price = null;
    public $stock_tracking_method = null;
    public $image_url = null;
    public $status = "active";
    public $created_at = null;
    public $updated_at = null;
    
    // virtual properties
    protected $sku = null;
    protected $image = [];    
    protected $attributes = [];

    protected $dbIgnoreFields = ["id","sku", "image", "attributes"];

    public function init()
    {
        $this->addListener('beforeCreate', array($this,'doBeforeCreate'));
        $this->addListener('afterCreate', array($this,'doAfterCreate'));

        $this->addListener('beforeUpdate', array($this,'doBeforeUpdate'));
        $this->addListener('afterUpdate', array($this,'doAfterUpdate'));
        
        $this->addListener('beforeDelete', array($this,'doBeforeDelete'));
        $this->addListener('afterDelete', array($this,'doAfterDelete'));
    }

    protected function doBeforeCreate() {

        // DB Transaction
        $this->startTransaction();

        $this->company_id = auth()->getCompanyId();

        $date = date("Y-m-d H:i:s");
        $this->created_at = $date;
        $this->updated_at = $date;
        
        if( $this->validate() ) {            

            // upload image
            if( $this->image ) {
                                
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $validate = Helpers_FileUpload::validate($this->image, $allowedTypes, 1);
                if( $validate["valid"] === true )
                {
                    $file = Helpers_FileUpload::save($this->image, ROOT_PATH."/public/uploads/".$this->company_id."/".date("Y")."/".date("m"));
                    $this->image_url = $file["url"];
                }
                else
                {
                    $this->addError($validate["error"], "image_url");
                }
            }
        }

        if( $this->hasErrors() ) {
            $this->rollback();
        }


        return !$this->hasErrors();
    }

    protected function doAfterCreate() {

        $masterProdId = $this->id;

        // create product
        if( $this->structure_type === "simple" )
        {
            $product = new Models_Product();
            $product->master_id = $masterProdId;
            $product->name = $this->name;
            $product->sku = $this->sku;
            $product->description = $this->description;
            $product->cost_price = $this->cost_price;
            $product->sale_price = $this->sale_price;
            $product->image_url = $this->image_url;
            $prodId = $product->create();
            if( !$prodId ) {
                $this->addErrors($product->getErrors());
            }
        }
        else
        {
            // Need to handle product variant creation logic
        }

        $this->hasErrors() ? $this->rollback() : $this->commit();        
    }


    protected function doBeforeUpdate() {

        // DB Transaction
        $this->startTransaction();
        

        $date = date("Y-m-d H:i:s");
        $this->updated_at = $date;
        
        if( $this->validate() ) {

            // upload image
            if( $this->image ) {
                                
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $validate = Helpers_FileUpload::validate($this->image, $allowedTypes, 1);
                if( $validate["valid"] === true )
                {
                    $file = Helpers_FileUpload::save($this->image, ROOT_PATH."/public/uploads/".$this->company_id."/".date("Y")."/".date("m"));
                    $this->image_url = $file["url"];
                }
                else
                {
                    $this->addError($validate["error"], "image_url");
                }
            }
        }

        if( $this->hasErrors() ) {
            $this->rollback();
        }


        return !$this->hasErrors();
    }


    protected function doAfterUpdate() {

        $masterProdId = $this->id;

        // create product
        if( $this->structure_type === "simple" )
        {
            $product = new Models_Product();
            $product->fetchByProperty(["master_id"], [$masterProdId]);
            if( !$product->isEmpty )
            {
                $product->name = $this->name;
                $product->sku = $this->sku;
                $product->description = $this->description;
                $product->cost_price = $this->cost_price;
                $product->sale_price = $this->sale_price;
                $product->image_url = $this->image_url;
                $updated = $product->update();
                if( $updated ) {
                    $this->addErrors($product->getErrors());
                }
            }            
        }
        else
        {
            // Need to handle product variant creation logic
        }

        $this->hasErrors() ? $this->rollback() : $this->commit();        
    }


    protected function doBeforeDelete() {

        $this->startTransaction();

        // Delete sku products from `products` table
        $product = new Models_Product();
        $product->delete("master_id={$this->id}");
        if( !$product->getDeletedRows() ) {
            
            $this->addError("Failed to delete products");

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


    public function validateProductInfo() {

        if(empty($this->name)) {
            $this->addError(validationErrMsg("required", "Name"), "name");
        }

        if( !empty($this->sku) ) {
            if( !$this->isUniqueSku($this->sku, $this->id) ) {
                $this->addError(validationErrMsg("duplicate", "SKU"), "sku");
            }
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

        if( $this->sale_price && !isValidPrice($this->sale_price) ) {
            $this->addError(validationErrMsg("invalid_price", "Sale price"), "sale_price");
        }

        if( $this->cost_price && !isValidPrice($this->cost_price) ) {
            $this->addError(validationErrMsg("invalid_price", "Cost"), "cost_price");
        }

        if(!empty($this->stock_tracking_method) && !in_array($this->stock_tracking_method, ['none','quantity','lot','serial'])) {
            $this->addError(validationErrMsg("invalid", "Stock tracking method"), "stock_tracking_method");
        }

        // Optionally, validate status
        if(!in_array($this->status, ['active','inactive','archived'])) {
            $this->addError(validationErrMsg("missing_or_invalid", "Status"), "status");
        }

        return !$this->hasErrors();
    }    
}
?>