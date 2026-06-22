<?php
class SalesreturnsController extends TinyPHP_Controller {

    public function indexAction() {
        // index view loaded automatically
    }

    public function editAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $tenantContext = tenantContext();
        $companyId = $tenantContext->companyId;

        $return = new Models_Return($id);
        if ($return->isEmpty || $return->company_id != $companyId) {
            abort(403);
        }

        $this->setViewVar('return', $return);
        $this->setViewVar('tenantContext', $tenantContext);
    }
}
?>
