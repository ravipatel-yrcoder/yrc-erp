<?php
class Api_ProformainvoicesController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function service(): Service_So_ProformaInvoice {
        return new Service_So_ProformaInvoice(tenantContext());
    }


    public function formContextAction(TinyPHP_Request $request) {
        $soId = $request->getInput("so_id", "Int", 0);
        $data = $this->service()->getFormContext($soId);
        return response($data)->sendJson();
    }


    public function createAction(TinyPHP_Request $request) {
        $payload = $request->getInputs();
        $soId    = (int) ($payload['sales_order_id'] ?? 0);

        $result = $this->service()->create($soId, $payload);

        if ($result['success']) {
            return response($result['data'], "Proforma invoice created successfully", 201)->sendJson();
        }
        return response([], "Failed to create proforma invoice", 422)->errors($result['errors'])->sendJson();
    }


    public function listForSoAction(TinyPHP_Request $request) {
        $soId = $request->getInput("so_id", "Int", 0);
        $data = $this->service()->listForSO($soId);
        return response($data)->sendJson();
    }


    public function entityAction(TinyPHP_Request $request) {
        $id = $request->getInput("id", "Int", 0);
        $data = $this->service()->get($id);
        return response($data)->sendJson();
    }


    public function cancelAction(TinyPHP_Request $request) {
        $id   = $request->getInput("id", "Int", 0);
        $note = $request->getInput("note", "String", "");

        $this->service()->cancel($id, $note);
        return response([], "Proforma invoice cancelled")->sendJson();
    }


    public function sendEmailAction(TinyPHP_Request $request) {
        $id      = $request->getInput("id", "Int", 0);
        $payload = $request->getInputs();

        $result = $this->service()->sendEmail($id, $payload);

        if ($result['success']) {
            return response([], "Proforma invoice sent successfully")->sendJson();
        }
        return response([], "Failed to send email", 422)->errors($result['errors'])->sendJson();
    }


    public function downloadPdfAction(TinyPHP_Request $request) {
        $id   = $request->getInput("id", "Int", 0);
        $mode = $request->getInput("mode", "String", "inline");

        try {
            $pdf = $this->service()->downloadPdf($id);
        } catch (Service_Exception $e) {
            http_response_code($e->getHttpStatusCode());
            exit($e->getMessage());
        }

        Helpers_Pdf::stream($pdf['bytes'], $pdf['filename'], $mode);
    }
}
?>
