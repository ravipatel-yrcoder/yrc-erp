<?php
class Api_VendorpricesController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function service(): Service_VendorPricing {
        return new Service_VendorPricing(tenantContext());
    }

    public function indexAction(TinyPHP_Request $request) {

        if ($request->isMethod("get")) {
            return $this->handleList($request);
        }
        if ($request->isMethod("post")) {
            return $this->handleSave($request);
        }
        if ($request->isMethod("delete")) {
            return $this->handleDelete($request);
        }

        return response([], "Method not allowed", 405)->sendJson();
    }

    private function handleList(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;

        $columns = [
            'id'                  => 'vpp.id',
            'vendor_id'           => 'vpp.vendor_id',
            'vendor_name'         => 'v.display_name',
            'product_id'          => 'vpp.product_id',
            'product_name'        => 'p.name',
            'product_sku'         => 'p.sku',
            'vendor_product_name' => 'vpp.vendor_product_name',
            'vendor_product_code' => 'vpp.vendor_product_code',
            'min_qty'             => 'vpp.min_qty',
            'unit_price'          => 'vpp.unit_price',
            'discount_type'       => 'vpp.discount_type',
            'discount_amount'     => 'vpp.discount_amount',
            'lead_time_days'      => 'vpp.lead_time_days',
            'start_date'          => 'vpp.start_date',
            'end_date'            => 'vpp.end_date',
            'status'              => 'vpp.status',
            'created_at'          => 'vpp.created_at',
        ];

        $dataFetch = (new TinyPHP_DataFetch($request))
            ->table('vendor_product_prices AS vpp')
            ->joins('JOIN vendors v ON v.id = vpp.vendor_id JOIN products p ON p.id = vpp.product_id')
            ->columns($columns)
            ->where('vpp.company_id = ?', [$companyId]);

        $vendorId  = $request->getInput('vendor_id',  'Int',    0);
        $productId = $request->getInput('product_id', 'Int',    0);
        $status    = $request->getInput('status',     'String', '');

        if ($vendorId)  $dataFetch->where('vpp.vendor_id = ?',  [$vendorId]);
        if ($productId) $dataFetch->where('vpp.product_id = ?', [$productId]);
        if ($status)    $dataFetch->where('vpp.status = ?',     [$status]);

        $results = $dataFetch->fetch();
        return response($results)->sendJson();
    }

    private function handleSave(TinyPHP_Request $request) {

        $id      = $request->getInput("id", "Int", 0);
        $inputs  = $request->getInputs();
        $service = $this->service();

        try {
            if ($id) {
                $result = $service->update($id, $inputs);
                $msg    = "Price rule updated successfully";
                $code   = 200;
            } else {
                $result = $service->create($inputs);
                $msg    = "Price rule created successfully";
                $code   = 201;
            }
        } catch (Service_Exception $e) {
            return response([], $e->getMessage(), $e->getHttpStatusCode())->sendJson();
        }

        if ($result['success']) {
            return response($result['data'] ?? [], $msg, $code)->sendJson();
        }

        return response([], $id ? "Failed to update price rule" : "Failed to create price rule", 422)
            ->errors($service->getErrors())->sendJson();
    }

    private function handleDelete(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);

        try {
            $this->service()->delete($id);
        } catch (Service_Exception $e) {
            return response([], $e->getMessage(), $e->getHttpStatusCode())->sendJson();
        }

        return response([], "Price rule deleted successfully", 200)->sendJson();
    }

    public function formContextAction(TinyPHP_Request $request) {

        $id        = $request->getInput("id", "Int", 0);
        $companyId = tenantContext()->companyId;
        $ruleData  = null;

        if ($id > 0) {
            $rule = new Models_VendorProductPrice($id);
            if (!$rule->isEmpty && (int)$rule->company_id === (int)$companyId) {
                $ruleData = array_merge(['id' => $rule->id], $rule->toArray());
                $vendor  = new Models_Vendor($rule->vendor_id);
                $product = new Models_Product($rule->product_id);
                $ruleData['vendor_name']  = $vendor->isEmpty  ? '' : $vendor->display_name;
                $ruleData['product_name'] = $product->isEmpty ? '' : $product->name;
            }
        }

        return response(['rule' => $ruleData])->sendJson();
    }

    public function forVendorAction(TinyPHP_Request $request) {

        $vendorId = $request->getInput("id", "Int", 0);
        $data     = $this->service()->getForVendor($vendorId);
        return response($data)->sendJson();
    }

    public function forProductAction(TinyPHP_Request $request) {

        $productId = $request->getInput("id", "Int", 0);
        $data      = $this->service()->getForProduct($productId);
        return response($data)->sendJson();
    }
}
?>
