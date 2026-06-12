<?php
class Api_InventoryController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function serviceInvMovement(): Service_Inv_Movement {
        return new Service_Inv_Movement(tenantContext());
    }

    public function itemsAction(TinyPHP_Request $request) {
        $companyId        = tenantContext()->companyId;
        $filterLocationId = $request->getInput('location_id', 'Int', 0);

        $stockJoin = $filterLocationId
            ? "LEFT JOIN inv_product_stock AS ips ON ips.product_id = p.id AND ips.company_id = {$companyId} AND ips.location_id = {$filterLocationId}"
            : "LEFT JOIN inv_product_stock AS ips ON ips.product_id = p.id AND ips.company_id = {$companyId}";

        $columns = [
            'id'            => 'p.id',
            'name'          => 'p.name',
            'uom_code'      => 'uom.code',
            'on_hand_qty'   => 'COALESCE(SUM(ips.on_hand_qty), 0)',
            'reserved_qty'  => 'COALESCE(SUM(ips.reserved_qty), 0)',
            'available_qty' => 'COALESCE(SUM(ips.on_hand_qty) - SUM(ips.reserved_qty), 0)',
        ];

        $df = (new TinyPHP_DataFetch($request))
            ->table('products AS p')
            ->joins(
                "INNER JOIN product_masters AS pm ON pm.id = p.master_id
                 LEFT JOIN uoms AS uom ON uom.id = p.base_uom_id
                 {$stockJoin}"
            )
            ->columns($columns)
            ->where('pm.company_id = ? AND pm.status <> ?', [$companyId, 'archived'])
            ->where("p.stock_tracking_method IN ('quantity', 'lot', 'serial')")
            ->groupBy('p.id')
            ->ignoreSearch(['on_hand_qty', 'reserved_qty', 'available_qty']);

        return response($df->fetch())->sendJson();
    }

    public function movementsAction(TinyPHP_Request $request) {
        $results = $this->serviceInvMovement()->list($request);
        return response($results)->sendJson();
    }

    public function movementsFormContextAction(TinyPHP_Request $request) {
        $data = $this->serviceInvMovement()->getFormContext();
        return response($data)->sendJson();
    }

    public function reservationsAction(TinyPHP_Request $request) {
        $companyId  = tenantContext()->companyId;
        $productId  = $request->getInput('product_id',  'Int', 0);
        $locationId = $request->getInput('location_id', 'Int', 0);

        if (!$productId) {
            return response(['success' => false, 'message' => 'product_id is required'])->sendJson();
        }

        $db     = db();
        $params = [$companyId, $productId];
        $locationWhere = '';
        if ($locationId) {
            $locationWhere = 'AND r.location_id = ?';
            $params[]      = $locationId;
        }

        $rows = $db->fetchAll(
            "SELECT
                 r.document_type,
                 r.document_id,
                 r.document_number,
                 r.document_line_id,
                 r.location_id,
                 r.reserved_qty,
                 l.name AS location_name,
                 CASE r.document_type
                     WHEN 'sales_order' THEN c.display_name
                     ELSE NULL
                 END AS customer_name
             FROM inv_stock_reservations AS r
             LEFT JOIN company_locations AS l  ON l.id = r.location_id
             LEFT JOIN sales_orders      AS so ON r.document_type = 'sales_order' AND so.id = r.document_id
             LEFT JOIN customers         AS c  ON c.id = so.customer_id
             WHERE r.company_id = ? AND r.product_id = ? $locationWhere
             ORDER BY l.name, r.document_type, r.document_id, r.document_line_id",
            $params
        );

        $reservations = [];
        $totalReserved = 0.0;

        foreach ($rows as $row) {
            $docType = $row->document_type;
            $link    = match($docType) {
                'sales_order'         => '/sales/orders/'         . $row->document_id,
                'manufacturing_order' => '/manufacturing/orders/' . $row->document_id,
                default               => '#',
            };

            $reservations[] = [
                'document_type'    => $docType,
                'document_id'      => (int)   $row->document_id,
                'document_number'  => $row->document_number,
                'document_line_id' => (int)   $row->document_line_id,
                'location_id'      => (int)   $row->location_id,
                'location_name'    => $row->location_name,
                'reserved_qty'     => (float) $row->reserved_qty,
                'customer_name'    => $row->customer_name,
                'link'             => $link,
            ];

            $totalReserved += (float) $row->reserved_qty;
        }

        return response([
            'total_reserved' => $totalReserved,
            'reservations'   => $reservations,
        ])->sendJson();
    }

    public function adjustmentsImportTemplateAction(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;
        $db        = db();

        $locations = $db->fetchAll(
            "SELECT id, name FROM company_locations WHERE company_id = ? AND status = 'active' ORDER BY name ASC",
            [$companyId]
        );
        $defaultLocation = count($locations) === 1 ? $locations[0]->name : '';

        $products = $db->fetchAll(
            "SELECT p.id, p.name, p.sku, p.stock_tracking_method
             FROM products p
             INNER JOIN product_masters pm ON pm.id = p.master_id
             WHERE pm.company_id = ? AND p.stock_tracking_method IN ('quantity','lot','serial') AND pm.status <> 'archived'
             ORDER BY p.name ASC",
            [$companyId]
        );

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="stock-adjustments-template.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Product ID', 'Product', 'SKU', 'Tracking Method', 'Location', 'Adjust Qty (+/-)', 'Serial/Lot Number', 'Note']);
        foreach ($products as $p) {
            fputcsv($out, [$p->id, $p->name, $p->sku, $p->stock_tracking_method, $defaultLocation, '', '', '']);
        }
        fclose($out);
        exit;
    }


    public function adjustmentsImportAction(TinyPHP_Request $request) {

        if (!tenantContext()->canDo('inventory_adjustments', 'write')) {
            return response([], "You do not have permission to import stock adjustments", 403)->sendJson();
        }

        $file = $_FILES['file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return response([], "No file uploaded or upload error occurred", 422)->sendJson();
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            return response([], "Only CSV files are supported", 422)->sendJson();
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            return response([], "File size must not exceed 2MB", 422)->sendJson();
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            return response([], "Failed to read uploaded file", 422)->sendJson();
        }

        $expectedHeaders = ['product id', 'product', 'sku', 'tracking method', 'location', 'adjust qty (+/-)', 'serial/lot number', 'note'];

        $headerRow = fgetcsv($handle);
        if (!$headerRow) {
            fclose($handle);
            return response([], "CSV file is empty", 422)->sendJson();
        }

        $normalizedHeaders = array_map(fn($h) => strtolower(trim($h)), $headerRow);
        if ($normalizedHeaders !== $expectedHeaders) {
            fclose($handle);
            return response([], "CSV columns do not match the expected template. Please use the provided import template.", 422)->sendJson();
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $row = array_pad($row, 8, '');
            if (count(array_filter($row, fn($v) => trim($v) !== '')) === 0) {
                continue;
            }
            if (trim($row[7]) === '') {
                $row[7] = 'Stock Adjustment Import';
            }
            $rows[] = $row;
        }
        fclose($handle);

        if (empty($rows)) {
            return response([], "CSV file contains no data rows", 422)->sendJson();
        }

        if (count($rows) > 2000) {
            return response([], "Import file cannot exceed 2000 rows", 422)->sendJson();
        }

        $service = $this->serviceInvMovement();
        try {
            $result = $service->importAdjustments($rows);
        } catch (Exception $e) {
            return response([], $e->getMessage(), 422)->sendJson();
        }

        if ($result['success']) {
            return response($result['data'], "Stock adjustments imported successfully", 201)->sendJson();
        }

        return response([], "Import validation failed", 422)->errors($result['errors'])->sendJson();
    }


    public function adjustmentsAction(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;

        $columns = [
            "id"              => "adj.id",
            "location"        => 'CASE WHEN l.code IS NOT NULL AND l.code <> "" THEN CONCAT(l.name, " (", l.code, ")") ELSE l.name END',
            "prod_name"       => "p.name",
            "quantity"        => "adj.quantity",
            "adjustment_type" => "adj.adjustment_type",
            "notes"           => "adj.notes",
            "uom_code"        => "uom.code",
            "created_at"      => "adj.created_at",
            "created_by"      => "u.name",
        ];

        $df = (new TinyPHP_DataFetch($request))
            ->table("inv_adjustments AS adj")
            ->joins(
                "LEFT JOIN products AS p ON p.id = adj.product_id
                 LEFT JOIN uoms AS uom ON uom.id = p.base_uom_id
                 LEFT JOIN company_locations AS l ON l.id = adj.location_id
                 LEFT JOIN users AS u ON u.id = adj.created_by"
            )
            ->columns($columns)
            ->where("adj.company_id = ?", [$companyId]);

        $filterProductId = $request->getInput('product_id', 'Int', 0);
        if ($filterProductId) {
            $df->where('adj.product_id = ?', [$filterProductId]);
        }

        $filterLocationId = $request->getInput('location_id', 'Int', 0);
        if ($filterLocationId) {
            $df->where('adj.location_id = ?', [$filterLocationId]);
        }

        $filterAdjType = $request->getInput('adjustment_type', 'String', '');
        if ($filterAdjType) {
            $df->where('adj.adjustment_type = ?', [$filterAdjType]);
        }

        $filterPerformedBy = $request->getInput('performed_by', 'Int', 0);
        if ($filterPerformedBy) {
            $df->where('adj.created_by = ?', [$filterPerformedBy]);
        }

        $filterDateFrom = $request->getInput('date_from', 'String', '');
        if ($filterDateFrom && strtotime($filterDateFrom)) {
            $df->where('adj.created_at >= ?', [localToUtc($filterDateFrom . ' 00:00:00')]);
        }

        $filterDateTo = $request->getInput('date_to', 'String', '');
        if ($filterDateTo && strtotime($filterDateTo)) {
            $df->where('adj.created_at <= ?', [localToUtc($filterDateTo . ' 23:59:59')]);
        }

        return response($df->fetch())->sendJson();
    }
}
