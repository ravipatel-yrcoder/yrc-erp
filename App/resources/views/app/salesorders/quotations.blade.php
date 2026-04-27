@extends('layouts.app')
@section('title', 'Quotations')

@section('content')
<!-- Content -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Quotations</h4>
            <p class="text-muted mb-0 small">Manage your quotations</p>
        </div>
        <div>
            <button class="btn btn-primary btn-sm" type="button" id="createQuotation"><i class="icon-base bx bx-plus icon-sm"></i> Add New</button>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="quotations_table">
                <thead>
                    <tr>
                        <th>SO#</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Reference</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->

@include('app.components.drawers.sales-orders.add-edit')

@endsection

@push('scripts')
<script>
// Pass through lead_id filter if present in URL
const leadId = '{{ $leadId }}';
const quotationsDtOptions = {
    order: [[5, 'desc']],
    ajax: {
        url: '/api/sales/quotations' + (leadId && leadId != '0' ? `?lead_id=${leadId}` : ''),
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            'data': 'so_number',
            'render': function(data, type, row) {
                return `<a href="/sales/orders/${row.id}/" class="text-primary fw-medium">${data}</a>`;
            }
        },
        {
            'data': 'order_date',
            'render': function(data, type, row) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        {'data': 'customer', 'defaultContent': '-'},
        {'data': 'reference', 'defaultContent': '-'},
        {
            'data': 'total_amount',
            'render': function(data) { return formatCurrency(data); }
        },
        {
            'data': 'id',
            'orderable': false,
            'searchable': false,
            'render': function(data) {
                return (
                    `<div class="d-inline-block">
                        <a href="/sales/orders/${data}/" class="btn text-primary btn-icon" title="View quotation"><i class="icon-base bx bx-show"></i></a>
                    </div>`
                );
            }
        }
    ]
};
const quotationsDt = initDataTable("#quotations_table", quotationsDtOptions);

document.getElementById('createQuotation').addEventListener('click', function() {
    openSalesOrderFormDrawer(0, {mode: 'lead_quotation', leadId: 0})
});

document.addEventListener('leadQuotationCreated', function(e) {

    quotationsDt.ajax.render
});
</script>
@endpush
