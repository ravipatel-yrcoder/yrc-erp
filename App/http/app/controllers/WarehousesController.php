<?php
class WarehousesController extends TinyPHP_Controller {

    public function indexAction() {
        if (!Service_CompanySettings::isMultiWarehouseEnabled(tenantContext()->companyId)) {
            abort(404);            
        }
    }
}
