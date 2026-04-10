<?php
class QuotationsController extends TinyPHP_Controller {

    public function indexAction(TinyPHP_Request $request) {
        $leadId = $request->getInput("lead_id", "Int", 0);
        $this->setViewVar("leadId", $leadId);
    }
}
?>
