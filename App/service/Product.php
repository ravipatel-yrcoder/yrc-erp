<?php
class Service_Product extends Service_Base {

    
    private function getSkuProductOrFail(int $id) : Models_Product {

        // validate purchase order and permissions
        $product = new Models_Product($id);
        if( $product->isEmpty ) {
            throw new Service_Exception("The requested product was not found", 404);
        }

        if( $product->company_id != $this->context->companyId ) {
            throw new Service_Exception("You do not have permission to access this product", 403);
        }

        return $product;
    }


    private function isValidProdCategory($categoryId) {
        
        $prodCategory = new Models_ProdCategory($categoryId);
        if( !$prodCategory->isEmpty && $prodCategory->company_id == $this->context->companyId ) {
            return true;
        }

        return false;
    }


    // Currently this is only handled for Simple Product, must handle variants whenever enabled variants support
    private function isUniqueSku($sku, $id=0) {
        
        $sku = strtolower(trim($sku));
        
        $bind = [$sku, $this->context->companyId, "archived"];
        $sql = "SELECT COUNT(id) FROM products
                WHERE lower(sku)=? AND company_id=? AND status <> ?";
        if( $id ) {
            $sql .=" AND id!=?";
            $bind[] = $id;
        }
        
        $count = $this->db->fetchVar($sql, $bind);

        return $count == 0;
    }


    private function isValidUom($baseUomId) {

        $bind = [$baseUomId, "active"];
        $sql = "SELECT COUNT(id) FROM uoms
                WHERE id=? AND status=?";
        
        $count = $this->db->fetchVar($sql, $bind);

        return $count == 1;
    }

    private function isValidPurchaseTaxes($purchase_tax_ids) {

        $placeholderIds = implode(',', array_fill(0, count($purchase_tax_ids), '?'));
        $bind = array_merge($purchase_tax_ids, [$this->context->companyId, "purchase", "both", "active"]);
        
        $sql = "SELECT COUNT(id) FROM taxes
                WHERE id IN ($placeholderIds) AND company_id=? AND (apply_on=? OR apply_on=?) AND status=?";        
        $count = $this->db->fetchVar($sql, $bind);

        return $count == count($purchase_tax_ids);
    }

    
    private function isValidSalesTaxes($sales_tax_ids) {

        $placeholderIds = implode(',', array_fill(0, count($sales_tax_ids), '?'));
        $bind = array_merge($sales_tax_ids, [$this->context->companyId, "sale", "both", "active"]);
        
        $sql = "SELECT COUNT(id) FROM taxes
                WHERE id IN ($placeholderIds) AND company_id=? AND (apply_on=? OR apply_on=?) AND status=?";

        $count = $this->db->fetchVar($sql, $bind);

        return $count == count($sales_tax_ids);
    }

