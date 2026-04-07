<?php
class Api_ActivitiesController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }


    // GET /api/activities?related_type=lead&related_id=123
    // POST /api/activities (create)
    public function indexAction(TinyPHP_Request $request) {

        if( $request->isMethod("get") ) {
            $this->handleList($request);
        }
        else if( $request->isMethod("post") ) {
            $this->handleSave($request);
        }

        response([], "Method not allowed", 405)->sendJson();
    }


    // GET /api/activities/form-context?id=&related_type=lead&related_id=123
    public function formContextAction(TinyPHP_Request $request) {

        if( !$request->isMethod("get") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        try {

            $activityId = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;

            $service = new Service_Activity(new Service_TenantContext($companyId, $userId));
            $data = $service->getFormContext($activityId);

            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->sendJson();
        } catch (Exception $e) {
            response([], "Failed to load form context", 500)->sendJson();
        }
    }


    // GET/POST/DELETE /api/activities/:id
    public function entityAction(TinyPHP_Request $request) {

        if( $request->isMethod("post") ) {
            $this->handleSave($request);
        }
        else if( $request->isMethod("delete") ) {
            $this->handleDelete($request);
        }

        response([], "Method not allowed", 405)->sendJson();
    }


    // POST /api/activities/:id/done
    public function doneAction(TinyPHP_Request $request) {

        if( !$request->isMethod("post") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        try {

            $id = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;
            $inputs = $request->getInputs();

            $service = new Service_Activity(new Service_TenantContext($companyId, $userId));
            $response = $service->markDone($id, $inputs);

            if( $response["success"] ) {
                response($response["data"], "Activity marked as done", 200)->sendJson();
            }

            response([], "Failed to mark activity as done", 422)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->sendJson();
        } catch (Exception $e) {
            response([], "Failed to mark activity as done", 500)->sendJson();
        }
    }


    private function handleList(TinyPHP_Request $request) {

        try {

            $relatedType = $request->getInput("related_type", "String", "");
            $relatedId = $request->getInput("related_id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;

            $service = new Service_Activity(new Service_TenantContext($companyId, $userId));
            $data = $service->list($relatedType, $relatedId);

            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->sendJson();
        } catch (Exception $e) {
            response([], "Failed to load activities", 500)->sendJson();
        }
    }


    private function handleSave(TinyPHP_Request $request) {

        try {

            $id = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;
            $inputs = $request->getInputs();

            $service = new Service_Activity(new Service_TenantContext($companyId, $userId));

            if( $id ) {
                $response = $service->update($id, $inputs);
                $message = "Activity updated successfully";
                $code = 200;
            } else {
                $response = $service->create($inputs);
                $message = "Activity created successfully";
                $code = 201;
            }

            if( $response["success"] ) {
                response($response["data"], $message, $code)->sendJson();
            }

            response([], $id ? "Failed to update activity" : "Failed to create activity", 422)
                ->errors($response["errors"])
                ->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->sendJson();
        } catch (Exception $e) {
            response([], "Failed to save activity", 500)->sendJson();
        }
    }


    private function handleDelete(TinyPHP_Request $request) {

        try {

            $id = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;

            $service = new Service_Activity(new Service_TenantContext($companyId, $userId));
            $service->delete($id);

            response([], "Activity deleted", 200)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode() ?: 500)->sendJson();
        } catch (Exception $e) {
            response([], "Failed to delete activity", 500)->sendJson();
        }
    }
}
?>