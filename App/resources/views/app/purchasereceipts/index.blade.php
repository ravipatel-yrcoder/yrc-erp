@extends('layouts.app')
@section('title', 'Purchase Receives')

@section('content')
<!-- Content -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Purchase Receives</h4>
            <p class="text-muted mb-0 small">Manage your purchase receives</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-end gap-3">

                <!-- Status -->
                <div class="w-px-250">
                    <label class="form-label mb-1 small fw-medium">Status</label>
                    <select id="filter_pr_status" class="form-select form-select-sm" multiple>
                        <option value="draft">Draft</option>
                        <option value="in_transit">In Transit</option>
                        <option value="received">Received</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Vendor -->
                <div class="w-px-250">
                    <label class="form-label mb-1 small fw-medium">Vendor</label>
                    <select id="filter_pr_vendor_id" class="form-select form-select-sm">
                        <option value="">All Vendors</option>
                        @foreach($vendorOptions as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Received Date -->
                <div class="d-flex align-items-center gap-2">
                    <div class="w-px-200">
                        <label class="form-label mb-1 small fw-medium">Received Date</label>
                        <select id="filter_pr_received_date_preset" class="form-select form-select-sm">
                            <option value="">Any Time</option>
                            <option value="today">Today</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="last_3_months">Last 3 Months</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div id="filter-pr-received-date-range-wrap" style="display:none;">
                        <label class="form-label mb-1 small fw-medium">&nbsp;</label>
                        <input type="text" id="filter_pr_received_date_range" class="form-control form-control-sm w-px-200" placeholder="Pick date range">
                    </div>
                </div>

                <!-- Buttons -->
                <div class="d-flex gap-2">
                    <button type="button" id="applyPrFilters" class="btn btn-sm btn-primary">Apply Filters</button>
                    <button type="button" id="resetPrFilters" class="btn btn-sm btn-outline-secondary">Reset</button>
                </div>

            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="purchase_receives_table">
                <thead>
                    <tr>
                        <th>Purchase Receive#</th>
                        <th>Date</th>
                        <th>Purchase Order#</th>
                        <th>Vendor</th>
                        <th>Status</th>
                        <!--<th>Billed</th>-->
                        <th>Items</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->

@endsection

@push('scripts')
<script>
let prFilters = {
    status:               [],
    vendor_id:            '',
    received_date_preset: '',
    received_date_from:   '',
    received_date_to:     ''
};

let purchaseReceivesDt;

const initPrFilterControls = function() {

    initSelect2('#filter_pr_status', {
        placeholder: 'All Statuses',
        multiple: true,
        minimumResultsForSearch: Infinity,
        width: 'resolve',
    });

    initSelect2('#filter_pr_vendor_id', {
        placeholder: 'All Vendors',
        width: 'resolve',
    });

    initSelect2('#filter_pr_received_date_preset', {
        placeholder: 'Any Time',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
        onChange: function(el) {
            const val = jQuery(el).val() || '';
            const rangeWrap = document.getElementById('filter-pr-received-date-range-wrap');
            if (val === 'custom') {
                rangeWrap.style.display = '';
            } else {
                rangeWrap.style.display = 'none';
                const fp = document.getElementById('filter_pr_received_date_range')._flatpickr;
                if (fp) fp.clear();
            }
        }
    });

    initDatePicker('#filter_pr_received_date_range', { mode: 'range', static: false });
};

const purchaseReceivesDtOptions = {
    order: [[1, 'desc']],
    ajax: {
        url: '/api/purchase/receipts',
        data: function(d) {
            d.filter_status               = prFilters.status;
            d.filter_vendor_id            = prFilters.vendor_id;
            d.filter_received_date_preset = prFilters.received_date_preset;
            d.filter_received_date_from   = prFilters.received_date_from;
            d.filter_received_date_to     = prFilters.received_date_to;
        },
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            'data': 'receipt_number',
            'render': function(data, type, row) {
                return `<a href="/purchase/receipts/${row.id}/">${data}</a>`;
            }
        },
        {
            'data': 'create_date',
            'render': function(data) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        {
            'data': 'po_number',
            'render': function(data, type, row) {
                return `<a href="/purchase/orders/${row.purchase_order_id}/">${data}</a>`;
            }
        },
        {'data': 'vendor'},
        {
            'data': 'status',
            'render': function(data) {
                const statusMap = {
                    draft:      ['Draft',      'warning'],
                    in_transit: ['In Transit', 'info'],
                    received:   ['Received',   'success'],
                    cancelled:  ['Cancelled',  'danger'],
                };
                const s = statusMap[data] || [data, 'secondary'];
                return `<span class="badge bg-label-${s[1]}">${s[0]}</span>`;
            }
        },
        {'data': 'items_count'},
        {
            'data': 'id',
            'orderable': false,
            'searchable': false,
            'render': function(data) {
                return (
                    `<div class="d-inline-block">
                        <a href="/purchase/receipts/${data}/" class="btn text-primary btn-icon item-edit" title="View purchase receive"><i class="icon-base bx bx-show"></i></a>
                    </div>`
                );
            }
        }
    ]
};

document.getElementById('applyPrFilters').addEventListener('click', function() {
    prFilters.status               = $('#filter_pr_status').val() || [];
    prFilters.vendor_id            = $('#filter_pr_vendor_id').val() || '';
    prFilters.received_date_preset = $('#filter_pr_received_date_preset').val() || '';

    const localDate = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;

    prFilters.received_date_from = '';
    prFilters.received_date_to   = '';
    if (prFilters.received_date_preset === 'custom') {
        const fp = document.getElementById('filter_pr_received_date_range')._flatpickr;
        if (fp && fp.selectedDates.length >= 2) {
            prFilters.received_date_from = localDate(fp.selectedDates[0]);
            prFilters.received_date_to   = localDate(fp.selectedDates[fp.selectedDates.length - 1]);
        }
    }

    purchaseReceivesDt.ajax.reload();
});

document.getElementById('resetPrFilters').addEventListener('click', function() {
    $('#filter_pr_status').val([]).trigger('change');
    $('#filter_pr_vendor_id').val('').trigger('change');
    $('#filter_pr_received_date_preset').val('').trigger('change'); // onChange → hides wrap + clears flatpickr
    prFilters = { status: [], vendor_id: '', received_date_preset: '', received_date_from: '', received_date_to: '' };
    purchaseReceivesDt.ajax.reload();
});

jQuery(document).ready(function() {
    initPrFilterControls();
    purchaseReceivesDt = initDataTable("#purchase_receives_table", purchaseReceivesDtOptions);
});
</script>
@endpush
