<?php
class Api_PurchaseinquiriesController extends TinyPHP_Controller
{
    public function init()
    {
        $this->setNoRenderer(true);
    }

    private function service(): Service_Po_Inquiry
    {
        return new Service_Po_Inquiry(tenantContext());
    }

    public function formContextAction(TinyPHP_Request $request)
    {
        $id   = $request->getInput("id", "Int", 0);
        $data = $this->service()->getFormContext($id);
        return response($data)->sendJson();
    }

    public function indexAction(TinyPHP_Request $request)
    {
        if ($request->isMethod("get")) {
            return $this->list($request);
        }

        if ($request->isMethod("post")) {
            $payload  = $request->getInputs();
            $response = $this->service()->create($payload);
            if ($response['success']) {
                return response($response['data'], "Purchase inquiry created successfully", 201)->sendJson();
            }
            return response([], "Failed to create purchase inquiry", 422)->errors($response['errors'])->sendJson();
        }
    }

    private function list(TinyPHP_Request $request): void
    {
        $companyId = tenantContext()->companyId;

        $dataFetch = new TinyPHP_DataFetch($request);

        $settingsSvc           = new Service_CompanySettings(tenantContext());
        $vendorQuoteComparison = (bool)(int) $settingsSvc->get('purchasing.vendor_quote_comparison', '0');

        $columns = [
            "id"               => "pi.id",
            "inquiry_number"   => "pi.inquiry_number",
            "title"            => "pi.title",
            "required_by_date" => "pi.required_by_date",
            "status"           => "pi.status",
            "item_count"       => "(SELECT COUNT(*) FROM purchase_inquiry_items pii WHERE pii.inquiry_id = pi.id)",
            "vendor_count"     => "(SELECT COUNT(*) FROM purchase_inquiry_vendors piv WHERE piv.inquiry_id = pi.id)",
            "first_name"       => "u.first_name",
            "last_name"        => "u.last_name",
            "created_at"       => "pi.created_at",
        ];

        if ($vendorQuoteComparison) {
            $columns["responded_count"] = "(SELECT COUNT(*) FROM purchase_inquiry_vendors piv2 WHERE piv2.inquiry_id = pi.id AND piv2.status = 'responded')";
        }

        $ignoreSearch = ['id', 'item_count', 'vendor_count'];
        if ($vendorQuoteComparison) {
            $ignoreSearch[] = 'responded_count';
        }

        $dataFetch
            ->table("purchase_inquiries AS pi")
            ->joins("LEFT JOIN users u ON u.id = pi.created_by")
            ->columns($columns)
            ->ignoreSearch($ignoreSearch)
            ->where("pi.company_id = ?", [$companyId]);

        $scope = (new Service_Scope(tenantContext()))->getCondition('purchase_inquiries', ['pi.created_by']);
        if ($scope['sql']) {
            $dataFetch->where($scope['sql'], $scope['bindings']);
        }

        $filterStatus = $request->getInput("filter_status", "array", []);
        if (!empty($filterStatus)) {
            $validStatuses = ['draft', 'sent', 'partially_responded', 'fully_responded', 'awarded', 'cancelled'];
            $filterStatus  = array_values(array_filter($filterStatus, fn($s) => in_array($s, $validStatuses, true)));
            if (!empty($filterStatus)) {
                $placeholders = implode(',', array_fill(0, count($filterStatus), '?'));
                $dataFetch->where("pi.status IN ({$placeholders})", $filterStatus);
            }
        }

        $filterVendorId = $request->getInput("filter_vendor_id", "Int", 0);
        if ($filterVendorId > 0) {
            $dataFetch->where("EXISTS (SELECT 1 FROM purchase_inquiry_vendors piv3 WHERE piv3.inquiry_id = pi.id AND piv3.vendor_id = ?)", [$filterVendorId]);
        }

        $filterRequiredByPreset = $request->getInput("filter_required_by_preset", "String", "");
        $filterRequiredByFrom   = $request->getInput("filter_required_by_from",   "String", "");
        $filterRequiredByTo     = $request->getInput("filter_required_by_to",     "String", "");
        $today = dateNow('Y-m-d');

        if ($filterRequiredByPreset) {
            switch ($filterRequiredByPreset) {
                case 'overdue':
                    $dataFetch->where("pi.required_by_date < ? AND pi.required_by_date IS NOT NULL AND pi.status NOT IN ('awarded','cancelled')", [$today]);
                    break;
                case 'due_today':
                    $dataFetch->where("pi.required_by_date = ? AND pi.required_by_date IS NOT NULL", [$today]);
                    break;
                case 'due_this_week':
                    $dataFetch->where("pi.required_by_date BETWEEN ? AND ? AND pi.required_by_date IS NOT NULL", [$today, dateNow('Y-m-d', 'sunday this week')]);
                    break;
                case 'due_this_month':
                    $dataFetch->where("pi.required_by_date BETWEEN ? AND ? AND pi.required_by_date IS NOT NULL", [$today, dateNow('Y-m-t')]);
                    break;
                case 'custom':
                    if ($filterRequiredByFrom && $filterRequiredByTo) {
                        $dataFetch->where("pi.required_by_date BETWEEN ? AND ? AND pi.required_by_date IS NOT NULL", [$filterRequiredByFrom, $filterRequiredByTo]);
                    } elseif ($filterRequiredByFrom) {
                        $dataFetch->where("pi.required_by_date >= ? AND pi.required_by_date IS NOT NULL", [$filterRequiredByFrom]);
                    } elseif ($filterRequiredByTo) {
                        $dataFetch->where("pi.required_by_date <= ? AND pi.required_by_date IS NOT NULL", [$filterRequiredByTo]);
                    }
                    break;
            }
        }

        $filterDatePreset = $request->getInput("filter_date_preset", "String", "");
        $filterDateFrom   = $request->getInput("filter_date_from",   "String", "");
        $filterDateTo     = $request->getInput("filter_date_to",     "String", "");

        if ($filterDatePreset) {
            switch ($filterDatePreset) {
                case 'today':
                    $dataFetch->where("DATE(pi.created_at) = ?", [$today]);
                    break;
                case 'this_week':
                    $dataFetch->where("DATE(pi.created_at) BETWEEN ? AND ?", [dateNow('Y-m-d', 'monday this week'), $today]);
                    break;
                case 'this_month':
                    $dataFetch->where("DATE(pi.created_at) BETWEEN ? AND ?", [dateNow('Y-m-01'), $today]);
                    break;
                case 'last_month':
                    $dataFetch->where("DATE(pi.created_at) BETWEEN ? AND ?", [
                        dateNow('Y-m-01', 'first day of last month'),
                        dateNow('Y-m-t',  'last day of last month'),
                    ]);
                    break;
                case 'last_3_months':
                    $dataFetch->where("DATE(pi.created_at) BETWEEN ? AND ?", [dateNow('Y-m-d', '-3 months'), $today]);
                    break;
                case 'custom':
                    if ($filterDateFrom && $filterDateTo) {
                        $dataFetch->where("DATE(pi.created_at) BETWEEN ? AND ?", [$filterDateFrom, $filterDateTo]);
                    } elseif ($filterDateFrom) {
                        $dataFetch->where("DATE(pi.created_at) >= ?", [$filterDateFrom]);
                    } elseif ($filterDateTo) {
                        $dataFetch->where("DATE(pi.created_at) <= ?", [$filterDateTo]);
                    }
                    break;
            }
        }

        $results = $dataFetch->fetch();
        response($results)->sendJson();
    }

