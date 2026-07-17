<?php
class Api_PurchaseReceiptsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function servicePoGrn(): Service_Po_Grn {
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
        
        $receiptId = $request->getInput("id", "Int", 0);

        $service = $this->servicePoGrn();
        $data = $service->getEditFormContext($receiptId);

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
            // yet to implement logic
        }
        else if( $request->isMethod("post") ) {
            return $this->updateStatus($request);
        }        
    }


    public function cancelAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);

        $service = $this->servicePoGrn();
        $data = $service->cancel($id);

        return response($data['data'], "Purchase receipt cancelled successfully", 200)->sendJson();
    }


    public function historyAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);

        $service = $this->servicePoGrn();
        $data = $service->getHistory($id);

        return response($data)->sendJson();
    }


    private function list(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;
        $poId = $request->getInput("po_id", "Int", 0);

        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = [
            "id"               => "grn.id",
            "receipt_number"   => "grn.grn_number",
            "create_date"      => "grn.created_at",
            "received_date"    => "grn.received_date",
            "vendor"           => "v.display_name",
            "purchase_order_id"=> "po.id",
            "po_number"        => "po.po_number",
            "status"           => "grn.status",
            "items_count"      => "COUNT(grni.id)",
        ];

        $dataFetch
            ->table("purchase_order_grns AS grn")
            ->joins("LEFT JOIN purchase_orders AS po ON grn.purchase_order_id = po.id
                LEFT JOIN vendors AS v ON po.vendor_id = v.id
                LEFT JOIN purchase_order_grn_items AS grni ON grn.id = grni.purchase_order_grn_id")
            ->columns($columns)
            ->ignoreSearch(['items_count'])
            ->where("grn.company_id = ?", [$companyId])
            ->groupBy("grn.id");

        if ($poId) {
            $dataFetch->where("grn.purchase_order_id = ?", [$poId]);
        }

        // Status filter
        $filterStatus = $request->getInput("filter_status", "array", []);
        if (!empty($filterStatus)) {
            $validStatuses = ['draft', 'in_transit', 'received', 'cancelled'];
            $filterStatus  = array_values(array_filter($filterStatus, fn($s) => in_array($s, $validStatuses, true)));
            if (!empty($filterStatus)) {
                $placeholders = implode(',', array_fill(0, count($filterStatus), '?'));
                $dataFetch->where("grn.status IN ({$placeholders})", $filterStatus);
            }
        }

        // Vendor filter
        $filterVendorId = $request->getInput("filter_vendor_id", "Int", 0);
        if ($filterVendorId > 0) {
            $dataFetch->where("po.vendor_id = ?", [$filterVendorId]);
        }

        // Received date filter
        $filterReceivedDatePreset = $request->getInput("filter_received_date_preset", "String", "");
        $filterReceivedDateFrom   = $request->getInput("filter_received_date_from",   "String", "");
        $filterReceivedDateTo     = $request->getInput("filter_received_date_to",     "String", "");
        $today = dateNow('Y-m-d');

        if ($filterReceivedDatePreset) {
            switch ($filterReceivedDatePreset) {
                case 'today':
                    $dataFetch->where("grn.received_date = ?", [$today]);
                    break;
                case 'this_week':
                    $dataFetch->where("grn.received_date BETWEEN ? AND ?", [dateNow('Y-m-d', 'monday this week'), $today]);
                    break;
                case 'this_month':
                    $dataFetch->where("grn.received_date BETWEEN ? AND ?", [dateNow('Y-m-01'), $today]);
                    break;
                case 'last_month':
                    $dataFetch->where("grn.received_date BETWEEN ? AND ?", [
                        dateNow('Y-m-01', 'first day of last month'),
                        dateNow('Y-m-t',  'last day of last month'),
                    ]);
                    break;
                case 'last_3_months':
                    $dataFetch->where("grn.received_date BETWEEN ? AND ?", [dateNow('Y-m-d', '-3 months'), $today]);
                    break;
                case 'custom':
                    if ($filterReceivedDateFrom && $filterReceivedDateTo) {
                        $dataFetch->where("grn.received_date BETWEEN ? AND ?", [$filterReceivedDateFrom, $filterReceivedDateTo]);
                    } elseif ($filterReceivedDateFrom) {
                        $dataFetch->where("grn.received_date >= ?", [$filterReceivedDateFrom]);
                    } elseif ($filterReceivedDateTo) {
                        $dataFetch->where("grn.received_date <= ?", [$filterReceivedDateTo]);
                    }
                    break;
            }
        }

        $results = $dataFetch->fetch();

        return response($results)->sendJson();
    }


    private function save(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $action = "create";
        if( $id ) {
            $action = "update";
        }

        $inputs = $request->getInputs();

        $service = $this->servicePoGrn();
        if( $action === "update" ) {
            $response = $service->update($id, $inputs);
        } else {
            $poId = $request->getInput("purchase_order_id", "Int", 0);
            $response = $service->create($poId, $inputs);
        }

        if( $response["success"] ) {
            
            $responseMessage = $action === "update" ? "Purchase receipt updated successfully" : "Purchase receipt created successfully";
            $responseCode = $action === "update" ? 200 : 201;
            
            return response($response["data"], $responseMessage, $responseCode)->sendJson();
        } else {
            
            $responseMessage = $action === "update" ? "Failed to update purchase receipt" : "Failed to create purchase receipt";
            
            return response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
        }
    }


    private function show(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        
        $service = $this->servicePoGrn();
        $data = $service->getDetails($id);

        return response($data)->sendJson();
    }


    private function updateStatus(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);        
        $inputs = $request->getInputs();

        $service = $this->servicePoGrn();
        $response = $service->updateStatus($id, $inputs);

        if( $response["success"] ) {
            return response($response["data"], "Status updated successfully", 200)->sendJson();
        } else {
            return response([], "Failed to update status", 422)->errors($response["errors"])->sendJson();
        }
    }
}
