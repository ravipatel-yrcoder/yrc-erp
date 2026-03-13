<?php
class SalesOrdersController extends TinyPHP_Controller {

    public function indexAction() {
    }

    public function editAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $salesOrder = new Models_SalesOrder($id);

        if( !(!$salesOrder->isEmpty && $salesOrder->company_id == auth()->getCompanyId()) ) {
            redirect("/sales-orders/");
        }

        $this->setViewVar('salesOrder', $salesOrder);
    }
}
?>
