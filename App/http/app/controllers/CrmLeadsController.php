<?php
class CrmLeadsController extends TinyPHP_Controller {

    public function indexAction() {
    }

    public function pipelineAction() {
        // data loaded via JS
    }

    public function editAction(TinyPHP_Request $request) {

        $id   = $request->getInput("id", "Int", 0);
        $lead = new Models_CrmLead($id);

        if( !(!$lead->isEmpty && $lead->company_id == auth()->getCompanyId()) ) {
            redirect("/crm/leads/");
        }

        $this->setViewVar('lead', $lead);
    }
}
?>
