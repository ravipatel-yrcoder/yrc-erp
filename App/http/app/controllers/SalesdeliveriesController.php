<?php
class SalesDeliveriesController extends TinyPHP_Controller {

    public function indexAction() {
    }

    public function editAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $dn = new Models_SalesDelivery($id);

        if (!(!$dn->isEmpty && $dn->company_id == auth()->getCompanyId())) {
            redirect("/sales-deliveries/");
        }

        $this->setViewVar('delivery', $dn);
    }
}
?>
