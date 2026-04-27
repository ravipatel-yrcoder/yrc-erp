<?php
class Api_InventoryController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    public function adjustmentsAction(TinyPHP_Request $request) {
        
        $companyId = tenantContext()->companyId;

        $columns = [
            "id" => "adj.id",
            "location" => "l.name",
            "location_code" => "l.code",
            "prod_name" => "p.name",
            "quantity" => "adj.quantity",
            "adjustment_type" => "adj.adjustment_type",
            "reason" => "adj.reason",
            "notes" => "adj.notes",
            "uom_code" => "uom.code",
            "uom_name" => "uom.name",
            "created_at" => "adj.created_at",
            "created_by" => "u.name",
        ];

        $dataFetch = new TinyPHP_DataFetch($request);
        $results = $dataFetch
            ->table("inv_adjustments AS adj")
            ->joins("LEFT JOIN products p ON adj.product_id=p.id
            LEFT JOIN uoms AS uom ON uom.id=base_uom_id
            LEFT JOIN company_locations AS l ON adj.location_id=l.id
            LEFT JOIN users AS u ON adj.created_by=u.id")
            ->columns($columns)
            ->where("adj.company_id=?", [$companyId])
            ->fetch();

        return response($results)->sendJson();
    }
}
