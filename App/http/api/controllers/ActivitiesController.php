<?php
class Api_ActivitiesController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function activityService(): Service_Activity {
        return new Service_Activity(tenantContext());
    }

    // GET /api/activities          → global page list (activities.read)
    // POST /api/activities         → create (permission via entity type)
    public function indexAction(TinyPHP_Request $request) {

        if ($request->isMethod("get")) {
            return $this->handlePageList($request);
        }
        if ($request->isMethod("post")) {
            return $this->handleSave($request);
        }
    }


    // GET /api/activities/form-context?id=
    public function formContextAction(TinyPHP_Request $request) {

        $activityId = $request->getInput("id", "Int", 0);

        $service = $this->activityService();
        $data    = $service->getFormContext($activityId);

        return response($data)->sendJson();
    }


    // GET /api/activities/page-form-context
    public function pageFormContextAction(TinyPHP_Request $request) {

        $service = $this->activityService();
        $data    = $service->getPageFormContext();

        return response($data)->sendJson();
    }


    // GET /api/activities/:entity_type/:entity_id
    public function entityActivitiesAction(TinyPHP_Request $request) {

        $params     = $request->getParams();
        $entityType = $params['entity_type'] ?? '';
        $entityId   = (int) ($params['entity_id'] ?? 0);

        $service = $this->activityService();
        $data    = $service->list($entityType, $entityId);

        return response($data)->sendJson();
    }


    // POST /api/activities/:id/status
    public function statusAction(TinyPHP_Request $request) {

        $id     = $request->getInput("id", "Int", 0);
        $inputs = $request->getInputs();

        $service  = $this->activityService();
        $response = $service->updateStatus($id, $inputs);

        if ($response["success"]) {
            return response([], "Activity status updated", 200)->sendJson();
        }

        return response([], "Failed to update activity status", 422)->errors($response["errors"])->sendJson();
    }


    // POST/DELETE /api/activities/:id
    public function entityAction(TinyPHP_Request $request) {

        if ($request->isMethod("post")) {
            return $this->handleSave($request);
        }
        if ($request->isMethod("delete")) {
            return $this->handleDelete($request);
        }
    }


    private function handlePageList(TinyPHP_Request $request) {

        $inputs = $request->getInputs();

        $filters = [
            'activity_type'  => $inputs['activity_type']  ?? '',
            'status'         => $inputs['status']         ?? '',
            'priority'       => $inputs['priority']       ?? '',
            'entity_type'    => $inputs['entity_type']    ?? '',
            'assigned_to'    => $inputs['assigned_to']    ?? '',
            'due_date_preset'=> $inputs['due_date_preset']?? '',
            'due_date_from'  => $inputs['due_date_from']  ?? '',
            'due_date_to'    => $inputs['due_date_to']    ?? '',
        ];

        $dtParams = [
            'start'  => $inputs['start']  ?? 0,
            'length' => $inputs['length'] ?? 25,
            'search' => ['value' => $inputs['search'] ?? ''],
        ];

        $service = $this->activityService();
        $data    = $service->listForPage($filters, $dtParams);
        $data['draw'] = (int) ($inputs['draw'] ?? 1);

        return response($data)->sendJson();
    }


    private function handleSave(TinyPHP_Request $request) {

        $id     = $request->getInput("id", "Int", 0);
        $inputs = $request->getInputs();

        $service = $this->activityService();

        if ($id) {
            $response = $service->update($id, $inputs);
        } else {
            $response = $service->create($inputs);
        }

        if ($response["success"]) {
            $msg  = $id ? "Activity updated successfully" : "Activity created successfully";
            $code = $id ? 200 : 201;
            return response($response["data"], $msg, $code)->sendJson();
        }

        $msg = $id ? "Failed to update activity" : "Failed to create activity";
        return response([], $msg, 422)->errors($response["errors"])->sendJson();
    }


    private function handleDelete(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);

        $service  = $this->activityService();
        $response = $service->delete($id);

        if ($response["success"]) {
            return response([], "Activity deleted", 200)->sendJson();
        }

        return response([], "Failed to delete activity", 200)->sendJson();
    }
}
?>
