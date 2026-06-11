<?php
class Api_ManufacturingBomsController extends TinyPHP_Controller
{
    public function init() {
        $this->setNoRenderer(true);
    }

    private function service(): Service_Manufacturing_Bom {
        return new Service_Manufacturing_Bom(tenantContext());
    }

    public function indexAction(TinyPHP_Request $request) {
        if ($request->isMethod("get"))  return $this->list($request);
        if ($request->isMethod("post")) return $this->save($request);
    }

    public function formContextAction(TinyPHP_Request $request) {
        $id   = $request->getInput("id", "Int", 0);
        $data = $this->service()->getFormContext($id);
        return response($data)->sendJson();
    }

    public function entityAction(TinyPHP_Request $request) {
        if ($request->isMethod("get"))    return $this->show($request);
        if ($request->isMethod("post"))   return $this->save($request);
        if ($request->isMethod("delete")) return $this->destroy($request);
    }

    private function list(TinyPHP_Request $request) {

        if (!tenantContext()->canDo('manufacturing_boms', 'read')) {
            return response([], "You do not have permission to view BOMs", 403)->sendJson();
        }

        $companyId = tenantContext()->companyId;
        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = [
            "id" => "b.id",
            "product_name" => "p.name",
            "name" => "b.name",
            "output_qty" => "b.output_qty",
            "is_default" => "b.is_default",
            "status" => "b.status",
            "component_count" => "COUNT(bi.id)",
            "created_by_name" => "u.name",
            "created_at" => "b.created_at",
        ];

        $dataFetch
            ->table("manufacturing_boms AS b")
            ->joins("LEFT JOIN products AS p ON p.id = b.product_id
                LEFT JOIN manufacturing_bom_items AS bi ON bi.bom_id = b.id
                LEFT JOIN users AS u ON u.id = b.created_by")
            ->columns($columns)
            ->ignoreSearch(['component_count'])
            ->where("b.company_id = ?", [$companyId])
            ->groupBy("b.id");

        // Status filter
        $filterStatus = $request->getInput("filter_status", "array", []);
        if (!empty($filterStatus)) {
            $validStatuses = ['active', 'inactive'];
            $filterStatus  = array_values(array_filter($filterStatus, fn($s) => in_array($s, $validStatuses, true)));
            if (!empty($filterStatus)) {
                $placeholders = implode(',', array_fill(0, count($filterStatus), '?'));
                $dataFetch->where("b.status IN ({$placeholders})", $filterStatus);
            }
        }

        // Finished product filter
        $filterProductId = $request->getInput("filter_product_id", "Int", 0);
        if ($filterProductId > 0) {
            $dataFetch->where("b.product_id = ?", [$filterProductId]);
        }

        // Is default filter
        $filterIsDefault = $request->getInput("filter_is_default", "String", "");
        if ($filterIsDefault !== '') {
            $dataFetch->where("b.is_default = ?", [(int) $filterIsDefault]);
        }

        // Created date filter
        $filterCreatedDatePreset = $request->getInput("filter_created_date_preset", "String", "");
        $filterCreatedDateFrom   = $request->getInput("filter_created_date_from",   "String", "");
        $filterCreatedDateTo     = $request->getInput("filter_created_date_to",     "String", "");
        $today = dateNow('Y-m-d');

        if ($filterCreatedDatePreset) {
            switch ($filterCreatedDatePreset) {
                case 'today':
                    $dataFetch->where("b.created_at >= ? AND b.created_at <= ?", [localToUtc($today . ' 00:00:00'), localToUtc($today . ' 23:59:59')]);
                    break;
                case 'this_week':
                    $dataFetch->where("b.created_at >= ? AND b.created_at <= ?", [localToUtc(dateNow('Y-m-d', 'monday this week') . ' 00:00:00'), localToUtc($today . ' 23:59:59')]);
                    break;
                case 'this_month':
                    $dataFetch->where("b.created_at >= ? AND b.created_at <= ?", [localToUtc(dateNow('Y-m-01') . ' 00:00:00'), localToUtc($today . ' 23:59:59')]);
                    break;
                case 'last_month':
                    $dataFetch->where("b.created_at >= ? AND b.created_at <= ?", [
                        localToUtc(dateNow('Y-m-01', 'first day of last month') . ' 00:00:00'),
                        localToUtc(dateNow('Y-m-t',  'last day of last month')  . ' 23:59:59'),
                    ]);
                    break;
                case 'last_3_months':
                    $dataFetch->where("b.created_at >= ? AND b.created_at <= ?", [localToUtc(dateNow('Y-m-d', '-3 months') . ' 00:00:00'), localToUtc($today . ' 23:59:59')]);
                    break;
                case 'custom':
                    if ($filterCreatedDateFrom && $filterCreatedDateTo) {
                        $dataFetch->where("b.created_at >= ? AND b.created_at <= ?", [localToUtc($filterCreatedDateFrom . ' 00:00:00'), localToUtc($filterCreatedDateTo . ' 23:59:59')]);
                    } elseif ($filterCreatedDateFrom) {
                        $dataFetch->where("b.created_at >= ?", [localToUtc($filterCreatedDateFrom . ' 00:00:00')]);
                    } elseif ($filterCreatedDateTo) {
                        $dataFetch->where("b.created_at <= ?", [localToUtc($filterCreatedDateTo . ' 23:59:59')]);
                    }
                    break;
            }
        }

        // Created by filter
        $filterCreatedBy = $request->getInput("filter_created_by", "Int", 0);
        if ($filterCreatedBy > 0) {
            $dataFetch->where("b.created_by = ?", [$filterCreatedBy]);
        }

        $results = $dataFetch->fetch();

        response($results)->sendJson();
    }

    private function save(TinyPHP_Request $request) {
        
        $id = (int) ($request->getParams()['id'] ?? $request->getInput("id", "Int", 0));
        $inputs = $request->getInputs();

        $service = $this->service();
        if ($id) {
            $result = $service->update($id, $inputs);
            $okMsg  = "BOM updated successfully";
            $failMsg = "Failed to update BOM";
            $okCode  = 200;
        } else {
            $result  = $service->create($inputs);
            $okMsg   = "BOM created successfully";
            $failMsg = "Failed to create BOM";
            $okCode  = 201;
        }

        if ($result["success"]) {
            return response($result["data"], $okMsg, $okCode)->sendJson();
        }

        return response([], $failMsg, 422)->errors($result["errors"])->sendJson();
    }

    private function show(TinyPHP_Request $request) {
        
        $id = (int) ($request->getParams()['id'] ?? $request->getInput("id", "Int", 0));
        $data = $this->service()->getDetails($id);
        return response($data)->sendJson();
    }

    private function destroy(TinyPHP_Request $request) {
        
        $id = (int) ($request->getParams()['id'] ?? $request->getInput("id", "Int", 0));
        $this->service()->delete($id);
        return response([], "BOM deleted successfully", 200)->sendJson();
    }
}