    public function entityAction(TinyPHP_Request $request)
    {
        $id = $request->getInput("id", "Int", 0);

        if ($request->isMethod("get")) {
            $data = $this->service()->getDetails($id);
            return response($data)->sendJson();
        }

        if ($request->isMethod("post")) {
            $payload  = $request->getInputs();
            $response = $this->service()->update($id, $payload);
            if ($response['success']) {
                return response([], "Purchase inquiry updated successfully")->sendJson();
            }
            return response([], "Failed to update purchase inquiry", 422)->errors($response['errors'])->sendJson();
        }
    }

    public function cancelAction(TinyPHP_Request $request)
    {
        $id = $request->getInput("id", "Int", 0);
        $this->service()->cancel($id);
        return response([], "Purchase inquiry cancelled")->sendJson();
    }

    public function sendToVendorAction(TinyPHP_Request $request)
    {
        $id       = $request->getInput("id",        "Int",    0);
        $vendorId = $request->getInput("vendor_id", "Int",    0);
        $to       = $request->getInput("to",        "String", "");
        $subject  = $request->getInput("subject",   "String", "");
        $body     = $request->getInput("body",      "String", "");
        $cc       = $request->getInput("cc",        "String", "");
        $bcc      = $request->getInput("bcc",       "String", "");

        $this->service()->sendToVendor($id, $vendorId, $to, $subject, $body, $cc, $bcc);
        return response([], "Email sent successfully")->sendJson();
    }

    public function emailDefaultsAction(TinyPHP_Request $request)
    {
        $id       = $request->getInput("id",        "Int", 0);
        $vendorId = $request->getInput("vendor_id", "Int", 0);
        $data     = $this->service()->getEmailDefaults($id, $vendorId);
        return response($data)->sendJson();
    }

    public function vendorPricesAction(TinyPHP_Request $request)
    {
        $id      = $request->getInput("id",  "Int", 0);
        $vid     = $request->getInput("vid", "Int", 0);
        $payload = $request->getInputs();
        $header  = $payload['header'] ?? [];
        $items   = $payload['items']  ?? [];
        $this->service()->saveVendorPrices($id, $vid, $header, $items);
        return response([], "Vendor prices saved successfully")->sendJson();
    }

    public function vendorRespondAction(TinyPHP_Request $request)
    {
        $id  = $request->getInput("id",  "Int", 0);
        $vid = $request->getInput("vid", "Int", 0);
        $this->service()->markVendorResponded($id, $vid);
        return response([], "Vendor marked as responded")->sendJson();
    }

    public function vendorWithdrawAction(TinyPHP_Request $request)
    {
        $id  = $request->getInput("id",  "Int", 0);
        $vid = $request->getInput("vid", "Int", 0);
        $this->service()->withdrawVendorQuote($id, $vid);
        return response([], "Vendor quote withdrawn")->sendJson();
    }

    public function comparisonAction(TinyPHP_Request $request)
    {
        $id   = $request->getInput("id", "Int", 0);
        $data = $this->service()->getComparison($id);
        return response($data)->sendJson();
    }

    public function awardAction(TinyPHP_Request $request)
    {
        $id              = $request->getInput("id",                "Int", 0);
        $inquiryVendorId = $request->getInput("inquiry_vendor_id", "Int", 0);
        $poId            = $this->service()->award($id, $inquiryVendorId);
        return response(['po_id' => $poId], "Inquiry awarded — purchase order created")->sendJson();
    }

    public function historyAction(TinyPHP_Request $request)
    {
        $id   = $request->getInput("id", "Int", 0);
        $data = $this->service()->getHistory($id);
        return response($data)->sendJson();
    }
}
?>
