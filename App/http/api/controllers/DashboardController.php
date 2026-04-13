<?php
class Api_DashboardController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }


    // GET /api/dashboard/summary
    public function summaryAction(TinyPHP_Request $request) {

        if( !$request->isMethod("get") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        $companyId = auth()->getCompanyId();
        $db = DB();
        $today = date('Y-m-d');

        try {

            // --- KPIs ---

            $openLeads = $db->fetchOne(
                "SELECT COUNT(*) AS count FROM crm_leads WHERE company_id = ? AND status = 'active'",
                [$companyId]
            );

            $openPos = $db->fetchOne(
                "SELECT COUNT(*) AS count FROM purchase_orders WHERE company_id = ? AND status IN ('draft','confirmed','partially_received')",
                [$companyId]
            );

            $openSos = $db->fetchOne(
                "SELECT COUNT(*) AS count, COALESCE(SUM(total_amount), 0) AS total_amount FROM sales_orders WHERE company_id = ? AND status IN ('draft','confirmed','in_progress','partially_dispatched','dispatched','partially_delivered')",
                [$companyId]
            );

            $overdueActivities = $db->fetchOne(
                "SELECT COUNT(*) AS count FROM activities WHERE company_id = ? AND is_done = 0 AND due_date < ?",
                [$companyId, $today]
            );

            // --- CRM Pipeline stage counts (non-won, non-lost) ---

            $pipeline = $db->fetchAll(
                "SELECT s.id AS stage_id, s.name AS stage_name, s.color, COUNT(l.id) AS lead_count
                 FROM crm_stages s
                 LEFT JOIN crm_leads l ON l.stage_id = s.id AND l.status = 'active' AND l.company_id = ?
                 WHERE s.company_id = ? AND s.status = 'active' AND s.is_won = 0 AND s.is_lost = 0
                 GROUP BY s.id
                 ORDER BY s.sort_order ASC, s.id ASC",
                [$companyId, $companyId]
            );

            // --- Activities due today or overdue (limit 8) ---

            $dueActivities = $db->fetchAll(
                "SELECT a.id, a.summary, a.type, a.due_date, a.due_time, a.related_type, a.related_id,
                        l.display_name AS lead_name
                 FROM activities a
                 LEFT JOIN crm_leads l ON l.id = a.related_id AND a.related_type = 'lead'
                 WHERE a.company_id = ? AND a.is_done = 0 AND a.due_date <= ?
                 ORDER BY a.due_date ASC
                 LIMIT 8",
                [$companyId, $today]
            );

            // --- Recent Purchase Orders (limit 6) ---

            $recentPos = $db->fetchAll(
                "SELECT po.id, po.po_number, po.status, po.order_date, v.display_name AS vendor_name
                 FROM purchase_orders po
                 LEFT JOIN vendors v ON v.id = po.vendor_id
                 WHERE po.company_id = ?
                 ORDER BY po.created_at DESC
                 LIMIT 6",
                [$companyId]
            );

            // --- Recent Sales Orders (limit 6) ---

            $recentSos = $db->fetchAll(
                "SELECT so.id, so.so_number, so.status, so.order_date, so.total_amount, c.display_name AS customer_name
                 FROM sales_orders so
                 LEFT JOIN customers c ON c.id = so.customer_id
                 WHERE so.company_id = ?
                 ORDER BY so.created_at DESC
                 LIMIT 6",
                [$companyId]
            );

            // --- Out of stock products (on_hand_qty = 0, limit 8) ---

            $outOfStock = $db->fetchAll(
                "SELECT p.id, p.name, p.sku, COALESCE(SUM(s.on_hand_qty), 0) AS total_on_hand
                 FROM products p
                 LEFT JOIN inv_product_stock s ON s.product_id = p.id AND s.company_id = ?
                 WHERE p.company_id = ? AND p.status = 'active'
                 GROUP BY p.id
                 HAVING total_on_hand = 0
                 ORDER BY p.name ASC
                 LIMIT 8",
                [$companyId, $companyId]
            );

            response([
                'kpis' => [
                    'open_leads'         => (int)   $openLeads->count,
                    'open_pos'           => (int)   $openPos->count,
                    'open_sos'           => (int)   $openSos->count,
                    'open_sos_total'     => (float) $openSos->total_amount,
                    'overdue_activities' => (int)   $overdueActivities->count,
                ],
                'crm_pipeline'   => $pipeline,
                'due_activities' => $dueActivities,
                'recent_pos'     => $recentPos,
                'recent_sos'     => $recentSos,
                'out_of_stock'   => $outOfStock,
            ])->sendJson();

        } catch (Exception $e) {
            response([], "Failed to load dashboard data", 500)->sendJson();
        }
    }
}
?>
