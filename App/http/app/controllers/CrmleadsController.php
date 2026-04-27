<?php
class CrmLeadsController extends TinyPHP_Controller {

    public function indexAction() {        
    }

    public function pipelineAction() {        
    }

    public function editAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);
        $lead = new Models_CrmLead($id);

        $tenantContext = tenantContext();
        $companyId = $tenantContext->companyId;

        if( !(!$lead->isEmpty && $lead->company_id == $companyId) ) {
            redirect("/crm/leads/");
        }

        $this->setViewVar('lead', $lead);
    }
}
?>
