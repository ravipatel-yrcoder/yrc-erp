<?php
class Api_QuotationsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function serviceSalesOrder(): Service_So_Order {
        return new Service_So_Order(tenantContext());
    }

    // GET /api/quotations
    public function indexAction(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;
        $leadId = $request->getInput("lead_id", "Int", 0);

        // open = draft quotations | converted = confirmed+ | empty (default) = all statuses
        $quoteStatus  = $request->getInput("filter_quote_status", "String", "");

        // expired | today | soon (7 days) | empty = no expiry filter
        $filterExpiry = $request->getInput("filter_quote_expiry", "String", "");
        $today        = date('Y-m-d');

        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = [
            "id"             => "so.id",
            "so_number"      => "so.so_number",
            "quote_date"     => "so.quote_date",
            "valid_until"    => "so.valid_until",
            "order_date"     => "so.order_date",
            "customer"       => "c.display_name",
            "reference"      => "so.reference",
            "status"         => "so.status",
            "quote_sent"     => "so.quote_sent",
            "grand_total"    => "so.grand_total",
            "lead_id"        => "so.lead_id",
            "created_by_name"=> "u.name",
        ];

        $dataFetch
            ->table("sales_orders AS so")
            ->joins("LEFT JOIN customers AS c ON so.customer_id = c.id LEFT JOIN users AS u ON u.id = so.created_by")
            ->columns($columns)
            ->where("so.company_id = ?", [$companyId])
            ->where("so.origin_type = ?", ['quotation']);

        // Status scope
        if ($quoteStatus === 'open') {
            $dataFetch->where("so.status = ?", ['draft']);
        } elseif ($quoteStatus === 'converted') {
            $dataFetch->where("so.status != ?", ['draft']);
        }
        // Expiry filter (only meaningful for open/draft quotations)
        if ($filterExpiry === 'expired') {
            $dataFetch->where("so.valid_until IS NOT NULL AND so.valid_until < ?", [$today]);
        } elseif ($filterExpiry === 'today') {
            $dataFetch->where("so.valid_until = ?", [$today]);
        } elseif ($filterExpiry === 'soon') {
            $threshold = date('Y-m-d', strtotime('+7 days'));
            $dataFetch->where("so.valid_until IS NOT NULL AND so.valid_until BETWEEN ? AND ?", [$today, $threshold]);
        }

        $scope = $this->serviceSalesOrder()->getScopeCondition('sales_orders', ['so.salesperson_id', 'so.created_by']);
        if ($scope['sql']) {
            $dataFetch->where($scope['sql'], $scope['bindings']);
        }

        if( $leadId > 0 ) {
            $dataFetch->where("so.lead_id = ?", [$leadId]);
        }

        $results = $dataFetch->fetch();

        return response($results)->sendJson();
    }
}
