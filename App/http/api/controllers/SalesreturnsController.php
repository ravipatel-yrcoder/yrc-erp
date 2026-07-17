<?php
class Api_SalesreturnsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function service(): Service_So_Return {
        return new Service_So_Return(tenantContext());
    }

    public function formContextAction(TinyPHP_Request $request) {

        $returnId = $request->getInput("id", "Int", 0);
        $soId     = $request->getInput("so_id", "Int", 0);
        $data = $this->service()->getFormContext($returnId, $soId);
        return response($data)->sendJson();
    }


    public function indexAction(TinyPHP_Request $request) {

        if ($request->isMethod("post")) {
            $payload = $request->getInputs();
            $id = $this->service()->create($payload);
            return response(['id' => $id], "Return created successfully")->sendJson();
        }

        if ($request->isMethod("get")) {
            return $this->listReturns($request);
        }
    }


    private function listReturns(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;
        $soId      = $request->getInput("so_id", "Int", 0);

        $columns = [
            "id"                     => "r.id",
            "return_number"          => "r.return_number",
            "return_type"            => "r.return_type",
            "reference_type"         => "r.reference_type",
            "reference_id"           => "r.reference_id",
            "so_number"              => "so.so_number",
            "party_type"             => "r.party_type",
            "party_id"               => "r.party_id",
            "party_name"             => "CASE WHEN r.party_type = 'customer' THEN c.display_name WHEN r.party_type = 'vendor' THEN v.display_name ELSE NULL END",
            "received_warehouse_name" => "w.name",
            "return_date"            => "r.return_date",
            "status"                 => "r.status",
            "items_count"            => "(SELECT COUNT(*) FROM return_items ri WHERE ri.return_id = r.id)",
            "created_by_name"        => "u.name",
            "created_at"             => "r.created_at",
        ];

        $dataFetch = (new TinyPHP_DataFetch($request))
            ->table("returns AS r")
            ->joins(
                "LEFT JOIN sales_orders AS so ON so.id = r.reference_id AND r.reference_type = 'sales_order'
                 LEFT JOIN customers AS c ON c.id = r.party_id AND r.party_type = 'customer'
                 LEFT JOIN vendors AS v ON v.id = r.party_id AND r.party_type = 'vendor'
                 LEFT JOIN inv_warehouses AS w ON w.id = r.received_warehouse_id
                 LEFT JOIN users AS u ON u.id = r.created_by"
            )
            ->columns($columns)
            ->where("r.company_id = ?", [$companyId]);

        // Inherit scope from parent SO (same as deliveries)
        $scope = (new Service_Scope(tenantContext()))->getCondition('sales_orders', ['so.salesperson_id', 'so.created_by']);
        if ($scope['sql']) {
            $dataFetch->where($scope['sql'], $scope['bindings']);
        }

        if ($soId > 0) {
            $dataFetch->where("r.reference_id = ? AND r.reference_type = 'sales_order'", [$soId]);
        }

        $status = $request->getInput("status", "String", "");
        if (!empty($status)) {
            $dataFetch->where("r.status = ?", [$status]);
        }

        $returnType = $request->getInput("return_type", "String", "");
        if (!empty($returnType)) {
            $dataFetch->where("r.return_type = ?", [$returnType]);
        }

        $partyId = $request->getInput("party_id", "Int", 0);
        if ($partyId > 0) {
            $dataFetch->where("r.party_id = ?", [$partyId]);
        }

        $dateFrom = $request->getInput("date_from", "String", "");
        if (!empty($dateFrom)) {
            $dataFetch->where("r.return_date >= ?", [$dateFrom]);
        }

        $dateTo = $request->getInput("date_to", "String", "");
        if (!empty($dateTo)) {
            $dataFetch->where("r.return_date <= ?", [$dateTo]);
        }

        $dataFetch->orderBy("r.id DESC");

        return response($dataFetch->fetch())->sendJson();
    }


    public function entityAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);

        if ($request->isMethod("get")) {
            $data = $this->service()->get($id);
            return response($data)->sendJson();
        }

        if ($request->isMethod("post")) {
            $payload = $request->getInputs();
            $this->service()->update($id, $payload);
            return response([], "Return updated successfully")->sendJson();
        }
    }


    public function itemFollowUpAction(TinyPHP_Request $request) {

        $itemId = $request->getInput("id", "Int", 0);
        $action = $request->getInput("action", "String", "");
        $qty    = (float) $request->getInput("qty", "String", "0");
        $notes  = $request->getInput("notes", "String", "") ?: null;

        $this->service()->processFollowUp($itemId, $action, $qty, $notes);
        $label = $action === 'restock' ? 'Items restocked to stock' : 'Items scrapped';
        return response([], $label)->sendJson();
    }


    public function statusAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $newStatus = $request->getInput("status", "String", "");
        $service = $this->service();

        if ($newStatus === 'in_transit') {
            $service->markInTransit($id);
            return response([], "Return marked as in transit")->sendJson();
        }

        if ($newStatus === 'cancelled') {
            $service->cancel($id);
            return response([], "Return cancelled successfully")->sendJson();
        }

        if ($newStatus === 'received') {
            $service->receive($id);
            return response([], "Return received — inventory updated")->sendJson();
        }

        return response([], "Invalid status", 422)->sendJson();
    }


}
?>
