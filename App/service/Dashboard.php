<?php
class Service_Dashboard extends Service_Base {

    // ─── Admin / Manager summary (existing KPI cards) ────────────────────────

    public function getSummary(string $dateFrom, string $dateTo): array {
        $ctx       = $this->context;
        $companyId = $ctx->companyId;
        $userId    = $ctx->userId;
        $today     = dateNow('Y-m-d');

        $dtFrom = localToUtc($dateFrom . ' 00:00:00');
        $dtTo   = localToUtc($dateTo   . ' 23:59:59');

        $result = [
            'business_alerts' => $this->getBusinessAlerts($companyId, $today),
        ];

        if ($ctx->hasRoleModule('crm') && $ctx->canAccess('crm_leads')) {
            $result['crm'] = $this->getCrmStats($companyId, $dtFrom, $dtTo);
        }

        if ($ctx->hasRoleModule('sales') && $ctx->canAccess('sales_orders')) {
            $result['sales'] = $this->getSalesStats($companyId, $dateFrom, $dateTo);
        }

        if ($ctx->hasRoleModule('purchasing') && $ctx->canAccess('purchase_orders')) {
            $result['purchasing'] = $this->getPurchasingStats($companyId);
        }

        if ($ctx->hasRoleModule('manufacturing') && $ctx->canAccess('manufacturing_orders')) {
            $result['manufacturing'] = $this->getManufacturingStats($companyId, $dateFrom, $dateTo);
        }

        if ($ctx->canAccess('customers')) {
            $result['customers'] = $this->getCustomerStats($companyId, $dtFrom, $dtTo);
        }

        return $result;
    }


    // ─── Operator summary ─────────────────────────────────────────────────────

    public function getOperatorSummary(string $period = 'month'): array {
        [$periodStart, $periodEnd] = $this->getPeriodDates($period);

        return [
            'my_work'     => $this->buildMyWork(),
            'performance' => $this->buildPerformance($periodStart, $periodEnd),
            'period'      => $period,
        ];
    }


    // ─── My Work sections ────────────────────────────────────────────────────

    private function buildMyWork(): array {
        $ctx       = $this->context;
        $companyId = $ctx->companyId;
        $userId    = $ctx->userId;
        $today     = dateNow('Y-m-d');

        $hasSalesOrders       = $ctx->hasRoleModule('sales')        && $ctx->canAccess('sales_orders');
        $hasSalesDelivery     = $ctx->hasRoleModule('sales')        && $ctx->canAccess('sales_deliveries');
        $hasPurchaseOrders    = $ctx->hasRoleModule('purchasing')   && $ctx->canAccess('purchase_orders');
        $hasPurchaseReceipts  = $ctx->hasRoleModule('purchasing')   && $ctx->canAccess('purchase_receipts');
        $hasPurchaseInquiries = $ctx->hasRoleModule('purchasing')   && $ctx->canAccess('purchase_inquiries');
        $hasCrmLeads          = $ctx->hasRoleModule('crm')          && $ctx->canAccess('crm_leads');
        $hasMfgOrders         = $ctx->hasRoleModule('manufacturing') && $ctx->canAccess('manufacturing_orders');

        // Sales deliveries and purchase receipts share scope with their parent order
        $soScope = $hasSalesDelivery
            ? (new Service_Scope($this->context))->getCondition('sales_orders', ['so.salesperson_id', 'so.created_by'])
            : null;

        $quotScope = $hasSalesOrders
            ? (new Service_Scope($this->context))->getCondition('sales_orders', ['so.salesperson_id', 'so.created_by'])
            : null;

        $expiryThreshold = date('Y-m-d', strtotime('+7 days'));

        return [
            'overdue' => [
                'activities' => $this->countOverdueActivities($companyId, $userId, $today),
                'deliveries' => $hasSalesDelivery
                    ? $this->countDelayedDeliveries($companyId, $today, $soScope, false)
                    : null,
                'receipts'   => $hasPurchaseReceipts
                    ? $this->countDelayedReceipts($companyId, $today, false)
                    : null,
                'mfg_orders' => $hasMfgOrders
                    ? $this->countOverdueMOs($companyId, $today)
                    : null,
            ],
            'today' => [
                'activities' => $this->countActivitiesToday($companyId, $userId, $today),
                'deliveries' => $hasSalesDelivery
                    ? $this->countDelayedDeliveries($companyId, $today, $soScope, true)
                    : null,
                'receipts'   => $hasPurchaseReceipts
                    ? $this->countDelayedReceipts($companyId, $today, true)
                    : null,
            ],
            'pending' => [
                'leads'                  => $hasCrmLeads
                    ? $this->countOpenLeads($companyId)
                    : null,
                'quotations'                => $hasSalesOrders
                    ? $this->countQuotations($companyId)
                    : null,
                'expired_quotations'        => $hasSalesOrders
                    ? $this->countExpiredQuotations($companyId, $today, $quotScope)
                    : null,
                'expiring_today_quotations' => $hasSalesOrders
                    ? $this->countExpiringTodayQuotations($companyId, $today, $quotScope)
                    : null,
                'expiring_quotations'       => $hasSalesOrders
                    ? $this->countExpiringQuotations($companyId, $today, $expiryThreshold, $quotScope)
                    : null,
                'sales_orders'           => $hasSalesOrders
                    ? $this->countOpenSalesOrders($companyId)
                    : null,
                'purchase_orders'        => $hasPurchaseOrders
                    ? $this->countOpenPurchaseOrders($companyId)
                    : null,
                'purchase_inquiries'     => $hasPurchaseInquiries
                    ? $this->countOpenInquiries($companyId)
                    : null,
                'mfg_orders'             => $hasMfgOrders
                    ? $this->countOpenMOs($companyId)
                    : null,
                'unscheduled_activities' => $this->countUnscheduledActivities($companyId, $userId),
            ],
        ];
    }


