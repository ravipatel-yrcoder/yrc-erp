<?php
class Api_QuotationsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }


    // GET /api/quotations
    public function indexAction(TinyPHP_Request $request) {

        if( !$request->isMethod("get") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        $companyId = auth()->getCompanyId();
        $leadId = $request->getInput("lead_id", "Int", 0);

        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = [
            "id" => "so.id",
            "so_number" => "so.so_number",
            "order_date" => "so.order_date",
            "customer" => "c.display_name",
            "reference" => "so.reference",
            "status" => "so.status",
            "total_amount" => "so.total_amount",
            "lead_id" => "so.lead_id",
        ];

        $dataFetch
            ->table("sales_orders AS so")
            ->joins("LEFT JOIN customers AS c ON so.customer_id = c.id")
            ->columns($columns)
            ->where("so.company_id = ?", [$companyId])
            ->where("so.status = ?", ['draft']);

        if( $leadId > 0 ) {
            $dataFetch->where("so.lead_id = ?", [$leadId]);
        }

        $results = $dataFetch->fetch();

        response($results)->sendJson();
    }
}
