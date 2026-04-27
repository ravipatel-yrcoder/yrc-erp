<?php
class Api_SubscriptionsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }


    private function serviceSubscription() : Service_Subscription {
        return new Service_Subscription();
    }


    // GET /api/subscription/summary
    public function summaryAction(TinyPHP_Request $request)
    {
        $companyId = tenantContext()->companyId;
        $service = $this->serviceSubscription();
        $data = $service->getSummary($companyId);

        return response($data)->sendJson();
    }


    // POST /api/subscription/module
    public function moduleAction(TinyPHP_Request $request)
    {
        $moduleKey = trim($request->getInput('module_key', 'string', ''));
        if (!$moduleKey) {
            return response([], 'Module key is required', 422)->sendJson();
        }

        $tenantContext = tenantContext();
        
        $service = $this->serviceSubscription();
        $service->changeModule($tenantContext->companyId, $tenantContext->userId, $moduleKey);

        return response([], 'Module switched successfully.')->sendJson();
    }


    // POST /api/subscription/upgrade
    public function upgradeAction(TinyPHP_Request $request)
    {
        $tenantContext = tenantContext();        
        $service = $this->serviceSubscription();

        $service->upgradePlan($tenantContext->companyId, $tenantContext->userId);

        return response([], 'Plan upgraded successfully.')->sendJson();
    }


    // POST /api/subscription/downgrade
    public function downgradeAction(TinyPHP_Request $request)
    {
        $moduleKey = trim($request->getInput('module_key', 'string', ''));
        if (!$moduleKey) {
            return response([], 'Please select a module to keep.', 422)->sendJson();
        }

        $tenantContext = tenantContext();        
        $service = $this->serviceSubscription();
        
        $service->downgradePlan($tenantContext->companyId, $tenantContext->userId, $moduleKey);

        return response([], 'Plan downgraded successfully.')->sendJson();
    }


    // POST /api/subscription/cancel
    public function cancelAction(TinyPHP_Request $request)
    {
        $tenantContext = tenantContext();        
        $service = $this->serviceSubscription();

        $service->cancelSubscription($tenantContext->companyId, $tenantContext->userId);

        return response([], 'Subscription cancelled.')->sendJson();
    }
}
?>