    // Activities are always scoped to assigned_to = current user
    private function countOverdueActivities(int $companyId, int $userId, string $today): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM activities
             WHERE company_id = ? AND assigned_to = ? AND status IN ('pending','in_progress') AND due_date < ?",
            [$companyId, $userId, $today]
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countActivitiesToday(int $companyId, int $userId, string $today): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM activities
             WHERE company_id = ? AND assigned_to = ? AND status IN ('pending','in_progress') AND due_date = ?",
            [$companyId, $userId, $today]
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countUnscheduledActivities(int $companyId, int $userId): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM activities
             WHERE company_id = ? AND assigned_to = ? AND status IN ('pending','in_progress') AND due_date IS NULL",
            [$companyId, $userId]
        );
        return (int) ($row->cnt ?? 0);
    }

    /**
     * Delayed/due deliveries — driven by sales_orders.expected_delivery_date.
     * $dueToday=true  → expected_delivery_date = today (due)
     * $dueToday=false → expected_delivery_date < today (delayed/overdue)
     * Scope inherited from parent SO.
     */
    private function countDelayedDeliveries(int $companyId, string $today, ?array $scope, bool $dueToday): int {
        $op     = $dueToday ? '=' : '<';
        $where  = "so.company_id = ? AND so.expected_delivery_date {$op} ? AND so.status NOT IN ('delivered','cancelled')";
        $params = [$companyId, $today];

        if ($scope && $scope['sql']) {
            $where  .= " AND " . $scope['sql'];
            $params  = array_merge($params, $scope['bindings']);
        }

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM sales_orders AS so WHERE {$where}",
            $params
        );
        return (int) ($row->cnt ?? 0);
    }

    /**
     * Delayed/due receipts — driven by purchase_orders.expected_delivery_date.
     * Purchase orders are company-wide (no per-user scope).
     */
    private function countDelayedReceipts(int $companyId, string $today, bool $dueToday): int {
        $op  = $dueToday ? '=' : '<';
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM purchase_orders
             WHERE company_id = ? AND expected_delivery_date {$op} ? AND status NOT IN ('received','cancelled')",
            [$companyId, $today]
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countOpenLeads(int $companyId): int {
        $scope  = (new Service_Scope($this->context))->getCondition('crm_leads', ['l.assigned_to', 'l.created_by']);
        $where  = "l.company_id = ? AND l.status = 'active'";
        $params = [$companyId];

        if ($scope['sql']) {
            $where  .= " AND " . $scope['sql'];
            $params  = array_merge($params, $scope['bindings']);
        }

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM crm_leads AS l WHERE {$where}",
            $params
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countQuotations(int $companyId): int {
        $scope  = (new Service_Scope($this->context))->getCondition('sales_orders', ['so.salesperson_id', 'so.created_by']);
        $where  = "so.company_id = ? AND so.origin_type = 'quotation' AND so.status = 'draft'";
        $params = [$companyId];

        if ($scope['sql']) {
            $where  .= " AND " . $scope['sql'];
            $params  = array_merge($params, $scope['bindings']);
        }

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM sales_orders AS so WHERE {$where}",
            $params
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countOpenSalesOrders(int $companyId): int {
        $scope  = (new Service_Scope($this->context))->getCondition('sales_orders', ['so.salesperson_id', 'so.created_by']);
        $where  = "so.company_id = ? AND so.status = 'confirmed'";
        $params = [$companyId];

        if ($scope['sql']) {
            $where  .= " AND " . $scope['sql'];
            $params  = array_merge($params, $scope['bindings']);
        }

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM sales_orders AS so WHERE {$where}",
            $params
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countOpenPurchaseOrders(int $companyId): int {
        // Purchase orders are company-wide — no per-user scope
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM purchase_orders
             WHERE company_id = ? AND status IN ('draft','confirmed')",
            [$companyId]
        );
        return (int) ($row->cnt ?? 0);
    }


    // ─── My Performance ──────────────────────────────────────────────────────

    private function buildPerformance(string $periodStart, string $periodEnd): array {
        $ctx       = $this->context;
        $companyId = $ctx->companyId;
        $userId    = $ctx->userId;

        $hasCrmLeads      = $ctx->hasRoleModule('crm')   && $ctx->canAccess('crm_leads');
        $hasSalesOrders   = $ctx->hasRoleModule('sales')  && $ctx->canAccess('sales_orders');
        $hasSalesDelivery = $ctx->hasRoleModule('sales')  && $ctx->canAccess('sales_deliveries');

        $crmScope = $hasCrmLeads
            ? (new Service_Scope($this->context))->getCondition('crm_leads', ['l.assigned_to', 'l.created_by'])
            : null;

        $soScope = ($hasSalesOrders || $hasSalesDelivery)
            ? (new Service_Scope($this->context))->getCondition('sales_orders', ['so.salesperson_id', 'so.created_by'])
            : null;

        return [
            'won_leads'          => $hasCrmLeads
                ? $this->countWonLeads($companyId, $periodStart, $periodEnd, $crmScope)
                : null,
            'lost_leads'         => $hasCrmLeads
                ? $this->countLostLeads($companyId, $periodStart, $periodEnd, $crmScope)
                : null,
            'sales_orders'       => $hasSalesOrders
                ? $this->countSalesOrdersCreated($companyId, $periodStart, $periodEnd, $soScope)
                : null,
            'deliveries_completed' => $hasSalesDelivery
                ? $this->countDeliveriesCompleted($companyId, $periodStart, $periodEnd, $soScope)
                : null,
            'completed_activities' => $this->countCompletedActivities($companyId, $userId, $periodStart, $periodEnd),
        ];
    }

    private function countWonLeads(int $companyId, string $start, string $end, ?array $scope): int {
        $where  = "l.company_id = ? AND l.status = 'won' AND l.converted_at BETWEEN ? AND ?";
        $params = [$companyId, $start, $end];

        if ($scope && $scope['sql']) {
            $where  .= " AND " . $scope['sql'];
            $params  = array_merge($params, $scope['bindings']);
        }

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM crm_leads AS l WHERE {$where}",
            $params
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countLostLeads(int $companyId, string $start, string $end, ?array $scope): int {
        $where  = "l.company_id = ? AND l.status = 'lost' AND l.closed_at BETWEEN ? AND ?";
        $params = [$companyId, $start, $end];

        if ($scope && $scope['sql']) {
            $where  .= " AND " . $scope['sql'];
            $params  = array_merge($params, $scope['bindings']);
        }

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM crm_leads AS l WHERE {$where}",
            $params
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countSalesOrdersCreated(int $companyId, string $start, string $end, ?array $scope): int {
        $where  = "so.company_id = ? AND so.origin_type = 'order' AND so.created_at BETWEEN ? AND ?";
        $params = [$companyId, $start, $end];

        if ($scope && $scope['sql']) {
            $where  .= " AND " . $scope['sql'];
            $params  = array_merge($params, $scope['bindings']);
        }

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM sales_orders AS so WHERE {$where}",
            $params
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countDeliveriesCompleted(int $companyId, string $start, string $end, ?array $scope): int {
        // Scope inherited from parent SO
        $where  = "dn.company_id = ? AND dn.status = 'completed' AND dn.updated_at BETWEEN ? AND ?";
        $params = [$companyId, $start, $end];

        if ($scope && $scope['sql']) {
            $where  .= " AND " . $scope['sql'];
            $params  = array_merge($params, $scope['bindings']);
        }

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM sales_deliveries AS dn
             LEFT JOIN sales_orders AS so ON so.id = dn.sales_order_id
             WHERE {$where}",
            $params
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countCompletedActivities(int $companyId, int $userId, string $start, string $end): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM activities
             WHERE company_id = ? AND assigned_to = ? AND status = 'completed' AND completed_at BETWEEN ? AND ?",
            [$companyId, $userId, $start, $end]
        );
        return (int) ($row->cnt ?? 0);
    }


    // ─── Period helper ───────────────────────────────────────────────────────

    private function getPeriodDates(string $period): array {
        $today = dateNow('Y-m-d');
        switch ($period) {
            case 'today':
                return [localToUtc("{$today} 00:00:00"), localToUtc("{$today} 23:59:59")];
            case 'week':
                $monday = dateNow('Y-m-d', 'monday this week');
                $sunday = dateNow('Y-m-d', 'sunday this week');
                return [localToUtc("{$monday} 00:00:00"), localToUtc("{$sunday} 23:59:59")];
            case 'month':
            default:
                return [localToUtc(dateNow('Y-m-01') . ' 00:00:00'), localToUtc(dateNow('Y-m-t') . ' 23:59:59')];
        }
    }


    // ─── Admin summary private methods (unchanged) ───────────────────────────

    private function getCrmStats(int $companyId, string $dtFrom, string $dtTo): array {
        $scope = (new Service_Scope($this->context))->getCondition('crm_leads', ['l.assigned_to', 'l.created_by']);

        // Pipeline value + active leads — filtered by date range (created_at)
        $activeWhere  = "l.company_id = ? AND l.status = 'active' AND l.created_at BETWEEN ? AND ?";
        $activeParams = [$companyId, $dtFrom, $dtTo];
        if ($scope['sql']) {
            $activeWhere  .= " AND " . $scope['sql'];
            $activeParams  = array_merge($activeParams, $scope['bindings']);
        }
        $activeRow = $this->db->fetchOne(
            "SELECT COALESCE(SUM(l.expected_revenue), 0) AS pipeline_value, COUNT(*) AS active_count
             FROM crm_leads AS l WHERE {$activeWhere}",
            $activeParams
        );

        // Won revenue — filtered by date range (converted_at)
        $wonWhere  = "l.company_id = ? AND l.status = 'won' AND l.converted_at BETWEEN ? AND ?";
        $wonParams = [$companyId, $dtFrom, $dtTo];
        if ($scope['sql']) {
            $wonWhere  .= " AND " . $scope['sql'];
            $wonParams  = array_merge($wonParams, $scope['bindings']);
        }
        $wonRow = $this->db->fetchOne(
            "SELECT COALESCE(SUM(l.won_revenue), 0) AS won_revenue FROM crm_leads AS l WHERE {$wonWhere}",
            $wonParams
        );

        // Conversion rate — filtered by date range (converted_at for won, closed_at for lost)
        $closedWhere  = "l.company_id = ? AND ((l.status = 'won' AND l.converted_at BETWEEN ? AND ?) OR (l.status = 'lost' AND l.closed_at BETWEEN ? AND ?))";
        $closedParams = [$companyId, $dtFrom, $dtTo, $dtFrom, $dtTo];
        if ($scope['sql']) {
            $closedWhere  .= " AND " . $scope['sql'];
            $closedParams  = array_merge($closedParams, $scope['bindings']);
        }
        $closedRow = $this->db->fetchOne(
            "SELECT SUM(CASE WHEN l.status = 'won' THEN 1 ELSE 0 END) AS won_count, COUNT(*) AS total_closed
             FROM crm_leads AS l WHERE {$closedWhere}",
            $closedParams
        );
        $wonCount    = (int) ($closedRow->won_count ?? 0);
        $totalClosed = (int) ($closedRow->total_closed ?? 0);
        $conversionRate = $totalClosed > 0 ? round(($wonCount / $totalClosed) * 100, 1) : null;

        return [
            'pipeline_value'      => (float) ($activeRow->pipeline_value ?? 0),
            'pipeline_value_fmt'  => formatIndian($activeRow->pipeline_value ?? 0),
            'active_leads'        => (int)   ($activeRow->active_count ?? 0),
            'won_revenue'         => (float) ($wonRow->won_revenue ?? 0),
            'won_revenue_fmt'     => formatIndian($wonRow->won_revenue ?? 0),
            'conversion_rate'     => $conversionRate,
        ];
    }

    private function getSalesStats(int $companyId, string $dateFrom, string $dateTo): array {
        $scope = (new Service_Scope($this->context))->getCondition('sales_orders', ['so.salesperson_id', 'so.created_by']);

        // Confirmed revenue, avg order value, total orders — filtered by date range (order_date)
        $rangeWhere  = "so.company_id = ? AND so.status NOT IN ('draft','cancelled') AND so.order_date BETWEEN ? AND ?";
        $rangeParams = [$companyId, $dateFrom, $dateTo];
        if ($scope['sql']) {
            $rangeWhere  .= " AND " . $scope['sql'];
            $rangeParams  = array_merge($rangeParams, $scope['bindings']);
        }
        $rangeRow = $this->db->fetchOne(
            "SELECT COALESCE(SUM(so.grand_total), 0) AS revenue,
                    COALESCE(AVG(so.grand_total), 0) AS avg_order_value,
                    COUNT(*) AS total_orders
             FROM sales_orders AS so WHERE {$rangeWhere}",
            $rangeParams
        );

        // Quotation pipeline — filtered by date range (quote_date)
        $quotWhere  = "so.company_id = ? AND so.origin_type = 'quotation' AND so.status = 'draft' AND so.quote_date BETWEEN ? AND ?";
        $quotParams = [$companyId, $dateFrom, $dateTo];
        if ($scope['sql']) {
            $quotWhere  .= " AND " . $scope['sql'];
            $quotParams  = array_merge($quotParams, $scope['bindings']);
        }
        $quotRow = $this->db->fetchOne(
            "SELECT COALESCE(SUM(so.grand_total), 0) AS pipeline FROM sales_orders AS so WHERE {$quotWhere}",
            $quotParams
        );

        // Returns — filtered by date range (return_date), received status only
        $retRow = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT r.id) AS returns_count,
                    COALESCE(SUM(ri.line_total), 0) AS returns_total
             FROM returns AS r
             LEFT JOIN return_items AS ri ON ri.return_id = r.id AND ri.company_id = ?
             WHERE r.company_id = ? AND r.status = 'received' AND r.return_date BETWEEN ? AND ?",
            [$companyId, $companyId, $dateFrom, $dateTo]
        );

        return [
            'confirmed_revenue'      => (float) ($rangeRow->revenue ?? 0),
            'confirmed_revenue_fmt'  => formatIndian($rangeRow->revenue ?? 0),
            'avg_order_value'        => (float) ($rangeRow->avg_order_value ?? 0),
            'avg_order_value_fmt'    => formatIndian($rangeRow->avg_order_value ?? 0),
            'total_orders'           => (int)   ($rangeRow->total_orders ?? 0),
            'quotation_pipeline'     => (float) ($quotRow->pipeline ?? 0),
            'quotation_pipeline_fmt' => formatIndian($quotRow->pipeline ?? 0),
            'returns_count'          => (int)   ($retRow->returns_count ?? 0),
            'returns_total'          => (float) ($retRow->returns_total ?? 0),
            'returns_total_fmt'      => formatIndian($retRow->returns_total ?? 0),
        ];
    }

    private function getPurchasingStats(int $companyId): array {
        $ctx   = $this->context;
        $today = dateNow('Y-m-d');

        $open = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM purchase_orders
             WHERE company_id = ? AND status IN ('draft','confirmed')",
            [$companyId]
        );
        $overdue = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM purchase_orders
             WHERE company_id = ? AND expected_delivery_date < ? AND status NOT IN ('received','cancelled')",
            [$companyId, $today]
        );

        $openPiCount = null;
        if ($ctx->canAccess('purchase_inquiries')) {
            $piRow = $this->db->fetchOne(
                "SELECT COUNT(*) AS cnt FROM purchase_inquiries
                 WHERE company_id = ? AND status NOT IN ('awarded','cancelled')",
                [$companyId]
            );
            $openPiCount = (int) ($piRow->cnt ?? 0);
        }

        return [
            'open_po_count'    => (int) ($open->cnt ?? 0),
            'overdue_receipts' => (int) ($overdue->cnt ?? 0),
            'open_pi_count'    => $openPiCount,
        ];
    }

    private function getInventoryStats(int $companyId, string $monthStart, string $monthEnd): array {
        $stock = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT product_id) AS cnt FROM inv_product_stock
             WHERE company_id = ? AND unrestricted_qty > 0",
            [$companyId]
        );
        $movements = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM inv_stock_movements
             WHERE company_id = ? AND created_at BETWEEN ? AND ?",
            [$companyId, $monthStart, $monthEnd]
        );
        return [
            'products_in_stock' => (int) ($stock->cnt ?? 0),
            'movements_month'   => (int) ($movements->cnt ?? 0),
        ];
    }

    private function getCustomerStats(int $companyId, string $dtFrom, string $dtTo): array {
        // Active count — all time
        $activeRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS active_count FROM customers WHERE company_id = ? AND status = 'active'",
            [$companyId]
        );
        // New customers — filtered by date range (created_at)
        $newRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS new_count FROM customers
             WHERE company_id = ? AND status = 'active' AND created_at BETWEEN ? AND ?",
            [$companyId, $dtFrom, $dtTo]
        );
        return [
            'active_count' => (int) ($activeRow->active_count ?? 0),
            'new_count'    => (int) ($newRow->new_count ?? 0),
        ];
    }

    public function getTopCustomersByRevenue(int $companyId, int $limit = 10): array {
        $rows = $this->db->fetchAll(
            "SELECT c.display_name AS name, COALESCE(SUM(so.grand_total), 0) AS revenue
             FROM customers AS c
             JOIN sales_orders AS so ON so.customer_id = c.id
             WHERE c.company_id = ? AND so.status NOT IN ('draft', 'cancelled')
             GROUP BY c.id, c.display_name
             ORDER BY revenue DESC
             LIMIT ?",
            [$companyId, $limit]
        );
        return array_map(fn($r) => ['name' => $r->name, 'revenue' => (float) $r->revenue], $rows);
    }

    public function getLeadsByMonth(int $companyId, int $year): array {
        $newRows  = $this->db->fetchAll(
            "SELECT MONTH(created_at) AS month, COUNT(*) AS cnt
             FROM crm_leads WHERE company_id = ? AND YEAR(created_at) = ?
             GROUP BY MONTH(created_at)",
            [$companyId, $year]
        );
        $wonRows  = $this->db->fetchAll(
            "SELECT MONTH(converted_at) AS month, COUNT(*) AS cnt
             FROM crm_leads WHERE company_id = ? AND status = 'won' AND YEAR(converted_at) = ?
             GROUP BY MONTH(converted_at)",
            [$companyId, $year]
        );
        $lostRows = $this->db->fetchAll(
            "SELECT MONTH(closed_at) AS month, COUNT(*) AS cnt
             FROM crm_leads WHERE company_id = ? AND status = 'lost' AND YEAR(closed_at) = ?
             GROUP BY MONTH(closed_at)",
            [$companyId, $year]
        );

        $new  = array_fill(1, 12, 0);
        $won  = array_fill(1, 12, 0);
        $lost = array_fill(1, 12, 0);
        foreach ($newRows  as $r) $new[(int)  $r->month] = (int) $r->cnt;
        foreach ($wonRows  as $r) $won[(int)  $r->month] = (int) $r->cnt;
        foreach ($lostRows as $r) $lost[(int) $r->month] = (int) $r->cnt;

        return ['new' => array_values($new), 'won' => array_values($won), 'lost' => array_values($lost)];
    }

    public function getSalesByMonth(int $companyId, int $year): array {
        $rows = $this->db->fetchAll(
            "SELECT MONTH(order_date) AS month, SUM(grand_total) AS total
             FROM sales_orders
             WHERE company_id = ? AND YEAR(order_date) = ?
               AND status NOT IN ('draft', 'cancelled')
             GROUP BY MONTH(order_date)",
            [$companyId, $year]
        );

        $months = array_fill(1, 12, 0.0);
        foreach ($rows as $row) {
            $months[(int) $row->month] = (float) $row->total;
        }
        return array_values($months);
    }

    // ─── Business Alerts (admin view) ────────────────────────────────────────

    private function getBusinessAlerts(int $companyId, string $today): array {
        $ctx = $this->context;

        $hasSalesOrders       = $ctx->hasRoleModule('sales')        && $ctx->canAccess('sales_orders');
        $hasSalesDelivery     = $ctx->hasRoleModule('sales')        && $ctx->canAccess('sales_deliveries');
        $hasPurchaseOrders    = $ctx->hasRoleModule('purchasing')   && $ctx->canAccess('purchase_orders');
        $hasPurchaseInquiries = $ctx->hasRoleModule('purchasing')   && $ctx->canAccess('purchase_inquiries');
        $hasMfgOrders         = $ctx->hasRoleModule('manufacturing') && $ctx->canAccess('manufacturing_orders');

        $expiryThreshold = dateNow('Y-m-d', '+7 days');

        return [
            'revenue'              => $hasSalesOrders       ? $this->getRevenueThisMonth($companyId)                                  : null,
            'delivery_alerts'      => $hasSalesDelivery     ? $this->countDeliveryAlerts($companyId, $today)                          : null,
            'pending_dispatch'     => $hasSalesOrders       ? $this->countPendingDispatch($companyId)                                 : null,
            'quotations'                => $hasSalesOrders       ? $this->countAdminQuotations($companyId)                                 : null,
            'expired_quotations'        => $hasSalesOrders       ? $this->countExpiredQuotations($companyId, $today)                     : null,
            'expiring_today_quotations' => $hasSalesOrders       ? $this->countExpiringTodayQuotations($companyId, $today)               : null,
            'expiring_quotations'       => $hasSalesOrders       ? $this->countExpiringQuotations($companyId, $today, $expiryThreshold)  : null,
            'open_pos'             => $hasPurchaseOrders    ? $this->countDraftPOs($companyId)                                        : null,
            'pending_receipts'     => $hasPurchaseOrders    ? $this->countPendingReceipts($companyId)                                 : null,
            'open_inquiries'       => $hasPurchaseInquiries ? $this->countOpenInquiries($companyId)                                   : null,
            'open_mos'             => $hasMfgOrders         ? $this->countOpenMOs($companyId)                                         : null,
            'overdue_mos'          => $hasMfgOrders         ? $this->countOverdueMOs($companyId, $today)                              : null,
            'open_activities'      => $this->countAllOpenActivities($companyId),
        ];
    }

    private function getRevenueThisMonth(int $companyId): array {
        $thisStart = dateNow('Y-m-01');
        $thisEnd   = dateNow('Y-m-t');
        $lastStart = dateNow('Y-m-01', 'first day of last month');
        $lastEnd   = dateNow('Y-m-t',  'last day of last month');

        $thisRow = $this->db->fetchOne(
            "SELECT COALESCE(SUM(grand_total), 0) AS revenue FROM sales_orders
             WHERE company_id = ? AND status NOT IN ('draft','cancelled') AND order_date BETWEEN ? AND ?",
            [$companyId, $thisStart, $thisEnd]
        );
        $lastRow = $this->db->fetchOne(
            "SELECT COALESCE(SUM(grand_total), 0) AS revenue FROM sales_orders
             WHERE company_id = ? AND status NOT IN ('draft','cancelled') AND order_date BETWEEN ? AND ?",
            [$companyId, $lastStart, $lastEnd]
        );

        $thisMo = (float) ($thisRow->revenue ?? 0);
        $lastMo = (float) ($lastRow->revenue ?? 0);

        $pctChange = null;
        $direction = null;
        if ($lastMo > 0) {
            $pctChange = round((($thisMo - $lastMo) / $lastMo) * 100, 1);
            $direction = $pctChange >= 0 ? 'up' : 'down';
        }

        return [
            'this_month_fmt' => formatIndian($thisMo),
            'pct_change'     => $pctChange,
            'direction'      => $direction,
        ];
    }

    private function countDeliveryAlerts(int $companyId, string $today): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM sales_orders
             WHERE company_id = ? AND expected_delivery_date < ? AND status NOT IN ('delivered','cancelled')",
            [$companyId, $today]
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countPendingDispatch(int $companyId): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM sales_orders
             WHERE company_id = ? AND status IN ('confirmed', 'partially_dispatched')",
            [$companyId]
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countAdminQuotations(int $companyId): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM sales_orders WHERE company_id = ? AND origin_type = 'quotation' AND status = 'draft'",
            [$companyId]
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countExpiredQuotations(int $companyId, string $today, ?array $scope = null): int {
        $where  = "so.company_id = ? AND so.origin_type = 'quotation' AND so.status = 'draft' AND so.valid_until IS NOT NULL AND so.valid_until < ?";
        $params = [$companyId, $today];

        if ($scope && $scope['sql']) {
            $where  .= " AND " . $scope['sql'];
            $params  = array_merge($params, $scope['bindings']);
        }

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM sales_orders AS so WHERE {$where}",
            $params
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countExpiringTodayQuotations(int $companyId, string $today, ?array $scope = null): int {
        $where  = "so.company_id = ? AND so.origin_type = 'quotation' AND so.status = 'draft' AND so.valid_until = ?";
        $params = [$companyId, $today];

        if ($scope && $scope['sql']) {
            $where  .= " AND " . $scope['sql'];
            $params  = array_merge($params, $scope['bindings']);
        }

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM sales_orders AS so WHERE {$where}",
            $params
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countExpiringQuotations(int $companyId, string $today, string $threshold, ?array $scope = null): int {
        $where  = "so.company_id = ? AND so.origin_type = 'quotation' AND so.status = 'draft' AND so.valid_until IS NOT NULL AND so.valid_until BETWEEN ? AND ?";
        $params = [$companyId, $today, $threshold];

        if ($scope && $scope['sql']) {
            $where  .= " AND " . $scope['sql'];
            $params  = array_merge($params, $scope['bindings']);
        }

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM sales_orders AS so WHERE {$where}",
            $params
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countOpenInquiries(int $companyId): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM purchase_inquiries
             WHERE company_id = ? AND status NOT IN ('awarded','cancelled')",
            [$companyId]
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countDraftPOs(int $companyId): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM purchase_orders WHERE company_id = ? AND status = 'draft'",
            [$companyId]
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countPendingReceipts(int $companyId): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM purchase_orders WHERE company_id = ? AND status = 'confirmed'",
            [$companyId]
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countAllOpenActivities(int $companyId): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM activities WHERE company_id = ? AND status = 'pending'",
            [$companyId]
        );
        return (int) ($row->cnt ?? 0);
    }

    private function getManufacturingStats(int $companyId, string $dateFrom, string $dateTo): array {
        $today = dateNow('Y-m-d');

        $openRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM manufacturing_orders
             WHERE company_id = ? AND status IN ('confirmed','materials_allocated','in_production')",
            [$companyId]
        );
        $completedRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM manufacturing_orders
             WHERE company_id = ? AND status = 'completed' AND updated_at BETWEEN ? AND ?",
            [$companyId, localToUtc($dateFrom . ' 00:00:00'), localToUtc($dateTo . ' 23:59:59')]
        );
        $overdueRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM manufacturing_orders
             WHERE company_id = ? AND planned_date IS NOT NULL AND planned_date < ? AND status IN ('confirmed','materials_allocated','in_production')",
            [$companyId, $today]
        );

        return [
            'open_count'      => (int) ($openRow->cnt ?? 0),
            'completed_count' => (int) ($completedRow->cnt ?? 0),
            'overdue_count'   => (int) ($overdueRow->cnt ?? 0),
        ];
    }

    private function countOpenMOs(int $companyId): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM manufacturing_orders
             WHERE company_id = ? AND status IN ('confirmed','materials_allocated','in_production')",
            [$companyId]
        );
        return (int) ($row->cnt ?? 0);
    }

    private function countOverdueMOs(int $companyId, string $today): int {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM manufacturing_orders
             WHERE company_id = ? AND planned_date IS NOT NULL AND planned_date < ? AND status IN ('confirmed','materials_allocated','in_production')",
            [$companyId, $today]
        );
        return (int) ($row->cnt ?? 0);
    }

    private function getMyWork(int $companyId, int $userId, string $today): array {
        $row = $this->db->fetchOne(
            "SELECT
                SUM(CASE WHEN due_date < ? AND status IN ('pending','in_progress') THEN 1 ELSE 0 END) AS overdue,
                SUM(CASE WHEN due_date = ? AND status IN ('pending','in_progress') THEN 1 ELSE 0 END) AS due_today,
                SUM(CASE WHEN status IN ('pending','in_progress') THEN 1 ELSE 0 END) AS total_open
             FROM activities
             WHERE company_id = ? AND assigned_to = ?",
            [$today, $today, $companyId, $userId]
        );
        return [
            'overdue'    => (int) ($row->overdue ?? 0),
            'due_today'  => (int) ($row->due_today ?? 0),
            'total_open' => (int) ($row->total_open ?? 0),
        ];
    }
}
?>
