<?php
class SubscriptionsController extends TinyPHP_Controller {

    public function indexAction() {
        
        $companyId = tenantContext()->companyId;
        $subscription = new Service_Subscription();

        $subPlan = $subscription->getCurrent($companyId);
        $this->setViewVar("subPlan", $subPlan);
    }
}
?>
