<?php
class SubscriptionexpiredController extends TinyPHP_Controller {

    public function indexAction() {

        // Load subscription so the view can show a status-specific message.
        // User is authenticated but subscription is blocked — do not use
        // tenantContext() here since it was never hydrated for this route.
        $companyId = auth()->getCompanyId();
        $subService = new Service_Subscription();
        $subscription = $subService->getCurrent($companyId);

        $this->setViewVar('subscription', $subscription);
        $this->setTitle('Subscription Inactive');
    }
}
?>
