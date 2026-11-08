<?php
class Api_ExtrachargesController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function service(): Service_ExtraCharge {
        return new Service_ExtraCharge(tenantContext());
    }

    public function indexAction(TinyPHP_Request $request) {

        if ($request->isMethod('get')) {
            $appliesTo = $request->getInput('applies_to', 'String', null) ?: null;
            $all       = (bool) $request->getInput('all', 'Int', 0);
            $service   = $this->service();
            $data      = $all ? $service->listAll() : $service->list($appliesTo);
            return response($data)->sendJson();
        }

        if ($request->isMethod('post')) {
            $id      = $request->getInput('id', 'Int', 0);
            $inputs  = $request->getInputs();
            $service = $this->service();
            $result  = $id > 0 ? $service->update($id, $inputs) : $service->create($inputs);
            if ($result['success']) {
                $msg  = $id > 0 ? 'Extra charge updated.' : 'Extra charge created.';
                $code = $id > 0 ? 200 : 201;
                return response((array) $result['data'], $msg, $code)->sendJson();
            }
            $msg = $id > 0 ? 'Failed to update extra charge.' : 'Failed to create extra charge.';
            return response([], $msg, 422)->errors($result['errors'])->sendJson();
        }

        if ($request->isMethod('delete')) {
            $id     = $request->getInput('id', 'Int', 0);
            $result = $this->service()->delete($id);
            if ($result['success']) {
                return response([], 'Extra charge deleted.')->sendJson();
            }
            return response([], 'Failed to delete extra charge.', 422)->sendJson();
        }
    }
}
?>