    private function validatePayload(array $payload) {

        $id = $payload['id'] ?? "";
        $structureType = $payload['structure_type'] ?? ""; 
        $name = $payload['name'] ?? ""; 
        $sku = $payload['sku'] ?? "";
        $baseUomId = $payload['base_uom_id'] ?? 0; 
        $categoryId = $payload['category_id'] ?? 0;
        $productType = $payload['type'] ?? "";
        $stockTrackingMethod = $payload['stock_tracking_method'] ?? "";
        $salePrice = (float) $payload['sale_price'] ?? "";
        $costPrice = (float) $payload['cost_price'] ?? "";
        $salesTaxes = $payload['sales_taxes'] ?? [];
        $purchaseTaxes = $payload['purchase_taxes'] ?? [];
        $status = $payload['status'] ?? "";

        if( empty($structureType) || !in_array($structureType, ['simple','variable']) ) {
            $this->addError(validationErrMsg("missing_or_invalid", "Structure type"), "structure_type");
        }

        if( empty($name) ) {
            $this->addError(validationErrMsg("required", "Name"), "name");
        }

        if( !empty($sku) && !$this->isUniqueSku($sku, $id) ) {
            $this->addError(validationErrMsg("duplicate", "SKU"), "sku");
        }

        if( empty($baseUomId) || !$this->isValidUom($baseUomId) ) {
            $this->addError(validationErrMsg("missing_or_invalid", "UOM"), "base_uom_id");
        }

        if( !empty($categoryId) && !$this->isValidProdCategory($categoryId) ) {
            $this->addError(validationErrMsg("invalid", "Category"), "category_id");
        }

        if( empty($productType) || !in_array($productType, ['goods','service','combo'])) {
            $this->addError(validationErrMsg("missing_or_invalid", "Product type"), "type");
        }

        if( empty($stockTrackingMethod) || !in_array($stockTrackingMethod, ['none','quantity','lot','serial'])) {
            $this->addError(validationErrMsg("missing_or_invalid", "Stock tracking method"), "stock_tracking_method");
        }

        if( $salePrice && !isValidPrice($salePrice) ) {
            $this->addError(validationErrMsg("invalid_price", "Sale price"), "sale_price");
        }

        if( $costPrice && !isValidPrice($costPrice) ) {
            $this->addError(validationErrMsg("invalid_price", "Cost"), "cost_price");
        }

        if( !empty($salesTaxes) && !$this->isValidSalesTaxes($salesTaxes) ) {
            $this->addError(validationErrMsg("invalid", "Sales Tax"), "sales_taxes[]");
        }        

        if( !empty($purchaseTaxes) && !$this->isValidPurchaseTaxes($purchaseTaxes) ) {
            $this->addError(validationErrMsg("invalid", "Purchase Tax"), "purchase_taxes[]");
        }

        
        if(!in_array($status, ['active','inactive','archived'])) {
            $this->addError(validationErrMsg("missing_or_invalid", "Status"), "status");
        }



        if( $structureType === "variable" ) {

            $this->addError("Variable products are not supported yet", "structure_type");

            // validate sku items for variable products
            // logic is not implemented yet
        }
    }

    private function hasBaseUomChanged(Models_Product $product, $baseUomId) {
        return (int) $baseUomId !== (int) $product->base_uom_id;
    }

