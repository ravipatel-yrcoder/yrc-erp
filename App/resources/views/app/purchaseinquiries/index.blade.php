@extends('layouts.app')
@section('title', 'Purchase Inquiries')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Purchase Inquiries</h4>
            <p class="text-muted mb-0 small">Manage purchase inquiries</p>
        </div>
        @if(tenantContext()->canDo('purchase_inquiries', 'write'))
        <div>
            <button onclick="openPurchaseInquiryFormDrawer()" class="btn btn-primary btn-sm"><i class="icon-base bx bx-plus icon-sm"></i> New Inquiry</button>
        </div>
        @endif
    </div>

    <!-- Filter Bar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-end gap-3">

                <div class="w-px-250">
                    <label class="form-label mb-1 small fw-medium">Status</label>
                    <select id="filter_pi_status" class="form-select form-select-sm" multiple>
                        <option value="draft">Draft</option>
                        <option value="sent">Sent</option>
                        <option value="partially_responded">Partially Responded</option>
                        <option value="fully_responded">Fully Responded</option>
                        <option value="awarded">Awarded</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="w-px-250">
                    <label class="form-label mb-1 small fw-medium">Vendor</label>
                    <select id="filter_pi_vendor_id" class="form-select form-select-sm">
                        <option value="">All Vendors</option>
                        @foreach($vendorOptions as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <div class="w-px-200">
                        <label class="form-label mb-1 small fw-medium">Required By</label>
                        <select id="filter_pi_required_by_preset" class="form-select form-select-sm">
                            <option value="">Any</option>
                            <option value="overdue">Overdue</option>
                            <option value="due_today">Due Today</option>
                            <option value="due_this_week">Due This Week</option>
                            <option value="due_this_month">Due This Month</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div id="filter-pi-required-by-range-wrap" style="display:none;">
                        <label class="form-label mb-1 small fw-medium">&nbsp;</label>
                        <input type="text" id="filter_pi_required_by_range" class="form-control form-control-sm w-px-200" placeholder="Pick date range">
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <div class="w-px-200">
                        <label class="form-label mb-1 small fw-medium">Inquiry Date</label>
                        <select id="filter_pi_date_preset" class="form-select form-select-sm">
                            <option value="">Any Time</option>
                            <option value="today">Today</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="last_3_months">Last 3 Months</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div id="filter-pi-date-range-wrap" style="display:none;">
                        <label class="form-label mb-1 small fw-medium">&nbsp;</label>
                        <input type="text" id="filter_pi_date_range" class="form-control form-control-sm w-px-200" placeholder="Pick date range">
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" id="applyPiFilters" class="btn btn-sm btn-primary">Apply Filters</button>
                    <button type="button" id="resetPiFilters" class="btn btn-sm btn-outline-secondary">Reset</button>
                </div>
            </div>
        </div>
    </div>


    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="purchase_inquiries_table">
                <thead>
                    <tr>
                        <th width="150px">Inquiry #</th>
                        <th>Title</th>
                        <th>Required By</th>
                        <th class="text-center">Items</th>
                        <th class="text-center">Vendors</th>
                        @if($vendor_quote_comparison)
                        <th>Responded</th>
                        @endif
                        <th>Status</th>
                        <th width="150px">Created By</th>
                        <th width="135px">Created At</th>
                        <th width="125px" class="text-center">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@includeOnce('app.components.drawers.purchase-inquiries.add-edit')
@endsection

@push('scripts')
<script>
let piFilters = {
    status:              [],
    vendor_id:           '',
    required_by_preset:  '',
    required_by_from:    '',
    required_by_to:      '',
    date_preset:         '',
    date_from:           '',
    date_to:             '',
};

const piStatusMap = {
    draft:               ['Draft',               'warning'],
    sent:                ['Sent',                'info'],
    partially_responded: ['Partially Responded', 'primary'],
    fully_responded:     ['Fully Responded',     'success'],
    awarded:             ['Awarded',             'success'],
    cancelled:           ['Cancelled',           'danger'],
};

const initPiFilterControls = function() {
    initSelect2('#filter_pi_status', {
        placeholder: 'All',
        multiple: true,
        minimumResultsForSearch: Infinity,
        width: 'resolve',
    });

    initSelect2('#filter_pi_vendor_id', {
        placeholder: 'All',
        width: 'resolve',
    });

    initSelect2('#filter_pi_required_by_preset', {
        placeholder: 'Any',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
        onChange: function(el) {
            const val      = jQuery(el).val() || '';
            const rangeWrap = document.getElementById('filter-pi-required-by-range-wrap');
            if (val === 'custom') {
                rangeWrap.style.display = '';
            } else {
                rangeWrap.style.display = 'none';
                const fp = document.getElementById('filter_pi_required_by_range')._flatpickr;
                if (fp) fp.clear();
            }
        }
    });

    initDatePicker('#filter_pi_required_by_range', { mode: 'range', static: false });

    initSelect2('#filter_pi_date_preset', {
        placeholder: 'Any Time',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
        onChange: function(el) {
            const val      = jQuery(el).val() || '';
            const rangeWrap = document.getElementById('filter-pi-date-range-wrap');
            if (val === 'custom') {
                rangeWrap.style.display = '';
            } else {
                rangeWrap.style.display = 'none';
                const fp = document.getElementById('filter_pi_date_range')._flatpickr;
                if (fp) fp.clear();
            }
        }
    });

    initDatePicker('#filter_pi_date_range', { mode: 'range', static: false });

    document.getElementById('applyPiFilters').addEventListener('click', function() {
        piFilters.status              = jQuery('#filter_pi_status').val() || [];
        piFilters.vendor_id           = jQuery('#filter_pi_vendor_id').val() || '';
        piFilters.required_by_preset  = jQuery('#filter_pi_required_by_preset').val() || '';
        piFilters.date_preset         = jQuery('#filter_pi_date_preset').val() || '';

        const localDate = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;

        piFilters.required_by_from = '';
        piFilters.required_by_to   = '';
        if (piFilters.required_by_preset === 'custom') {
            const fp = document.getElementById('filter_pi_required_by_range')._flatpickr;
            if (fp && fp.selectedDates.length >= 2) {
                piFilters.required_by_from = localDate(fp.selectedDates[0]);
                piFilters.required_by_to   = localDate(fp.selectedDates[fp.selectedDates.length - 1]);
            }
        }

        piFilters.date_from = '';
        piFilters.date_to   = '';
        if (piFilters.date_preset === 'custom') {
            const fp = document.getElementById('filter_pi_date_range')._flatpickr;
            if (fp && fp.selectedDates.length >= 2) {
                piFilters.date_from = localDate(fp.selectedDates[0]);
                piFilters.date_to   = localDate(fp.selectedDates[fp.selectedDates.length - 1]);
            }
        }

        piDt.ajax.reload();
    });

    document.getElementById('resetPiFilters').addEventListener('click', function() {
        jQuery('#filter_pi_status').val(null).trigger('change');
        jQuery('#filter_pi_vendor_id').val('').trigger('change');
        jQuery('#filter_pi_required_by_preset').val('').trigger('change'); // onChange → hides wrap + clears flatpickr
        jQuery('#filter_pi_date_preset').val('').trigger('change');        // onChange → hides wrap + clears flatpickr
        piFilters = { status: [], vendor_id: '', required_by_preset: '', required_by_from: '', required_by_to: '', date_preset: '', date_from: '', date_to: '' };
        piDt.ajax.reload();
    });
};

let piDt;

const piDtOptions = {
    order: [[ {{ $vendor_quote_comparison ? 8 : 7 }}, 'desc']],
    ajax: {
        url: '/api/purchase/inquiries',
        data: function(d) {
            d.filter_status              = piFilters.status;
            d.filter_vendor_id           = piFilters.vendor_id;
            d.filter_required_by_preset  = piFilters.required_by_preset;
            d.filter_required_by_from    = piFilters.required_by_from;
            d.filter_required_by_to      = piFilters.required_by_to;
            d.filter_date_preset         = piFilters.date_preset;
            d.filter_date_from           = piFilters.date_from;
            d.filter_date_to             = piFilters.date_to;
        },
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            data: 'inquiry_number',
            render: function(data, type, row) {
                return `<a href="/purchase/inquiries/${row.id}/">${data}</a>`;
            }
        },
        {
            data: 'title',
            render: function(data) {
                return data || '<span class="text-muted">-</span>';
            }
        },
        {
            data: 'required_by_date',
            render: function(data) {
                return data ? formatMySqlDate(data, window.sysDefaultConfig.dateFormat) : '<span class="text-muted">-</span>';
            }
        },
        { data: 'item_count', orderable: false, className: 'text-center' },
        { data: 'vendor_count', orderable: false, className: 'text-center' },
        @if($vendor_quote_comparison)
        {
            data: 'responded_count',
            orderable: false,
            render: function(data, type, row) {
                const total     = parseInt(row.vendor_count) || 0;
                const responded = parseInt(data) || 0;
                const color     = responded === total && total > 0 ? 'success' : responded > 0 ? 'warning' : 'secondary';
                return `<span class="badge bg-label-${color}">${responded}/${total}</span>`;
            }
        },
        @endif
        {
            data: 'status',
            render: function(data) {
                const s = piStatusMap[data] || [data, 'secondary'];
                return `<span class="badge badge-sm bg-label-${s[1]}">${s[0]}</span>`;
            }
        },
        {
            data: 'first_name',
            render: function(data, type, row) {
                return `${row.first_name || ''} ${row.last_name || ''}`.trim() || '-';
            }
        },
        {
            data: 'created_at',
            render: function(data) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        {
            data: 'id',
            class: 'text-center',
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                let html = '';
                if (row.status === 'draft' && canDo('purchase_inquiries', 'write')) {
                    html += `<a href="javascript:void(0);" onclick="openPurchaseInquiryFormDrawer(${row.id})" class="btn text-warning btn-icon item-edit" title="Edit"><i class="icon-base bx bxs-edit"></i></a>`;
                }
                html += `<a href="/purchase/inquiries/${row.id}/" class="btn text-primary btn-icon" title="View"><i class="icon-base bx bx-show"></i></a>`;
                return html;
            }
        }
    ]
};

jQuery(document).ready(function() {
    initPiFilterControls();
    piDt = initDataTable('#purchase_inquiries_table', piDtOptions);
});
</script>
@endpush
