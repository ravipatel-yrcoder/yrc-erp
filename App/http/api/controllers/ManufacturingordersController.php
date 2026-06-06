<?php
class Api_ManufacturingOrdersController extends TinyPHP_Controller
{
    public function init() {
        $this->setNoRenderer(true);
    }

    private function service(): Service_Manufacturing_Order {
        return new Service_Manufacturing_Order(tenantContext());
    }

    public function indexAction(TinyPHP_Request $request) {
        
        if ($request->isMethod("get"))  return $this->list($request);
        
        if ($request->isMethod("post")) return $this->save($request);
    }

    public function formContextAction(TinyPHP_Request $request) {
        
        $data = $this->service()->getFormContext();
        return response($data)->sendJson();
    }

    public function entityAction(TinyPHP_Request $request) {
        if ($request->isMethod("get"))  return $this->show($request);
        if ($request->isMethod("post")) return $this->save($request);
    }

    public function confirmAction(TinyPHP_Request $request) {
        
        $id = (int) ($request->getParams()['id'] ?? $request->getInput("id", "Int", 0));
        $this->service()->confirm($id);
        return response([], "Manufacturing order confirmed", 200)->sendJson();
    }

    public function cancelAction(TinyPHP_Request $request) {
        
        $id = (int) ($request->getParams()['id'] ?? $request->getInput("id", "Int", 0));
        $this->service()->cancel($id);
        return response([], "Manufacturing order cancelled", 200)->sendJson();
    }

    public function saveAllocationAction(TinyPHP_Request $request) {
        
        $id = (int) ($request->getParams()['id'] ?? 0);
        $inputs = $request->getInputs();
        $result = $this->service()->saveAllocation($id, $inputs);
        
        if ($result['success']) {
            return response($result['data'], "Materials allocated successfully", 200)->sendJson();
        }
        
        return response([], "Validation failed", 422)->errors($result['errors'])->sendJson();
    }

    public function cancelAllocationAction(TinyPHP_Request $request) {
        $id           = (int) ($request->getParams()['id'] ?? 0);
        $allocationId = (int) ($request->getParams()['allocationId'] ?? 0);
        $this->service()->cancelAllocation($id, $allocationId);
        return response([], "Allocation cancelled", 200)->sendJson();
    }

    public function recordOutputAction(TinyPHP_Request $request) {
        $id     = (int) ($request->getParams()['id'] ?? 0);
        $inputs = $request->getInputs();
        $result = $this->service()->recordOutput($id, $inputs);
        if ($result['success']) {
            return response($result['data'], "Output recorded successfully", 200)->sendJson();
        }
        $responseData = isset($result['shortage_items']) ? ['shortage_items' => $result['shortage_items']] : [];
        return response($responseData, "Validation failed", 422)->errors($result['errors'])->sendJson();
    }

    public function forceCompleteAction(TinyPHP_Request $request) {
        $id = (int) ($request->getParams()['id'] ?? 0);
        $this->service()->forceComplete($id);
        return response([], "Manufacturing order force completed", 200)->sendJson();
    }

    public function recordMaterialReturnAction(TinyPHP_Request $request) {
        $id     = (int) ($request->getParams()['id'] ?? 0);
        $inputs = $request->getInputs();
        $result = $this->service()->recordMaterialReturn($id, $inputs);
        if ($result['success']) {
            return response($result['data'], "Materials returned successfully", 200)->sendJson();
        }
        return response([], "Validation failed", 422)->errors($result['errors'])->sendJson();
    }

