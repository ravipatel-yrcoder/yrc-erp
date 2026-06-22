<?php
class Api_ReturnReasonsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function service(): Service_Returns_Reason {
        return new Service_Returns_Reason(tenantContext());
    }

    public function indexAction(TinyPHP_Request $request) {

        if ($request->isMethod("get")) {

            $includeInactive = (bool) $request->getInput("include_inactive", "Int", 0);
            $data = $this->service()->list($includeInactive);
            return response($data)->sendJson();

        } else if ($request->isMethod("post")) {

            $id      = $request->getInput("id", "Int", 0);
            $inputs  = $request->getInputs();
            $service = $this->service();

            $result = $id > 0 ? $service->update($id, $inputs) : $service->create($inputs);

            if ($result["success"]) {
                $msg  = $id > 0 ? "Return reason updated successfully" : "Return reason created successfully";
                $code = $id > 0 ? 200 : 201;
                return response($result["data"], $msg, $code)->sendJson();
            }

            $msg = $id > 0 ? "Failed to update return reason" : "Failed to create return reason";
            return response([], $msg, 422)->errors($result["errors"])->sendJson();

        } else if ($request->isMethod("delete")) {

            $id = $request->getInput("id", "Int", 0);
            $this->service()->delete($id);
            return response([], "Return reason deleted successfully")->sendJson();
        }
    }
}
?>
