<?php
class Api_InventoryController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function serviceInvMovement(): Service_Inv_Movement {
        return new Service_Inv_Movement(tenantContext());
    }

    public function itemsAction(TinyPHP_Request $request) {
        $companyId        = tenantContext()->companyId;
        $filterLocationId = $request->getInput('location_id', 'Int', 0);

        $stockJoin = $filterLocationId
            ? "LEFT JOIN inv_product_stock AS ips ON ips.product_id = p.id AND ips.company_id = {$companyId} AND ips.location_id = {$filterLocationId}"
            : "LEFT JOIN inv_product_stock AS ips ON ips.product_id = p.id AND ips.company_id = {$companyId}";

        $columns = [
            'id'            => 'p.id',
            'name'          => 'p.name',
            'uom_code'      => 'uom.code',
            'on_hand_qty'   => 'COALESCE(SUM(ips.on_hand_qty), 0)',
            'reserved_qty'  => 'COALESCE(SUM(ips.reserved_qty), 0)',
            'available_qty' => 'COALESCE(SUM(ips.on_hand_qty) - SUM(ips.reserved_qty), 0)',
        ];

        $df = (new TinyPHP_DataFetch($request))
            ->table('products AS p')
            ->joins(
                "INNER JOIN product_masters AS pm ON pm.id = p.master_id
                 LEFT JOIN uoms AS uom ON uom.id = p.base_uom_id
                 {$stockJoin}"
            )
            ->columns($columns)
            ->where('pm.company_id = ? AND pm.status <> ?', [$companyId, 'archived'])
            ->where("p.stock_tracking_method IN ('quantity', 'lot', 'serial')")
            ->groupBy('p.id');

        return response($df->fetch())->sendJson();
    }

    public function movementsAction(TinyPHP_Request $request) {
        $results = $this->serviceInvMovement()->list($request);
        return response($results)->sendJson();
    }

    public function movementsFormContextAction(TinyPHP_Request $request) {
        $data = $this->serviceInvMovement()->getFormContext();
        return response($data)->sendJson();
    }

    public function adjustmentsAction(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;

        $columns = [
            "id"              => "adj.id",
            "location"        => 'CASE WHEN l.code IS NOT NULL AND l.code <> "" THEN CONCAT(l.name, " (", l.code, ")") ELSE l.name END',
            "prod_name"       => "p.name",
            "quantity"        => "adj.quantity",
            "adjustment_type" => "adj.adjustment_type",
            "notes"           => "adj.notes",
            "uom_code"        => "uom.code",
            "created_at"      => "adj.created_at",
            "created_by"      => "u.name",
        ];

        $df = (new TinyPHP_DataFetch($request))
            ->table("inv_adjustments AS adj")
            ->joins(
                "LEFT JOIN products AS p ON p.id = adj.product_id
                 LEFT JOIN uoms AS uom ON uom.id = p.base_uom_id
                 LEFT JOIN company_locations AS l ON l.id = adj.location_id
                 LEFT JOIN users AS u ON u.id = adj.created_by"
            )
            ->columns($columns)
            ->where("adj.company_id = ?", [$companyId]);

        $filterProductId = $request->getInput('product_id', 'Int', 0);
        if ($filterProductId) {
            $df->where('adj.product_id = ?', [$filterProductId]);
        }

        $filterLocationId = $request->getInput('location_id', 'Int', 0);
        if ($filterLocationId) {
            $df->where('adj.location_id = ?', [$filterLocationId]);
        }

        $filterAdjType = $request->getInput('adjustment_type', 'String', '');
        if ($filterAdjType) {
            $df->where('adj.adjustment_type = ?', [$filterAdjType]);
        }

        $filterPerformedBy = $request->getInput('performed_by', 'Int', 0);
        if ($filterPerformedBy) {
            $df->where('adj.created_by = ?', [$filterPerformedBy]);
        }

        $filterDateFrom = $request->getInput('date_from', 'String', '');
        if ($filterDateFrom && strtotime($filterDateFrom)) {
            $df->where('DATE(adj.created_at) >= ?', [$filterDateFrom]);
        }

        $filterDateTo = $request->getInput('date_to', 'String', '');
        if ($filterDateTo && strtotime($filterDateTo)) {
            $df->where('DATE(adj.created_at) <= ?', [$filterDateTo]);
        }

        return response($df->fetch())->sendJson();
    }
}
