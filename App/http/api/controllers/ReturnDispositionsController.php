<?php
class Api_ReturnDispositionsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function service(): Service_Returns_Disposition {
        return new Service_Returns_Disposition(tenantContext());
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
                $msg  = $id > 0 ? "Disposition updated successfully" : "Disposition created successfully";
                $code = $id > 0 ? 200 : 201;
                return response($result["data"], $msg, $code)->sendJson();
            }

            $msg = $id > 0 ? "Failed to update disposition" : "Failed to create disposition";
            return response([], $msg, 422)->errors($result["errors"])->sendJson();

        } else if ($request->isMethod("delete")) {

            $id     = $request->getInput("id", "Int", 0);
            $result = $this->service()->delete($id);
            return response([], "Disposition deleted successfully")->sendJson();
        }
    }
}
?>
