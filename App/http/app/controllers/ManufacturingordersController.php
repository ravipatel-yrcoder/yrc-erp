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
}
