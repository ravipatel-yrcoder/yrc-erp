<?php
class Api_DashboardController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function serviceDashboard(): Service_Dashboard {
        return new Service_Dashboard(tenantContext());
    }

    // GET /api/dashboard/summary?date_from=Y-m-d&date_to=Y-m-d
    public function summaryAction(TinyPHP_Request $request) {
        $year     = date('Y');
        $dateFrom = $request->getInput('date_from', 'String', "{$year}-01-01");
        $dateTo   = $request->getInput('date_to',   'String', "{$year}-12-31");
        $data = $this->serviceDashboard()->getSummary($dateFrom, $dateTo);
        return response($data)->sendJson();
    }

    // GET /api/dashboard/sales-by-month?year=2025
    public function salesByMonthAction(TinyPHP_Request $request) {
        $year = $request->getInput('year', 'Int', (int) date('Y'));
        $data = $this->serviceDashboard()->getSalesByMonth(tenantContext()->companyId, $year);
        return response(['months' => $data])->sendJson();
    }

    // GET /api/dashboard/top-customers
    public function topCustomersAction(TinyPHP_Request $request) {
        $data = $this->serviceDashboard()->getTopCustomersByRevenue(tenantContext()->companyId);
        return response(['customers' => $data])->sendJson();
    }

    // GET /api/dashboard/leads-by-month?year=2025
    public function leadsByMonthAction(TinyPHP_Request $request) {
        $year = $request->getInput('year', 'Int', (int) date('Y'));
        $data = $this->serviceDashboard()->getLeadsByMonth(tenantContext()->companyId, $year);
        return response($data)->sendJson();
    }

    // GET /api/dashboard/operator-summary?period=today|week|month
    public function operatorSummaryAction(TinyPHP_Request $request) {
        $period = $request->getInput('period', 'String', 'month');
        if (!in_array($period, ['today', 'week', 'month'], true)) {
            $period = 'month';
        }
        $data = $this->serviceDashboard()->getOperatorSummary($period);
        return response($data)->sendJson();
    }
}
?>
