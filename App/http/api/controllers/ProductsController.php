<?php
class Api_ProductsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function serviceProduct(): Service_Product {
        return new Service_Product(tenantContext());
    }

    // Products (SKU)
    public function indexAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            return $this->handleGet($request);
        }
        else if( $request->isMethod("post") ) {
            return $this->handlePost($request);
        }
        else if( $request->isMethod("delete") ) {
            return $this->handleDelete($request);
        }
    }


    private function handleGet(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;

        $columns = [
            "id" => "p.id", "master_id" => "p.master_id", "name" => "p.name", "description" => "p.description", "image_url" => "p.image_url", "sale_price" =>"p.sale_price","status" =>"p.status","stock_tracking_method" => "p.stock_tracking_method","created_at" => "p.created_at",
            "category" => "c.name",
        ];

        $dataFetch = new TinyPHP_DataFetch($request);
        $results = $dataFetch
        ->table("products AS p")
        ->joins("INNER JOIN product_masters AS pm ON pm.id=p.master_id
        LEFT JOIN product_categories c ON pm.category_id=c.id")
        ->columns($columns)
        ->where("pm.company_id = ? AND pm.status <> ?", [$companyId, "archived"])
        ->fetch();

        return response($results)->sendJson();
    }


    private function handlePost(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $action = "create";
        if( $id ) {
            $action = "update";
        }

        $inputs = $request->getInputs();

        $service = $this->serviceProduct();
        if( $action === "update" ) {
            $response = $service->update($id, $inputs);
        } else {
            $response = $service->create($inputs);
        }

        if( $response["success"] ) {
            
            $responseMessage = $action === "update" ? "Product updated successfully" : "Product created successfully";
            $responseCode = $action === "update" ? 200 : 201;
            
            return response($response["data"], $responseMessage, $responseCode)->sendJson();
        } else {
            
            $responseMessage = $action === "update" ? "Failed to update product" : "Failed to create product";
            
            return response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
        }
    }


    private function handleDelete(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
            
        $service = $this->serviceProduct();
        $response = $service->delete($id);

        if( $response["success"] ) {
            return response($response["data"], "Product deleted successfully", 200)->sendJson();
        } else {
            return response([], "Failed to delete product", 422)->errors($response["errors"])->sendJson();
        }
    }


    public function searchAction(TinyPHP_Request $request) {
        $q = trim($request->getInput("q", "String", ""));
        $service = $this->serviceProduct();
        $results = $service->search($q);
        return response($results)->sendJson();
    }

    public function formContextAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);

        $service = $this->serviceProduct();
        $data = $service->getFormContext($id);

        return response($data)->sendJson();
    }

    public function importAction(TinyPHP_Request $request) {

        $file = $_FILES['file'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return response([], "No file uploaded or upload error occurred", 422)->sendJson();
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            return response([], "Only CSV files are supported", 422)->sendJson();
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            return response([], "File size must not exceed 2MB", 422)->sendJson();
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            return response([], "Failed to read uploaded file", 422)->sendJson();
        }

        $expectedHeaders = ['product', 'sku', 'description', 'uom', 'category', 'product type', 'classification code', 'tracking method', 'sales price', 'sales taxes', 'cost', 'purchase taxes'];

        $headerRow = fgetcsv($handle);
        if (!$headerRow) {
            fclose($handle);
            return response([], "CSV file is empty", 422)->sendJson();
        }

        $normalizedHeaders = array_map(fn($h) => strtolower(trim($h)), $headerRow);
        if ($normalizedHeaders !== $expectedHeaders) {
            fclose($handle);
            return response([], "CSV columns do not match the expected template. Please use the provided import template.", 422)->sendJson();
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $row = array_pad($row, 12, '');
            if (count(array_filter($row, fn($v) => trim($v) !== '')) === 0) {
                continue;
            }
            $rows[] = $row;
        }
        fclose($handle);

        if (empty($rows)) {
            return response([], "CSV file contains no data rows", 422)->sendJson();
        }

        if (count($rows) > 2000) {
            return response([], "Import file cannot exceed 2000 rows", 422)->sendJson();
        }

        $service = $this->serviceProduct();
        $result  = $service->import($rows);

        if ($result['success']) {
            return response($result['data'], "Products imported successfully", 201)->sendJson();
        }

        return response([], "Import validation failed", 422)->errors($result['errors'])->sendJson();
    }
}