    private function validateImmutableFields(Models_Product $product, array $payload) {

        $uomChanged        = $this->hasBaseUomChanged($product, $payload['base_uom_id'] ?? 0);
        $trackStockChanged = isset($payload['stock_tracking_method']) && $payload['stock_tracking_method'] !== $product->stock_tracking_method;

        if( $uomChanged || $trackStockChanged ) {

            $companyId = $this->context->companyId;
            $productId = $product->id;

            // Check existing stock records
            $stockCount = (int) $this->db->fetchVar(
                "SELECT COUNT(id) FROM inv_product_stock WHERE company_id = ? AND product_id = ?",
                [$companyId, $productId]
            );

            // Count active (draft/confirmed) SO + PO lines — completed and cancelled orders do not block changes
            $openOrderCount = (int) $this->db->fetchVar(
                "SELECT SUM(cnt) FROM (
                     SELECT COUNT(soi.id) AS cnt
                     FROM sales_order_items soi
                     INNER JOIN sales_orders so ON so.id = soi.sales_order_id
                     WHERE soi.product_id = ? AND so.company_id = ? AND so.status IN ('draft', 'confirmed')
                     UNION ALL
                     SELECT COUNT(poi.id) AS cnt
                     FROM purchase_order_items poi
                     INNER JOIN purchase_orders po ON po.id = poi.purchase_order_id
                     WHERE poi.product_id = ? AND po.company_id = ? AND po.status IN ('draft', 'confirmed')
                 ) AS open_lines",
                [$productId, $companyId, $productId, $companyId]
            );

            if( $uomChanged && $stockCount > 0 ) {
                $this->addError("UOM cannot be changed because inventory records exist", "base_uom_id");
            }

            if( $trackStockChanged ) {

                $currentMethod = $product->stock_tracking_method;
                $newMethod     = $payload['stock_tracking_method'];
                $isEnabling    = ($currentMethod === 'none' || $currentMethod === null);

                if( $isEnabling ) {
                    // none → tracked: only block if open orders exist
                    if( $openOrderCount > 0 ) {
                        $this->addError("Inventory tracking cannot be enabled because this product has active orders", "stock_tracking_method");
                    }
                } else {
                    // tracked → none or between tracked methods: block if stock records OR open orders exist
                    if( $stockCount > 0 ) {
                        $errorField = ($newMethod === 'none') ? "track_inventory" : "stock_tracking_method";
                        $this->addError("Tracking method cannot be changed because inventory records exist", $errorField);
                    } elseif( $openOrderCount > 0 ) {
                        $errorField = ($newMethod === 'none') ? "track_inventory" : "stock_tracking_method";
                        $this->addError("Tracking method cannot be changed because this product has active orders", $errorField);
                    }
                }
            }
        }
    }


    private function validateAndUploadImage(array $image) {
        
        if( $image ) {
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $validate = Helpers_FileUpload::validate($image, $allowedTypes, 1);
            if( $validate["valid"] === true ) {

                $file = Helpers_FileUpload::save($image, ROOT_PATH."/public/uploads/".$this->context->companyId."/".date("Y")."/".date("m"));
                return $file["url"];
            }
            else
            {
                $this->addError($validate["error"], "image_url");
            }
        } else {
            $this->addError("Missing image", "image_url");
        }

        return false;
    }


    private function validateDeletionAllowed(int $productId) {

        $bind1 = [$this->context->companyId, $productId];
        $sql1 = "SELECT count(id) FROM inv_adjustments
                WHERE
                company_id=? AND product_id=?";
        $count1 = $this->db->fetchVar($sql1, $bind1);
        if( $count1 > 0 ) {
            throw new Service_Exception("Product cannot be deleted because related records exist");
        }

        $bind2 = [$productId];
        $sql2 = "SELECT count(id) FROM purchase_order_items
                WHERE
                product_id=?";
        $count2 = $this->db->fetchVar($sql2, $bind2);
        if( $count2 > 0 ) {
            throw new Service_Exception("Product cannot be deleted because related records exist");
        }
    }


    private function createMasterProduct(array $payload) {

        $master = new Models_ProductMaster();
        $master->fillFromArray($payload);
        $master->company_id = $this->context->companyId;
        $master->created_by = $this->context->userId;
        $masterId = $master->create();
        if( !$masterId ) {
            throw new Exception("Failed to create product");
        }

        return $masterId;
    }


    private function createSkuProduct(int $masterProdId, array $payload) {

        $product = new Models_Product();
        $product->fillFromArray($payload);
        $product->master_id = $masterProdId;
        $product->company_id = $this->context->companyId;
        $product->created_by = $this->context->userId;
        $skuProdId = $product->create();
        if( !$skuProdId ) {
            throw new Service_Exception("Failed to create product");
        }
        
        return $skuProdId;
    }


    private function saveProductTaxes(int $productId, string $taxType, $taxes) {

        $companyId = $this->context->companyId;
        $userId = $this->context->userId;

        $prodTax = new Models_ProductTax();
        $savedProdTaxes = $prodTax->getAll(["tax_id"], ["company_id" => $companyId, "product_id" => $productId, "apply_on" => $taxType]);
        $savedProdTaxIds = [];
        foreach($savedProdTaxes as $savedProdTax) {
            $savedProdTaxIds[] = $savedProdTax->tax_id;
        }

        $idsToCreate = array_values(array_diff($taxes, $savedProdTaxIds));
        $idsToDelete = array_values(array_diff($savedProdTaxIds, $taxes));
        
        //$idsToUpdate = array_values(array_intersect($savedProdTaxIds, $taxes));

        // create
        foreach($idsToCreate as $taxId) {
            $prodTax = new Models_ProductTax();
            $prodTax->company_id = $companyId;
            $prodTax->product_id = $productId;
            $prodTax->tax_id = $taxId;
            $prodTax->apply_on = $taxType;
            $prodTax->created_by = $userId;
            $taxId = $prodTax->create();
            if( !$taxId ) {                        
                throw new Service_Exception("Failed to save {$taxType} tax");
            }
        }

        // delete
        if( $idsToDelete ) {
            $prodTax = new Models_ProductTax();
            $prodTax->delete("product_id={$productId} AND tax_id IN(".implode(",", $idsToDelete).") AND apply_on='{$taxType}'");
            if( !$prodTax->getDeletedRows() ) {
                throw new Service_Exception("Failed to save {$taxType} tax");
            }
        }
    }


    // UOM Change validation should applied before calling this method
    private function saveBaseUomAsProductUoms(int $productId, int $baseUomId, int $oldBaseUomId=0) {

        $companyId = $this->context->companyId;
        $userId = $this->context->userId;
        $uom = new Models_Uom($baseUomId);


        if( $uom->isEmpty ) {
            throw new Service_Exception("Failed to save UOM configuration");
        }

        
        // Delete existing base uom from product_uoms if exist
        if( $oldBaseUomId ) {
            $productUom = new Models_ProductUom();
            $productUom->fetchByProperty(["company_id", "product_id", "base_uom_id", "is_base"], [$companyId, $productId, $oldBaseUomId, 1]);
            if( !$productUom->isEmpty ) {
                
                // delete
                $productUom->delete();
                if( !$productUom->getDeletedRows() > 0 ) {
                    throw new Service_Exception("Failed to save UOM configuration");
                }
            }
        }        


        // create new base uom in product_uoms
        $productUom = new Models_ProductUom();
        $productUom->company_id = $companyId;
        $productUom->product_id = $productId;
        $productUom->name = $uom->name;        
        $productUom->base_uom_id = $uom->id;
        $productUom->conversion_factor = 1;
        $productUom->status = 'active';
        $productUom->is_base = 1;
        $productUom->created_by = $userId;
        if( !$productUom->create() ) {
            throw new Service_Exception("Failed to save UOM configuration");
        }
    }


    /**
     * Retrive add/edit form context data
     */
    public function getFormContext(int $skuProdId) : array {
        
        $companyId = $this->context->companyId;

        $productDetails = [];
        if( $skuProdId ) {

            $product = $this->getSkuProductOrFail($skuProdId);

            $purchaseTaxes = array_column($product->getTaxes("purchase"), "tax_id");
            $salesTaxes = array_column($product->getTaxes("sale"), "tax_id");

            $productDetails = array_merge(['id' => $skuProdId, 'sales_taxes' => $salesTaxes, 'purchase_taxes' => $purchaseTaxes, "category_id" => $product->master->category_id], $product->toArray());
        }

        $categories = Models_ProdCategory::getCategories($companyId, "tree");
        
        $uom = new Models_Uom();
        $baseUoms = $uom->getAll(["id", "name", "code"], ["status" => "active"]);

        $tax = new Models_Tax();
        $taxes = $tax->getAll(["id", "name", "code", "apply_on"], ["company_id" => $companyId, "status" => "active"]);

        $purchaseTaxes = $salesTaxes = [];
        foreach($taxes as $taxRow) {
            $applyOn = strtoupper($taxRow->apply_on);
            
            if( $applyOn === "PURCHASE" || $applyOn === "BOTH" ) {
                $purchaseTaxes[] = $taxRow;
            }
            
            if( strtoupper($applyOn) === "SALES" || $applyOn === "BOTH" ) {
                $salesTaxes[] = $taxRow;
            }
        }

        $data = [
            'categories' => $categories,
            'base_uoms' => $baseUoms,
            'purchase_taxes' => $purchaseTaxes,
            'sales_taxes' => $salesTaxes,
            'product_details' => $productDetails,
        ];

        return $data;
    }


    public function create(array $payload) {

        if (!$this->context->canDo('products', 'write')) {
            throw new Service_Exception('You do not have permission to create products', 403);
        }

        // Validate incoming data
        $this->validatePayload($payload);


        // validate and upload image
        if( !empty($payload["image"]) ) {
            $imgUrl = $this->validateAndUploadImage($payload["image"]);
            if( $imgUrl !== false ) {
                $payload["image_url"] = $imgUrl;
            }
        }        

        if ($this->hasErrors()) {
            return [
                "success" => false,
                "errors"  => $this->getErrors()
            ];
        }


        // Begin transaction
        $this->db->startTransaction();

        try {

            // create master product if require
            $masterId = $payload["master_id"] ?? 0;
            if( empty($masterId) ) {
                $masterId = $this->createMasterProduct($payload);
            }

            // create sku product            
            $skuProductId = $this->createSkuProduct($masterId, $payload);


            // save product uoms
            $baseUomId = $payload['base_uom_id'] ?? 0; 
            $this->saveBaseUomAsProductUoms($skuProductId, $baseUomId);
            

            // save purchase taxes
            $purchaseTaxes = $payload["purchase_taxes"] ?? [];
            $this->saveProductTaxes($skuProductId, "purchase", $purchaseTaxes);            


            // create sales taxes
            $salesTaxes = $payload["sales_taxes"] ?? [];
            $this->saveProductTaxes($skuProductId, "sale", $salesTaxes);


            // Commit
            $this->db->commit();

            return [
                "success" => true,
                "data" => [
                    "masterId" => $masterId,
                    "id" => $skuProductId,
                ],
            ];

        } catch (Exception $e) {

            $this->db->rollBack();
            throw $e;
        }
    }


    public function update(int $prodId, array $payload) {

        if (!$this->context->canDo('products', 'write')) {
            throw new Service_Exception('You do not have permission to update products', 403);
        }

        $product = $this->getSkuProductOrFail($prodId);

        // Validate payload
        $this->validatePayload($payload);

        // Validate UOM & Tracking Method fields
        $this->validateImmutableFields($product, $payload);
        
        // validate and upload image
        if( !empty($payload["image"]) ) {
            $imgUrl = $this->validateAndUploadImage($payload["image"]);
            if( $imgUrl !== false ) {
                $payload["image_url"] = $imgUrl;
            }
        }

        if ($this->hasErrors()) {
            return [
                "success" => false,
                "errors"  => $this->getErrors()
            ];
        }

        // Begin transaction
        $this->db->startTransaction();
        
        try {

            $baseUomId = $payload['base_uom_id'] ?? 0;
            $oldBaseUomId = $product->base_uom_id;
            $uomChanged = $this->hasBaseUomChanged($product, $baseUomId);

            // update master product
            $masterProduct = $product->master;
            $masterProduct->fillFromArray($payload, ["company_id", "created_by", "created_at"]);
            if( !$masterProduct->update() ) {
                throw new Service_Exception("Failed to update product");
            }

            $product->fillFromArray($payload, ["company_id", "master_id", "created_by", "created_at"]);
            if( !$product->update() ) {
                throw new Service_Exception("Failed to update product");
            }


            // update base uom in product_uoms if base uom changed
            if( $uomChanged ) {
                $this->saveBaseUomAsProductUoms($prodId, $baseUomId, $oldBaseUomId);
            }            


            // save purchase taxes
            $purchaseTaxes = $payload["purchase_taxes"] ?? [];
            $this->saveProductTaxes($prodId, "purchase", $purchaseTaxes);            


            // create sales taxes
            $salesTaxes = $payload["sales_taxes"] ?? [];
            $this->saveProductTaxes($prodId, "sale", $salesTaxes);



            // Commit
            $this->db->commit();

            return [
                "success" => true,
                "data" => [],
            ];

        } catch (Exception $e) {

            $this->db->rollBack();
            throw $e;
        }


    }


    public function delete(int $prodId) {

        if (!$this->context->canDo('products', 'delete')) {
            throw new Service_Exception('You do not have permission to delete products', 403);
        }

        $product = $this->getSkuProductOrFail($prodId);

        $this->validateDeletionAllowed($prodId);

        // Begin transaction
        $this->db->startTransaction();

        try {

            $masterProdId = $product->master_id;

            $product->delete();
            if( !$product->getDeletedRows() > 0 ) {
                throw new Service_Exception("Failed to delete product");
            }

            // delete master product
            $masterProd = new Models_ProductMaster($masterProdId);
            $masterProd->delete();
            if( !$masterProd->getDeletedRows() > 0 ) {
                throw new Service_Exception("Failed to delete product");
            }

            // Delete product taxes
            $prodTax = new Models_ProductTax();        
            $prodTax->delete("company_id={$this->context->companyId} AND product_id={$prodId}");
            if( !$prodTax->getDeletedRows() ) {
                throw new Service_Exception("Failed to delete product. Associated tax records could not be removed");              
            }

            // Commit
            $this->db->commit();

            return [
                "success" => true,
                "data" => [],
            ];

        } catch (Exception $e) {

            $this->db->rollBack();
            throw $e;
        }

    }

    public function search(string $q): array
    {
        if (trim($q) === '') {
            return [];
        }

        $companyId = $this->context->companyId;
        $like = '%' . $q . '%';

        return $this->db->fetchAll(
            "SELECT p.id, p.name
             FROM products AS p
             INNER JOIN product_masters AS pm ON pm.id = p.master_id
             WHERE pm.company_id = ?
               AND pm.status <> 'archived'
               AND p.stock_tracking_method IN ('quantity', 'lot', 'serial')
               AND p.name LIKE ?
             ORDER BY p.name ASC
             LIMIT 20",
            [$companyId, $like]
        );
    }
}