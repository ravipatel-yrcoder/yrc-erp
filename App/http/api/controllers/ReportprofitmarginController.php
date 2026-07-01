<?php
class Api_ReportProfitMarginController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    public function lineItemsAction(TinyPHP_Request $request) {

        $ctx       = tenantContext();
        $companyId = $ctx->companyId;

        $columns = [
            'so_id'         => 'so.id',
            'order_date'    => 'so.order_date',
            'so_number'     => 'so.so_number',
            'customer_name' => 'c.display_name',
            'product_name'  => 'COALESCE(soi.product_name, p.name)',
            'product_sku'   => 'COALESCE(soi.product_sku, p.sku)',
            'uom_code'      => 'soi.uom_code',
            'net_qty'       => '(soi.ordered_qty - COALESCE(ret.total_returned_qty, 0))',
            'unit_price'    => 'soi.unit_price',
            'unit_cost'     => 'soi.actual_cost',
            'revenue'       => 'ROUND(soi.line_total - COALESCE(ret.total_return_value, 0), 4)',
            'cogs'          => 'ROUND(COALESCE(soi.actual_cost, soi.planned_cost, 0) * (soi.ordered_qty - COALESCE(ret.total_returned_qty, 0)), 4)',
            'gross_margin'  => 'ROUND((soi.line_total - COALESCE(ret.total_return_value, 0)) - (COALESCE(soi.actual_cost, soi.planned_cost, 0) * (soi.ordered_qty - COALESCE(ret.total_returned_qty, 0))), 4)',
        ];

        // Aggregate only received returns — draft/in_transit returns do not affect P&L
        $returnJoin = ' LEFT JOIN ('
            . ' SELECT ri.reference_item_id,'
            . '        SUM(ri.return_qty) AS total_returned_qty,'
            . '        SUM(ri.return_qty * ri.unit_price) AS total_return_value'
            . ' FROM return_items ri'
            . ' JOIN returns r ON r.id = ri.return_id'
            . " WHERE ri.company_id = ? AND r.status = 'received'"
            . ' GROUP BY ri.reference_item_id'
            . ') ret ON ret.reference_item_id = soi.id';

        $joins = 'INNER JOIN sales_orders so ON so.id = soi.sales_order_id'
               . ' INNER JOIN products p ON p.id = soi.product_id'
               . ' LEFT JOIN customers c ON c.id = so.customer_id'
               . $returnJoin;

        $filters = $this->resolveFilters($request, $ctx);

        $dataFetch = new TinyPHP_DataFetch($request);
        $dataFetch
            ->table('sales_order_items AS soi')
            ->joins($joins, [$companyId])
            ->columns($columns)
            ->where('so.company_id = ?', [$companyId])
            ->where("so.status NOT IN ('draft', 'cancelled')");

        foreach ($filters as $f) {
            $dataFetch->where($f['cond'], $f['bind']);
        }

        $result = $dataFetch->fetch();

        // Append margin_pct to each row
        foreach ($result['data'] as $row) {
            $row->margin_pct = ($row->revenue > 0)
                ? round(($row->gross_margin / $row->revenue) * 100, 2)
                : null;
        }

        // Totals query — same WHERE + search as DataFetch, no LIMIT, aggregated over all matching rows
        $whereParts = ["so.company_id = ?", "so.status NOT IN ('draft', 'cancelled')"];
        $whereBind  = [$companyId];
        foreach ($filters as $f) {
            $whereParts[] = $f['cond'];
            $whereBind    = array_merge($whereBind, $f['bind']);
        }
        $searchCond = $this->resolveSearchCondition($request, $columns);
        if ($searchCond['cond']) {
            $whereParts[] = $searchCond['cond'];
            $whereBind    = array_merge($whereBind, $searchCond['bind']);
        }
        $whereClause = 'WHERE ' . implode(' AND ', $whereParts);

        // $companyId prepended first for the returns subquery binding in $joins
        $totalsBind = array_merge([$companyId], $whereBind);

        $totalsSql = "
            SELECT
                ROUND(SUM(soi.line_total - COALESCE(ret.total_return_value, 0)), 4) AS revenue,
                ROUND(SUM(COALESCE(soi.actual_cost, soi.planned_cost, 0) * (soi.ordered_qty - COALESCE(ret.total_returned_qty, 0))), 4) AS cogs,
                ROUND(SUM((soi.line_total - COALESCE(ret.total_return_value, 0)) - (COALESCE(soi.actual_cost, soi.planned_cost, 0) * (soi.ordered_qty - COALESCE(ret.total_returned_qty, 0)))), 4) AS gross_margin
            FROM sales_order_items soi
            {$joins}
            {$whereClause}
        ";
        $totalsRow = DB()->fetchOne($totalsSql, $totalsBind);

