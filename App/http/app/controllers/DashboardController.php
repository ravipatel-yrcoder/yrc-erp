<?php
class DashboardController extends TinyPHP_Controller {

    public function indexAction(TinyPHP_Request $request) {
        $hour = (int) dateNow('H');
        if ($hour < 12) {
            $greeting = 'Good morning';
        } elseif ($hour < 17) {
            $greeting = 'Good afternoon';
        } else {
            $greeting = 'Good evening';
        }

        $currentYear = (int) date('Y');
        $companyRow  = DB('platform_db')->fetchOne(
            "SELECT YEAR(created_at) AS yr FROM companies WHERE id = ? LIMIT 1",
            [tenantContext()->companyId]
        );
        $companyCreatedYear = $companyRow ? (int) $companyRow->yr : $currentYear;

        $this->setViewVar('greeting',   $greeting);
        $this->setViewVar('monthLabel', date('F Y'));
        $this->setViewVar('chartYears', range($currentYear, $companyCreatedYear));
    }
}
?>