    private function list(TinyPHP_Request $request) {

        if (!tenantContext()->canDo('manufacturing_orders', 'read')) {
            return response([], "You do not have permission to view manufacturing orders", 403)->sendJson();
        }

        $companyId = tenantContext()->companyId;
        $dataFetch  = new TinyPHP_DataFetch($request);

        $columns = [
            "id"                       => "mo.id",
            "mo_number"                => "mo.mo_number",
            "product_name"             => "p.name",
            "bom_name"                 => "mo.bom_name",
            "source_location_id"       => "mo.source_location_id",
            "source_location_name"     => "src_loc.name",
            "destination_location_id"  => "mo.destination_location_id",
            "destination_location_name"=> "dest_loc.name",
            "planned_qty"              => "mo.planned_qty",
            "produced_qty"             => "mo.produced_qty",
            "planned_date"             => "mo.planned_date",
            "status"                   => "mo.status",
            "allocation_status"        => "mo.allocation_status",
            "created_by_name"          => "u.name",
            "created_at"               => "mo.created_at",
        ];

        $dataFetch
            ->table("manufacturing_orders AS mo")
            ->joins("LEFT JOIN products AS p ON p.id = mo.product_id
                LEFT JOIN users AS u ON u.id = mo.created_by
                LEFT JOIN company_locations AS src_loc ON src_loc.id = mo.source_location_id
                LEFT JOIN company_locations AS dest_loc ON dest_loc.id = mo.destination_location_id")
            ->columns($columns)
            ->where("mo.company_id = ?", [$companyId]);

        // Status filter
        $filterStatus = $request->getInput("filter_status", "array", []);
        if (!empty($filterStatus)) {
            $validStatuses = ['draft', 'confirmed', 'in_production', 'completed', 'cancelled'];
            $filterStatus  = array_values(array_filter($filterStatus, fn($s) => in_array($s, $validStatuses, true)));
            if (!empty($filterStatus)) {
                $placeholders = implode(',', array_fill(0, count($filterStatus), '?'));
                $dataFetch->where("mo.status IN ({$placeholders})", $filterStatus);
            }
        }

        // Finished product filter
        $filterProductId = $request->getInput("filter_product_id", "Int", 0);
        if ($filterProductId > 0) {
            $dataFetch->where("mo.product_id = ?", [$filterProductId]);
        }

        // Allocation status filter
        $filterAllocationStatus = $request->getInput("filter_allocation_status", "array", []);
        if (!empty($filterAllocationStatus)) {
            $validAllocationStatuses = ['not_allocated', 'partially_allocated', 'fully_allocated'];
            $filterAllocationStatus  = array_values(array_filter($filterAllocationStatus, fn($s) => in_array($s, $validAllocationStatuses, true)));
            if (!empty($filterAllocationStatus)) {
                $placeholders = implode(',', array_fill(0, count($filterAllocationStatus), '?'));
                $dataFetch->where("mo.allocation_status IN ({$placeholders})", $filterAllocationStatus);
            }
        }

        // Scheduled date filter
        $filterScheduledDatePreset = $request->getInput("filter_scheduled_date_preset", "String", "");
        $filterScheduledDateFrom   = $request->getInput("filter_scheduled_date_from",   "String", "");
        $filterScheduledDateTo     = $request->getInput("filter_scheduled_date_to",     "String", "");
        if ($filterScheduledDatePreset) {
            $today = date('Y-m-d');
            switch ($filterScheduledDatePreset) {
                case 'overdue':
                    $dataFetch->where("mo.planned_date < ? AND mo.planned_date IS NOT NULL AND mo.status NOT IN ('completed','cancelled')", [$today]);
                    break;
                case 'due_today':
                    $dataFetch->where("mo.planned_date = ? AND mo.planned_date IS NOT NULL", [$today]);
                    break;
                case 'due_this_week':
                    $weekEnd = date('Y-m-d', strtotime('sunday this week'));
                    $dataFetch->where("mo.planned_date BETWEEN ? AND ? AND mo.planned_date IS NOT NULL", [$today, $weekEnd]);
                    break;
                case 'due_this_month':
                    $monthEnd = date('Y-m-t');
                    $dataFetch->where("mo.planned_date BETWEEN ? AND ? AND mo.planned_date IS NOT NULL", [$today, $monthEnd]);
                    break;
                case 'custom':
                    if ($filterScheduledDateFrom && $filterScheduledDateTo) {
                        $dataFetch->where("mo.planned_date BETWEEN ? AND ? AND mo.planned_date IS NOT NULL", [$filterScheduledDateFrom, $filterScheduledDateTo]);
                    } elseif ($filterScheduledDateFrom) {
                        $dataFetch->where("mo.planned_date >= ? AND mo.planned_date IS NOT NULL", [$filterScheduledDateFrom]);
                    } elseif ($filterScheduledDateTo) {
                        $dataFetch->where("mo.planned_date <= ? AND mo.planned_date IS NOT NULL", [$filterScheduledDateTo]);
                    }
                    break;
            }
        }

        $results = $dataFetch->fetch();

        response($results)->sendJson();
    }

    private function save(TinyPHP_Request $request) {
        $id     = (int) ($request->getParams()['id'] ?? 0);
        $inputs = $request->getInputs();

        if ($id > 0) {
            $result  = $this->service()->update($id, $inputs);
            $message = "Manufacturing order updated successfully";
        } else {
            $result  = $this->service()->create($inputs);
            $message = "Manufacturing order created successfully";
        }

        if ($result["success"]) {
            return response($result["data"], $message, 200)->sendJson();
        }

        return response([], "Validation failed", 422)->errors($result["errors"])->sendJson();
    }

    private function show(TinyPHP_Request $request) {
        $id   = (int) ($request->getParams()['id'] ?? $request->getInput("id", "Int", 0));
        $data = $this->service()->getDetails($id);
        return response($data)->sendJson();
    }
}
