<?php
class Api_PurchaseOrdersController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function servicePurchaseOrder(): Service_Po_Order {
        return new Service_Po_Order(tenantContext());
    }

    private function servicePOGrn(): Service_Po_Grn {
        return new Service_Po_Grn(tenantContext());
    }

    public function indexAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            return $this->list($request);
        }
        else if( $request->isMethod("post") ) {
            return $this->save($request);
        }        
    }


    public function formContextAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);

        $service = $this->servicePurchaseOrder();
        $data = $service->getFormContext($id);

        return response($data)->sendJson();
    }


    public function entityAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            return $this->show($request);
        }
        else if( $request->isMethod("post") ) {
            return $this->save($request);
        }        
    }


    public function statusAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            
            $id = $request->getInput("id", "Int", 0);    
        
            $service = $this->servicePurchaseOrder();
            $data = $service->getStatus($id);

            return response($data)->sendJson();
        }
        else if( $request->isMethod("post") ) {
            return $this->updateStatus($request);
        }
    }


    public function receiveFormContextAction(TinyPHP_Request $request) {
        
        $poId = $request->getInput("id", "Int", 0);
        
        $grnService = $this->servicePOGrn();
        $data = $grnService->getCreateFormContext($poId);

        return response($data)->sendJson();
    }


    public function historyAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);

        $service = $this->servicePurchaseOrder();
        $data = $service->getHistory($id);

        return response($data)->sendJson();
    }


    public function generateEmailPdfAction(TinyPHP_Request $request) {
        $id     = $request->getInput("id", "Int", 0);
        $result = $this->servicePurchaseOrder()->generateEmailPdf($id);
        return response($result)->sendJson();
    }


    public function sendEmailAction(TinyPHP_Request $request) {

        $id     = $request->getInput("id", "Int", 0);
        $inputs = $request->getInputs();

        $service = $this->servicePurchaseOrder();
        $result  = $service->sendEmail($id, $inputs);

        if ($result["success"]) {
            return response([], "Email sent successfully", 200)->sendJson();
        }

        return response([], "Failed to send email", 422)->errors($result["errors"])->sendJson();
    }


    private function list(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;

        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = [
            "id"                => "po.id",
            "po_number"         => "po.po_number",
            "order_date"        => "po.order_date",
            "vendor"            => "v.display_name",
            "reference"         => "po.reference",
            "status"            => "po.status",
            "exp_delivery_date" => "po.expected_delivery_date",
            "amount"            => "SUM(poi.line_total)",
            "currency_code"     => "po.currency_code",
            "created_by_name"   => "u.name",
        ];

        $dataFetch
            ->table("purchase_orders AS po")
            ->joins("LEFT JOIN vendors AS v ON po.vendor_id = v.id
                LEFT JOIN purchase_order_items AS poi ON po.id = poi.purchase_order_id
                LEFT JOIN users AS u ON u.id = po.created_by")
            ->columns($columns)
            ->ignoreSearch(['amount'])
            ->where("po.company_id = ?", [$companyId])
            ->groupBy("po.id");

        // Status filter
        $filterStatus = $request->getInput("filter_status", "array", []);
        if (!empty($filterStatus)) {
            $validStatuses = ['draft', 'confirmed', 'partially_received', 'received', 'cancelled'];
            $filterStatus  = array_values(array_filter($filterStatus, fn($s) => in_array($s, $validStatuses, true)));
            if (!empty($filterStatus)) {
                $placeholders = implode(',', array_fill(0, count($filterStatus), '?'));
                $dataFetch->where("po.status IN ({$placeholders})", $filterStatus);
            }
        }

        // Vendor filter
        $filterVendorId = $request->getInput("filter_vendor_id", "Int", 0);
        if ($filterVendorId > 0) {
            $dataFetch->where("po.vendor_id = ?", [$filterVendorId]);
        }

        // Expected delivery filter
        $filterExpDeliveryPreset = $request->getInput("filter_exp_delivery_preset", "String", "");
        $filterExpDeliveryFrom   = $request->getInput("filter_exp_delivery_from",   "String", "");
        $filterExpDeliveryTo     = $request->getInput("filter_exp_delivery_to",     "String", "");
        if ($filterExpDeliveryPreset) {
            $today = date('Y-m-d');
            switch ($filterExpDeliveryPreset) {
                case 'overdue':
                    $dataFetch->where("po.expected_delivery_date < ? AND po.expected_delivery_date IS NOT NULL AND po.status NOT IN ('received','cancelled')", [$today]);
                    break;
                case 'due_today':
                    $dataFetch->where("po.expected_delivery_date = ? AND po.expected_delivery_date IS NOT NULL", [$today]);
                    break;
                case 'due_this_week':
                    $weekEnd = date('Y-m-d', strtotime('sunday this week'));
                    $dataFetch->where("po.expected_delivery_date BETWEEN ? AND ? AND po.expected_delivery_date IS NOT NULL", [$today, $weekEnd]);
                    break;
                case 'due_this_month':
                    $monthEnd = date('Y-m-t');
                    $dataFetch->where("po.expected_delivery_date BETWEEN ? AND ? AND po.expected_delivery_date IS NOT NULL", [$today, $monthEnd]);
                    break;
                case 'custom':
                    if ($filterExpDeliveryFrom && $filterExpDeliveryTo) {
                        $dataFetch->where("po.expected_delivery_date BETWEEN ? AND ? AND po.expected_delivery_date IS NOT NULL", [$filterExpDeliveryFrom, $filterExpDeliveryTo]);
                    } elseif ($filterExpDeliveryFrom) {
                        $dataFetch->where("po.expected_delivery_date >= ? AND po.expected_delivery_date IS NOT NULL", [$filterExpDeliveryFrom]);
                    } elseif ($filterExpDeliveryTo) {
                        $dataFetch->where("po.expected_delivery_date <= ? AND po.expected_delivery_date IS NOT NULL", [$filterExpDeliveryTo]);
                    }
                    break;
            }
        }

        // Order date filter
        $filterOrderDatePreset = $request->getInput("filter_order_date_preset", "String", "");
        $filterOrderDateFrom   = $request->getInput("filter_order_date_from",   "String", "");
        $filterOrderDateTo     = $request->getInput("filter_order_date_to",     "String", "");
        if ($filterOrderDatePreset) {
            $today = date('Y-m-d');
            switch ($filterOrderDatePreset) {
                case 'today':
                    $dataFetch->where("po.order_date = ?", [$today]);
                    break;
                case 'this_week':
                    $dataFetch->where("po.order_date BETWEEN ? AND ?", [date('Y-m-d', strtotime('monday this week')), $today]);
                    break;
                case 'this_month':
                    $dataFetch->where("po.order_date BETWEEN ? AND ?", [date('Y-m-01'), $today]);
                    break;
                case 'last_month':
                    $dataFetch->where("po.order_date BETWEEN ? AND ?", [
                        date('Y-m-01', strtotime('first day of last month')),
                        date('Y-m-t',  strtotime('last day of last month')),
                    ]);
                    break;
                case 'last_3_months':
                    $dataFetch->where("po.order_date BETWEEN ? AND ?", [date('Y-m-d', strtotime('-3 months')), $today]);
                    break;
                case 'custom':
                    if ($filterOrderDateFrom && $filterOrderDateTo) {
                        $dataFetch->where("po.order_date BETWEEN ? AND ?", [$filterOrderDateFrom, $filterOrderDateTo]);
                    } elseif ($filterOrderDateFrom) {
                        $dataFetch->where("po.order_date >= ?", [$filterOrderDateFrom]);
                    } elseif ($filterOrderDateTo) {
                        $dataFetch->where("po.order_date <= ?", [$filterOrderDateTo]);
                    }
                    break;
            }
        }

        $results = $dataFetch->fetch();

        response($results)->sendJson();
    }


    private function save(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $action = "create";
        if( $id ) {
            $action = "update";
        }

        $inputs = $request->getInputs();

        $service = $this->servicePurchaseOrder();
        if( $action === "update" ) {
            $response = $service->update($id, $inputs);
        } else {
            $response = $service->create($inputs);
        }

        if( $response["success"] ) {
            
            $responseMessage = $action === "update" ? "Purchase order updated successfully" : "Purchase order created successfully";
            $responseCode = $action === "update" ? 200 : 201;
            
            return response($response["data"], $responseMessage, $responseCode)->sendJson();
        } else {
            
            $responseMessage = $action === "update" ? "Failed to update purchase order" : "Failed to create purchase order";
            return response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
        }
    }


    private function show(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);    

        $service = $this->servicePurchaseOrder();
        $data = $service->getDetails($id);

        return response($data)->sendJson();
    }


    private function updateStatus(TinyPHP_Request $request) {

        $id     = $request->getInput("id", "Int", 0);
        $status = $request->getInput("status", "String", "");
        $inputs = $request->getInputs();

        $service = $this->servicePurchaseOrder();

        if ($status === 'cancelled') {
            $response = $service->cancel($id);
        } else {
            $response = $service->updateStatus($id, $inputs);
        }

        if( $response["success"] ) {
            return response($response["data"], "Status updated successfully", 200)->sendJson();
        } else {
            return response([], "Failed to update status", 422)->errors($response["errors"])->sendJson();
        }
    }
}