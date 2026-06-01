<?php
class Api_ManufacturingBomsController extends TinyPHP_Controller
{
    public function init() {
        $this->setNoRenderer(true);
    }

    private function service(): Service_Manufacturing_Bom {
        return new Service_Manufacturing_Bom(tenantContext());
    }

    public function indexAction(TinyPHP_Request $request) {
        if ($request->isMethod("get"))  return $this->list($request);
        if ($request->isMethod("post")) return $this->save($request);
    }

    public function formContextAction(TinyPHP_Request $request) {
        $id   = $request->getInput("id", "Int", 0);
        $data = $this->service()->getFormContext($id);
        return response($data)->sendJson();
    }

    public function entityAction(TinyPHP_Request $request) {
        if ($request->isMethod("get"))    return $this->show($request);
        if ($request->isMethod("post"))   return $this->save($request);
        if ($request->isMethod("delete")) return $this->destroy($request);
    }

    private function list(TinyPHP_Request $request) {

        if (!tenantContext()->canDo('manufacturing_boms', 'read')) {
            return response([], "You do not have permission to view BOMs", 403)->sendJson();
        }

        $companyId = tenantContext()->companyId;
        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = [
            "id" => "b.id",
            "product_name" => "p.name",
            "name" => "b.name",
            "output_qty" => "b.output_qty",
            "is_default" => "b.is_default",
            "status" => "b.status",
            "component_count" => "COUNT(bi.id)",
            "created_by_name" => "u.name",
            "created_at" => "b.created_at",
        ];

        $results = $dataFetch
            ->table("manufacturing_boms AS b")
            ->joins("LEFT JOIN products AS p ON p.id = b.product_id
                LEFT JOIN manufacturing_bom_items AS bi ON bi.bom_id = b.id
                LEFT JOIN users AS u ON u.id = b.created_by")
            ->columns($columns)
            ->where("b.company_id = ?", [$companyId])
            ->groupBy("b.id")
            ->fetch();

        response($results)->sendJson();
    }

    private function save(TinyPHP_Request $request) {
        
        $id = (int) ($request->getParams()['id'] ?? $request->getInput("id", "Int", 0));
        $inputs = $request->getInputs();

        $service = $this->service();
        if ($id) {
            $result = $service->update($id, $inputs);
            $okMsg  = "BOM updated successfully";
            $failMsg = "Failed to update BOM";
            $okCode  = 200;
        } else {
            $result  = $service->create($inputs);
            $okMsg   = "BOM created successfully";
            $failMsg = "Failed to create BOM";
            $okCode  = 201;
        }

        if ($result["success"]) {
            return response($result["data"], $okMsg, $okCode)->sendJson();
        }

        return response([], $failMsg, 422)->errors($result["errors"])->sendJson();
    }

    private function show(TinyPHP_Request $request) {
        
        $id = (int) ($request->getParams()['id'] ?? $request->getInput("id", "Int", 0));
        $data = $this->service()->getDetails($id);
        return response($data)->sendJson();
    }

    private function destroy(TinyPHP_Request $request) {
        
        $id = (int) ($request->getParams()['id'] ?? $request->getInput("id", "Int", 0));
        $this->service()->delete($id);
        return response([], "BOM deleted successfully", 200)->sendJson();
    }
}
