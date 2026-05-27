<?php
class Api_SalesOrdersController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function serviceSalesOrder() : Service_So_Order {
        return new Service_So_Order(tenantContext());
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
        $leadId = $request->getInput("lead_id", "Int", 0);
        
        $service = $this->serviceSalesOrder();
        $data = $service->getFormContext($id, $leadId);

        return response($data)->sendJson();
    }


    public function customersSearchAction(TinyPHP_Request $request) {
        
        $query = $request->getInput("q", "String", "");
        
        $service = $this->serviceSalesOrder();
        $data = $service->searchCustomers($query);

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

        $id     = $request->getInput("id", "Int", 0);
        $inputs = $request->getInputs();
        $newStatus = $inputs["status"] ?? "";

        $ctx = tenantContext();
        if ($newStatus === "confirmed" && !$ctx->canDo("sales_orders", "confirm")) {
            return response([], "You do not have permission to confirm sales orders", 403)->sendJson();
        }
        if ($newStatus === "cancelled" && !$ctx->canDo("sales_orders", "cancel")) {
            return response([], "You do not have permission to cancel sales orders", 403)->sendJson();
        }

        $service = $this->serviceSalesOrder();
        $response = $service->updateStatus($id, $inputs);

        if( $response["success"] ) {
            return response($response["data"], "Status updated successfully", 200)->sendJson();
        } elseif (!empty($response["warning"])) {
            return response([], "Please review stock warnings", 200)->warnings($response["warnings"])->warningType($response["warning_type"])->sendJson();
        } else {
            return response([], "Failed to update status", 422)->errors($response["errors"])->sendJson();
        }
    }


    public function generateEmailPdfAction(TinyPHP_Request $request) {

        $id     = $request->getInput("id", "Int", 0);
        $result = $this->serviceSalesOrder()->generateEmailPdf($id);

        return response($result)->sendJson();
    }


    public function sendEmailAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $inputs = $request->getInputs();

        $service = $this->serviceSalesOrder();
        $result = $service->sendEmail($id, $inputs);

        if( $result["success"] ) {
            return response([], "Email sent successfully", 200)->sendJson();
        } else {
            return response([], "Failed to send email", 422)->errors($result["errors"])->sendJson();
        }
    }


    public function historyAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);
        
        $service = $this->serviceSalesOrder();
        $data = $service->getHistory($id);

        response($data)->sendJson();
    }


    private function list(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;
        $leadId = $request->getInput("lead_id", "Int", 0);

        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = [
            "id"                     => "so.id",
            "so_number"              => "so.so_number",
            "origin_type"            => "so.origin_type",
            "order_date"             => "so.order_date",
            "customer"               => "c.display_name",
            "reference"              => "so.reference",
            "status"                 => "so.status",
            "expected_delivery_date" => "so.expected_delivery_date",
            "grand_total"            => "so.grand_total",
            "lead_id"                => "so.lead_id",
            "created_by_name"        => "u.name",
        ];

        $dataFetch
            ->table("sales_orders AS so")
            ->joins("LEFT JOIN customers AS c ON so.customer_id = c.id LEFT JOIN users AS u ON u.id = so.created_by")
            ->columns($columns)
            ->where("so.company_id = ?", [$companyId])
            ->where("(so.origin_type != ? OR so.status != ?)", ['quotation', 'draft']);

        $scope = (new Service_Scope(tenantContext()))->getCondition('sales_orders', ['so.salesperson_id', 'so.created_by']);
        if ($scope['sql']) {
            $dataFetch->where($scope['sql'], $scope['bindings']);
        }

        if ($leadId > 0) {
            $dataFetch->where("so.lead_id = ?", [$leadId]);
        }

        // Status filter
        $filterStatus = $request->getInput("filter_status", "array", []);
        if (!empty($filterStatus)) {
            $validStatuses = ['draft', 'confirmed', 'cancelled', 'partially_dispatched', 'dispatched', 'partially_delivered', 'delivered'];
            $filterStatus  = array_values(array_filter($filterStatus, fn($s) => in_array($s, $validStatuses, true)));
            if (!empty($filterStatus)) {
                $placeholders = implode(',', array_fill(0, count($filterStatus), '?'));
                $dataFetch->where("so.status IN ({$placeholders})", $filterStatus);
            }
        }

        // Delivery date preset filter
        $filterDelivery = $request->getInput("filter_delivery", "String", "");
        if ($filterDelivery) {
            $today = date('Y-m-d');
            switch ($filterDelivery) {
                case 'overdue':
                    $dataFetch->where("so.expected_delivery_date < ? AND so.expected_delivery_date IS NOT NULL AND so.status NOT IN ('delivered','cancelled')", [$today]);
                    break;
                case 'due_today':
                    $dataFetch->where("so.expected_delivery_date = ? AND so.status NOT IN ('delivered','cancelled')", [$today]);
                    break;
                case 'due_this_week':
                    $weekEnd = date('Y-m-d', strtotime('sunday this week'));
                    $dataFetch->where("so.expected_delivery_date BETWEEN ? AND ? AND so.expected_delivery_date IS NOT NULL AND so.status NOT IN ('delivered','cancelled')", [$today, $weekEnd]);
                    break;
                case 'due_this_month':
                    $monthEnd = date('Y-m-t');
                    $dataFetch->where("so.expected_delivery_date BETWEEN ? AND ? AND so.expected_delivery_date IS NOT NULL AND so.status NOT IN ('delivered','cancelled')", [$today, $monthEnd]);
                    break;
                case 'custom':
                    $from = $request->getInput("filter_delivery_date_from", "String", "");
                    $to   = $request->getInput("filter_delivery_date_to",   "String", "");
                    if ($from && $to) {
                        $dataFetch->where("so.expected_delivery_date BETWEEN ? AND ? AND so.expected_delivery_date IS NOT NULL", [$from, $to]);
                    } elseif ($from) {
                        $dataFetch->where("so.expected_delivery_date >= ? AND so.expected_delivery_date IS NOT NULL", [$from]);
                    } elseif ($to) {
                        $dataFetch->where("so.expected_delivery_date <= ? AND so.expected_delivery_date IS NOT NULL", [$to]);
                    }
                    break;
            }
        }

        // Order date filter (preset takes priority over custom range)
        $filterOrderDatePreset = $request->getInput("filter_order_date_preset", "String", "");
        $filterOrderDateFrom   = $request->getInput("filter_order_date_from",   "String", "");
        $filterOrderDateTo     = $request->getInput("filter_order_date_to",     "String", "");
        if ($filterOrderDatePreset) {
            $today = date('Y-m-d');
            switch ($filterOrderDatePreset) {
                case 'today':
                    $dataFetch->where("DATE(so.order_date) = ?", [$today]);
                    break;
                case 'this_week':
                    $dataFetch->where("DATE(so.order_date) BETWEEN ? AND ?", [date('Y-m-d', strtotime('monday this week')), $today]);
                    break;
                case 'this_month':
                    $dataFetch->where("DATE(so.order_date) BETWEEN ? AND ?", [date('Y-m-01'), $today]);
                    break;
                case 'last_month':
                    $dataFetch->where("DATE(so.order_date) BETWEEN ? AND ?", [
                        date('Y-m-01', strtotime('first day of last month')),
                        date('Y-m-t',  strtotime('last day of last month')),
                    ]);
                    break;
                case 'last_3_months':
                    $dataFetch->where("DATE(so.order_date) BETWEEN ? AND ?", [date('Y-m-d', strtotime('-3 months')), $today]);
                    break;
            }
        } elseif ($filterOrderDateFrom || $filterOrderDateTo) {
            if ($filterOrderDateFrom && $filterOrderDateTo) {
                $dataFetch->where("DATE(so.order_date) BETWEEN ? AND ?", [$filterOrderDateFrom, $filterOrderDateTo]);
            } elseif ($filterOrderDateFrom) {
                $dataFetch->where("DATE(so.order_date) >= ?", [$filterOrderDateFrom]);
            } else {
                $dataFetch->where("DATE(so.order_date) <= ?", [$filterOrderDateTo]);
            }
        }

        // Salesperson filter — only honoured when the user has team/all scope
        $filterSalespersonId = $request->getInput("filter_salesperson_id", "Int", 0);
        if ($filterSalespersonId > 0 && in_array(tenantContext()->scopeFor('sales_orders'), ['team', 'all'])) {
            $dataFetch->where("(so.salesperson_id = ? OR so.created_by = ?)", [$filterSalespersonId, $filterSalespersonId]);
        }

        $results = $dataFetch->fetch();

        response($results)->sendJson();
    }


    private function save(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $action = $id ? "update" : "create";

        $inputs = $request->getInputs();

        $service = $this->serviceSalesOrder();
        if( $action === "update" ) {
            $response = $service->update($id, $inputs);
        } else {
            $response = $service->create($inputs);
        }

        if( $response["success"] ) {
            
            $responseMessage = $action === "update" ? "Sales order updated successfully" : "Sales order created successfully";
            $responseCode = $action === "update" ? 200 : 201;
            
            return response($response["data"], $responseMessage, $responseCode)->sendJson();
        } elseif (!empty($response["warning"])) {
            
            return response([], "Please review stock warnings", 200)->warnings($response["warnings"])->warningType($response["warning_type"])->sendJson();
        } else {
            
            $responseMessage = $action === "update" ? "Failed to update sales order" : "Failed to create sales order";
            return response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
        }
    }


    private function show(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        
        $service = $this->serviceSalesOrder();
        $data = $service->getDetails($id);

        return response($data)->sendJson();
    }
}
