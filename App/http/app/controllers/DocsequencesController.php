<?php
class DocsequencesController extends TinyPHP_Controller {

    public function indexAction() {
        $service   = new Service_Sequence(tenantContext());
        $sequences = $service->getAllForSettings();
        $this->setViewVar('sequences', $sequences);
    }
}
?>
