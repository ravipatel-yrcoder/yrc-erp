<?php
class ManufacturingordersController extends TinyPHP_Controller
{
    public function indexAction() {

        $tenantContext = tenantContext();
        $db = Service_TenantDBResolver::resolve($tenantContext->companyId);

        $productOptions = $db->fetchAll(
            "SELECT id, name, sku FROM products WHERE company_id = ? AND status = 'active' ORDER BY name ASC",
            [$tenantContext->companyId]
        );

        $this->setViewVar('productOptions', $productOptions);
    }

    public function editAction(TinyPHP_Request $request) {

        $id            = $request->getInput("id", "Int", 0);
        $tenantContext = tenantContext();
        $companyId     = $tenantContext->companyId;

        $mo = new Models_ManufacturingOrder($id);
        if ($mo->isEmpty || $mo->company_id != $companyId) {
            abort(403);
        }

        $this->setViewVar('moId', $id);
    }

    public function materialRequirementSheetAction(TinyPHP_Request $request) {
        $this->setNoRenderer(true);
        $id = $request->getInput("id", "Int", 0);
        try {
            $data = (new Service_Manufacturing_Order(tenantContext()))->getMrsData($id);
        } catch (Service_Exception $e) {
            http_response_code($e->getHttpStatusCode());
            exit($e->getMessage());
        }
        $pdf = Helpers_Pdf::render('pdf.manufacturing-mrs', ['printData' => $data], ['no_footer' => true]);
        Helpers_Pdf::stream($pdf, 'MRS-' . $data['mo']['mo_number'] . '.pdf', 'download');
    }

    public function issueSlipAction(TinyPHP_Request $request) {
        $this->setNoRenderer(true);
        $id      = $request->getInput("id", "Int", 0);
        $allocId = $request->getInput("allocId", "Int", 0);
        try {
            $data = (new Service_Manufacturing_Order(tenantContext()))->getIssueSlipData($id, $allocId);
        } catch (Service_Exception $e) {
            http_response_code($e->getHttpStatusCode());
            exit($e->getMessage());
        }
        $pdf = Helpers_Pdf::render('pdf.manufacturing-issue-slip', ['printData' => $data], ['no_footer' => true]);
        Helpers_Pdf::stream($pdf, 'MIS-' . $data['mo']['mo_number'] . '-' . $allocId . '.pdf', 'download');
    }
}
