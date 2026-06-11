<?php
class Api_SalesDeliveriesController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function serviceSoDelivery() : Service_So_Delivery {
        return new Service_So_Delivery(tenantContext());
    }

    public function indexAction(TinyPHP_Request $request) {
        
        if ($request->isMethod("get")) {
            return $this->listDeliveries($request);
        }
        if ($request->isMethod("post")) {
            return $this->save($request);
        }        
    }


    public function entityAction(TinyPHP_Request $request) {
        
        if ($request->isMethod("get")) {
            return $this->show($request);
        }
        if ($request->isMethod("post")) {
            return $this->save($request);
        }        
    }


    public function formContextAction(TinyPHP_Request $request) {
        
        $dnId = $request->getInput("id", "Int", 0);
        $soId = $request->getInput("so_id", "Int", 0);
        
        $service = $this->serviceSoDelivery();
        $data = $service->getFormContext($dnId, $soId);

        return response($data)->sendJson();
    }


    public function soSearchAction(TinyPHP_Request $request) {
        
        $query = $request->getInput("q", "String", "");
        
        $service = $this->serviceSoDelivery();
        $data = $service->searchSalesOrders($query);

        return response($data)->sendJson();
    }


    public function statusAction(TinyPHP_Request $request) {

        $id        = $request->getInput("id", "Int", 0);
        $inputs    = $request->getInputs();
        $newStatus = $inputs["status"] ?? "";

        $ctx = tenantContext();
        if ($newStatus === "dispatched" && !$ctx->canDo("sales_deliveries", "dispatch")) {
            return response([], "You do not have permission to dispatch deliveries", 403)->sendJson();
        }
        if (in_array($newStatus, ["delivered", "returned", "lost"]) && !$ctx->canDo("sales_deliveries", "mark_complete")) {
            return response([], "You do not have permission to mark delivery outcomes", 403)->sendJson();
        }
        if ($newStatus === "cancelled" && !$ctx->canDo("sales_deliveries", "cancel")) {
            return response([], "You do not have permission to cancel deliveries", 403)->sendJson();
        }
        if ($newStatus === "draft" && !$ctx->canDo("sales_deliveries", "write")) {
            return response([], "You do not have permission to revert deliveries", 403)->sendJson();
        }

        $service = $this->serviceSoDelivery();
        $response = $service->updateStatus($id, $inputs);

        if ($response["success"]) {
            return response($response["data"], "Delivery note status updated", 200)->sendJson();
        } else {
            return response([], "Failed to update delivery note status", 422)->errors($response["errors"] ?? [])->sendJson();
        }
    }


    public function historyAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);
        
        $service = $this->serviceSoDelivery();
        $data = $service->getHistory($id);

        return response($data)->sendJson();
    }


    private function listDeliveries(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;
        $soId = $request->getInput("so_id", "Int", 0);

        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = [
            "id" => "dn.id",
            "dn_number" => "dn.dn_number",
            "so_number" => "so.so_number",
            "customer" => "c.display_name",
            "location" => "l.name",
            "status" => "dn.status",
            "dispatch_date" => "dn.dispatch_date",
            "delivery_date" => "dn.delivery_date",
            "items_count" => "(SELECT COUNT(*) FROM sales_delivery_items WHERE sales_delivery_id = dn.id)",
            "created_by_name" => "u.name",
            "created_at" => "dn.created_at",
        ];

        $query = $dataFetch
            ->table("sales_deliveries AS dn")
            ->joins(
                "LEFT JOIN sales_orders AS so ON so.id = dn.sales_order_id
                 LEFT JOIN customers AS c ON c.id = dn.customer_id
                 LEFT JOIN company_locations AS l ON l.id = dn.location_id
                 LEFT JOIN users AS u ON u.id = dn.created_by"
            )
            ->columns($columns)
            ->where("dn.company_id = ?", [$companyId]);

        // Deliveries inherit scope from parent SO — show all DNs whose parent SO is accessible
        $scope = (new Service_Scope(tenantContext()))->getCondition('sales_orders', ['so.salesperson_id', 'so.created_by']);
        if ($scope['sql']) {
            $query->where($scope['sql'], $scope['bindings']);
        }

        if ($soId) {
            $query->where("dn.sales_order_id = ?", [$soId]);
        }

        // Status filter
        $filterStatus = $request->getInput("filter_status", "array", []);
        if (!empty($filterStatus)) {
            $validStatuses = ['draft', 'dispatched', 'delivered', 'returned', 'lost', 'cancelled'];
            $filterStatus  = array_values(array_filter($filterStatus, fn($s) => in_array($s, $validStatuses, true)));
            if (!empty($filterStatus)) {
                $placeholders = implode(',', array_fill(0, count($filterStatus), '?'));
                $query->where("dn.status IN ({$placeholders})", $filterStatus);
            }
        }

        // Customer filter
        $filterCustomerId = $request->getInput("filter_customer_id", "Int", 0);
        if ($filterCustomerId > 0) {
            $query->where("dn.customer_id = ?", [$filterCustomerId]);
        }

        $today = dateNow('Y-m-d');

        // Dispatch date filter
        $filterDispatchDatePreset = $request->getInput("filter_dispatch_date_preset", "String", "");
        $filterDispatchDateFrom   = $request->getInput("filter_dispatch_date_from",   "String", "");
        $filterDispatchDateTo     = $request->getInput("filter_dispatch_date_to",     "String", "");
        if ($filterDispatchDatePreset) {
            switch ($filterDispatchDatePreset) {
                case 'today':
                    $query->where("dn.dispatch_date = ?", [$today]);
                    break;
                case 'this_week':
                    $query->where("dn.dispatch_date BETWEEN ? AND ?", [dateNow('Y-m-d', 'monday this week'), $today]);
                    break;
                case 'this_month':
                    $query->where("dn.dispatch_date BETWEEN ? AND ?", [dateNow('Y-m-01'), $today]);
                    break;
                case 'last_month':
                    $query->where("dn.dispatch_date BETWEEN ? AND ?", [
                        dateNow('Y-m-01', 'first day of last month'),
                        dateNow('Y-m-t',  'last day of last month'),
                    ]);
                    break;
                case 'last_3_months':
                    $query->where("dn.dispatch_date BETWEEN ? AND ?", [dateNow('Y-m-d', '-3 months'), $today]);
                    break;
                case 'custom':
                    if ($filterDispatchDateFrom && $filterDispatchDateTo) {
                        $query->where("dn.dispatch_date BETWEEN ? AND ?", [$filterDispatchDateFrom, $filterDispatchDateTo]);
                    } elseif ($filterDispatchDateFrom) {
                        $query->where("dn.dispatch_date >= ?", [$filterDispatchDateFrom]);
                    } elseif ($filterDispatchDateTo) {
                        $query->where("dn.dispatch_date <= ?", [$filterDispatchDateTo]);
                    }
                    break;
            }
        }

        // Delivery date filter
        $filterDeliveryDatePreset = $request->getInput("filter_delivery_date_preset", "String", "");
        $filterDeliveryDateFrom   = $request->getInput("filter_delivery_date_from",   "String", "");
        $filterDeliveryDateTo     = $request->getInput("filter_delivery_date_to",     "String", "");
        if ($filterDeliveryDatePreset) {
            switch ($filterDeliveryDatePreset) {
                case 'today':
                    $query->where("dn.delivery_date = ?", [$today]);
                    break;
                case 'this_week':
                    $query->where("dn.delivery_date BETWEEN ? AND ?", [dateNow('Y-m-d', 'monday this week'), $today]);
                    break;
                case 'this_month':
                    $query->where("dn.delivery_date BETWEEN ? AND ?", [dateNow('Y-m-01'), $today]);
                    break;
                case 'last_month':
                    $query->where("dn.delivery_date BETWEEN ? AND ?", [
                        dateNow('Y-m-01', 'first day of last month'),
                        dateNow('Y-m-t',  'last day of last month'),
                    ]);
                    break;
                case 'last_3_months':
                    $query->where("dn.delivery_date BETWEEN ? AND ?", [dateNow('Y-m-d', '-3 months'), $today]);
                    break;
                case 'custom':
                    if ($filterDeliveryDateFrom && $filterDeliveryDateTo) {
                        $query->where("dn.delivery_date BETWEEN ? AND ?", [$filterDeliveryDateFrom, $filterDeliveryDateTo]);
                    } elseif ($filterDeliveryDateFrom) {
                        $query->where("dn.delivery_date >= ?", [$filterDeliveryDateFrom]);
                    } elseif ($filterDeliveryDateTo) {
                        $query->where("dn.delivery_date <= ?", [$filterDeliveryDateTo]);
                    }
                    break;
            }
        }

        $results = $query->fetch();

        return response($results)->sendJson();
    }


    private function show(TinyPHP_Request $request) {

        $id        = $request->getInput("id", "Int", 0);
        $companyId = tenantContext()->companyId;

        // Verify access via parent SO scope before fetching full details
        $scope = (new Service_Scope(tenantContext()))->getCondition('sales_orders', ['so.salesperson_id', 'so.created_by']);
        $sql    = "SELECT dn.id FROM sales_deliveries dn
                   LEFT JOIN sales_orders so ON so.id = dn.sales_order_id
                   WHERE dn.id = ? AND dn.company_id = ?";
        $params = [$id, $companyId];
        if ($scope['sql']) {
            $sql   .= " AND (" . $scope['sql'] . ")";
            $params = array_merge($params, $scope['bindings']);
        }
        if (!DB()->fetchOne($sql, $params)) {
            return response([], "Access denied", 403)->sendJson();
        }

        $service = $this->serviceSoDelivery();
        $data    = $service->getDetails($id);

        return response($data)->sendJson();
    }


    private function save(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $action = $id ? "update" : "create";

        $inputs = $request->getInputs();

        $service = $this->serviceSoDelivery();

        if ($action === "update") {
            $response = $service->update($id, $inputs);
        } else {
            $response = $service->create($inputs);
        }

        if ($response["success"]) {
            
            $responseMessage = $action === "update" ? "Delivery note updated successfully" : "Delivery note created successfully";
            $responseCode = $action === "update" ? 200 : 201;
            
            return response($response["data"], $responseMessage, $responseCode)->sendJson();
        } else {
            
            $responseMessage = $action === "update" ? "Failed to update delivery note" : "Failed to create delivery note";
            return response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
        }
    }    
}
?>
