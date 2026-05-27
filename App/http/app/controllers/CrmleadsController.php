<?php
class CrmLeadsController extends TinyPHP_Controller {

    public function indexAction() {        
    }

    public function pipelineAction() {        
    }

    public function editAction(TinyPHP_Request $request) {

        $id            = $request->getInput("id", "Int", 0);
        $tenantContext = tenantContext();
        $companyId     = $tenantContext->companyId;

        $lead = new Models_CrmLead($id);
        if ($lead->isEmpty || $lead->company_id != $companyId) {
            redirect("/crm/leads/");
        }

        // Apply data scope — same check the API list and pipeline use
        $scope  = (new Service_Scope($tenantContext))->getCondition('crm_leads', ['l.created_by', 'l.assigned_to']);
        $sql    = "SELECT l.id FROM crm_leads l WHERE l.id = ? AND l.company_id = ?";
        $params = [$id, $companyId];
        if ($scope['sql']) {
            $sql   .= " AND (" . $scope['sql'] . ")";
            $params = array_merge($params, $scope['bindings']);
        }
        if (!DB()->fetchOne($sql, $params)) {
            abort(403, "You do not have access to this lead.");
        }

        $this->setViewVar('lead', $lead);
    }
}
?>
