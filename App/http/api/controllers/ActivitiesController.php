<?php
class Api_ActivitiesController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function activityService(): Service_Activity {
        return new Service_Activity(tenantContext());
    }

    // GET /api/activities?related_type=lead&related_id=123
    // POST /api/activities (create)
    public function indexAction(TinyPHP_Request $request) {

        if( $request->isMethod("get") ) {
            return $this->handleList($request);
        }
        else if( $request->isMethod("post") ) {
            return $this->handleSave($request);
        }        
    }


    // GET /api/activities/form-context?id=&related_type=lead&related_id=123
    public function formContextAction(TinyPHP_Request $request) {

        $activityId = $request->getInput("id", "Int", 0);
            
        $service = $this->activityService();
        $data = $service->getFormContext($activityId);

        return response($data)->sendJson();
    }


    // GET/POST/DELETE /api/activities/:id
    public function entityAction(TinyPHP_Request $request) {

        if( $request->isMethod("post") ) {
            return $this->handleSave($request);
        }
        else if( $request->isMethod("delete") ) {
            return $this->handleDelete($request);
        }        
    }


    // POST /api/activities/:id/done
    public function doneAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $inputs = $request->getInputs();

        $service = $this->activityService();
        $response = $service->markDone($id, $inputs);

        if( $response["success"] ) {
            return response($response["data"], "Activity marked as done", 200)->sendJson();
        }

        return response([], "Failed to mark activity as done", 422)->sendJson();
    }


    private function handleList(TinyPHP_Request $request) {

        $relatedType = $request->getInput("related_type", "String", "");
        $relatedId = $request->getInput("related_id", "Int", 0);
        
        $service = $this->activityService();
        $data = $service->list($relatedType, $relatedId);

        return response($data)->sendJson();
    }


    private function handleSave(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $inputs = $request->getInputs();

        $service = $this->activityService();

        if( $id ) {
            $response = $service->update($id, $inputs);
        } else {
            $response = $service->create($inputs);
        }


        if( $response["success"] ) {
            
            $msg = $id ? "Activity updated successfully" : "Activity created successfully";
            $code = $id ? 200 : 201;
            
            return response($response["data"], $msg, $code)->sendJson();
        } else {
            
            $msg = $id ? "Failed to update activity" : "Failed to create activity";
            return response([], $msg, 422)->errors($response["errors"])->sendJson();
        }
    }


    private function handleDelete(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
            
        $service = $this->activityService();
        $response = $service->delete($id);
        
        if( $response["success"] ) {
            return response([], "Activity deleted", 200)->sendJson();
        }
        
        return response([], "Failed to delete activity", 200)->sendJson();
        
    }
}
?>