        $result['totals'] = [
            'revenue'      => (float) ($totalsRow->revenue      ?? 0),
            'cogs'         => (float) ($totalsRow->cogs         ?? 0),
            'gross_margin' => (float) ($totalsRow->gross_margin ?? 0),
        ];

        return response($result)->sendJson();
    }


    // Mirrors the search LIKE logic inside TinyPHP_DataFetch so the totals query
    // applies the same search term that DataFetch applies to the paginated data.
    private function resolveSearchCondition(TinyPHP_Request $request, array $fetchColumns): array {
        if (!$request->hasInput('search')) {
            return ['cond' => '', 'bind' => []];
        }

        $search    = $request->getInput('search', 'array', []);
        $searchVal = $search['value'] ?? '';
        if (!$searchVal) {
            return ['cond' => '', 'bind' => []];
        }

        $dtColumns  = $request->getInput('columns', 'array', []);
        $likeConds  = [];
        $likeBinds  = [];

        foreach ($dtColumns as $dtColumn) {
            if (empty($dtColumn['searchable'])) continue;
            $colKey = ($dtColumn['name'] ?? '') ?: ($dtColumn['data'] ?? '');
            if (!$colKey || !isset($fetchColumns[$colKey])) continue;
            $likeConds[] = $fetchColumns[$colKey] . ' LIKE ?';
            $likeBinds[] = '%' . $searchVal . '%';
        }

        if (!$likeConds) {
            return ['cond' => '', 'bind' => []];
        }

        return ['cond' => '(' . implode(' OR ', $likeConds) . ')', 'bind' => $likeBinds];
    }


    private function resolveFilters(TinyPHP_Request $request, $ctx): array {
        $filters = [];
        $today   = dateNow('Y-m-d');

        $datePreset = $request->getInput('date_preset', 'String', 'this_month');
        $dateFrom   = $request->getInput('date_from',   'String', '');
        $dateTo     = $request->getInput('date_to',     'String', '');

        if ($datePreset && $datePreset !== 'custom') {
            switch ($datePreset) {
                case 'today':
                    $filters[] = ['cond' => 'DATE(so.order_date) = ?', 'bind' => [$today]];
                    break;
                case 'this_month':
                    $filters[] = ['cond' => 'DATE(so.order_date) BETWEEN ? AND ?', 'bind' => [dateNow('Y-m-01'), $today]];
                    break;
                case 'last_month':
                    $filters[] = ['cond' => 'DATE(so.order_date) BETWEEN ? AND ?', 'bind' => [
                        dateNow('Y-m-01', 'first day of last month'),
                        dateNow('Y-m-t',  'last day of last month'),
                    ]];
                    break;
                case 'last_3_months':
                    $filters[] = ['cond' => 'DATE(so.order_date) BETWEEN ? AND ?', 'bind' => [dateNow('Y-m-d', '-3 months'), $today]];
                    break;
                case 'this_year':
                    $filters[] = ['cond' => 'DATE(so.order_date) BETWEEN ? AND ?', 'bind' => [dateNow('Y-01-01'), dateNow('Y-12-31')]];
                    break;
            }
        } elseif ($dateFrom || $dateTo) {
            if ($dateFrom && $dateTo) {
                $filters[] = ['cond' => 'DATE(so.order_date) BETWEEN ? AND ?', 'bind' => [$dateFrom, $dateTo]];
            } elseif ($dateFrom) {
                $filters[] = ['cond' => 'DATE(so.order_date) >= ?', 'bind' => [$dateFrom]];
            } else {
                $filters[] = ['cond' => 'DATE(so.order_date) <= ?', 'bind' => [$dateTo]];
            }
        }

        $customerId = $request->getInput('customer_id', 'Int', 0);
        if ($customerId > 0) {
            $filters[] = ['cond' => 'so.customer_id = ?', 'bind' => [$customerId]];
        }

        $productId = $request->getInput('product_id', 'Int', 0);
        if ($productId > 0) {
            $filters[] = ['cond' => 'soi.product_id = ?', 'bind' => [$productId]];
        }

        $salespersonId = $request->getInput('salesperson_id', 'Int', 0);
        if ($salespersonId > 0 && in_array($ctx->scopeFor('reporting_profit_margin'), ['team', 'all'])) {
            $filters[] = ['cond' => '(so.salesperson_id = ? OR so.created_by = ?)', 'bind' => [$salespersonId, $salespersonId]];
        }

        $scope = (new Service_Scope($ctx))->getCondition('reporting_profit_margin', ['so.salesperson_id', 'so.created_by']);
        if ($scope['sql']) {
            $filters[] = ['cond' => $scope['sql'], 'bind' => $scope['bindings']];
        }

        return $filters;
    }
}
