<?php
class Service_Inv_Stock extends Service_Base {
    
    public function reserve(array $items) {

        $companyId  = $this->context->companyId;
        foreach ($items as $item) {

            $prodId = $item["prod_id"];
            $locationId = $item["location_id"];
            $qty = $item["qty"];

            $product = new Models_Product($prodId);
            if (empty($product->stock_tracking_method) || $product->stock_tracking_method === 'none') {
                continue;
            }

            $stock = new Models_InvProductStock();
            $stock->fetchByProperty(["company_id", "location_id", "product_id"], [$companyId, $locationId, $prodId]);

            if ($stock->isEmpty) {
                throw new Service_Exception("Stock record not found for product: " . $product->name, 422);
            }

            $stock->reserved_qty = (float) $stock->reserved_qty + (float) $qty;
            if (!$stock->update()) {
                throw new Service_Exception("Failed to reserve stock for product: " . $product->name);
            }
        }
    }



    public function release(array $items) {

        $companyId  = $this->context->companyId;

        foreach ($items as $item) {

            $prodId = $item["prod_id"];
            $locationId = $item["location_id"];
            $qty = $item["qty"];

            $product = new Models_Product($prodId);
            if (empty($product->stock_tracking_method) || $product->stock_tracking_method === 'none') {
                continue;
            }

            $stock = new Models_InvProductStock();
            $stock->fetchByProperty(
                ["company_id", "location_id", "product_id"],
                [$companyId, $locationId, $prodId]
            );

            if ($stock->isEmpty) {
                continue; // nothing to release
            }

            $stock->reserved_qty = max(0, (float) $stock->reserved_qty - (float) $qty);
            if (!$stock->update()) {
                throw new Service_Exception("Failed to release reserved stock for product: " . $product->name);
            }
        }
    }
}