<?php
class ActivitiesController extends TinyPHP_Controller {

    public function indexAction(TinyPHP_Request $request) {

        $service = new Service_Activity(tenantContext());
        $ctx     = $service->getPageFormContext();

        $this->setViewVar('scope',             $ctx['scope']);
        $this->setViewVar('showAssignedTo',    $ctx['scope'] !== 'own');
        $this->setViewVar('assignedToOptions', $ctx['users'] ?? []);
    }
}
?>
