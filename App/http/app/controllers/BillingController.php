<?php
class BillingController extends TinyPHP_Controller {

    public function indexAction()
    {
        $companyId = tenantContext()->companyId;
        $subService = new Service_Subscription();
        $summary = $subService->getSummary($companyId);

        $this->setViewVar('summary', $summary);
        $this->setTitle('Billing');
    }
}
?>
