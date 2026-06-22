@extends('layouts.app')
@section('title', 'Returns')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Returns</h4>
            <p class="text-muted mb-0 small">Manage customer returns</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-end gap-3">

                <div class="w-px-200">
                    <label class="form-label mb-1 small fw-medium">Status</label>
                    <select id="filter_return_status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="in_transit">In Transit</option>
                        <option value="received">Received</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <div class="w-px-180">
                        <label class="form-label mb-1 small fw-medium">Return Date</label>
                        <select id="filter_return_date_preset" class="form-select form-select-sm">
                            <option value="">Any Time</option>
                            <option value="today">Today</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div id="filter-return-date-range-wrap" style="display:none;">
                        <label class="form-label mb-1 small fw-medium">&nbsp;</label>
                        <input type="text" id="filter_return_date_range" class="form-control form-control-sm w-px-200" placeholder="Pick date range">
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" id="applyReturnFilters" class="btn btn-sm btn-primary">Apply Filters</button>
                    <button type="button" id="resetReturnFilters" class="btn btn-sm btn-outline-secondary">Reset</button>
                </div>

            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="returns_table">
                <thead>
                    <tr>
                        <th>Return #</th>
                        <th>Party</th>
                        <th>Reference</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const returnStatusMap = {
    draft:      ['Draft',      'secondary'],
    in_transit: ['In Transit', 'warning'],
    received:   ['Received',   'success'],
    cancelled:  ['Cancelled',  'dark'],
};

let returnFilters = {
    status:      '',
    date_preset: '',
    date_from:   '',
    date_to:     '',
};

let returnsDt;

const initReturnFilterControls = function() {

    initSelect2('#filter_return_status', {
        placeholder: 'All Statuses',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
    });

    initSelect2('#filter_return_date_preset', {
        placeholder: 'Any Time',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
        onChange: function(el) {
            const val = jQuery(el).val() || '';
            const rangeWrap = document.getElementById('filter-return-date-range-wrap');
            if (val === 'custom') {
                rangeWrap.style.display = '';
            } else {
                rangeWrap.style.display = 'none';
                const fp = document.getElementById('filter_return_date_range')._flatpickr;
                if (fp) fp.clear();
            }
        }
    });

    initDatePicker('#filter_return_date_range', { mode: 'range', static: false });
};

const returnsDtOptions = {
    order: [[0, 'desc']],
    ajax: {
        url: '/api/sales/returns',
        data: function(d) {
            d.status      = returnFilters.status;
            d.return_type = 'customer';
            d.date_from   = returnFilters.date_from;
            d.date_to     = returnFilters.date_to;
        },
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            data: 'return_number',
            render: function(data, type, row) {
                return `<a href="/sales/returns/${row.id}/">${data}</a>`;
            }
        },
        { data: 'party_name', defaultContent: '-' },
        {
            data: 'reference_type',
            render: function(data, type, row) {
                return data ? `${data} #${row.reference_id}` : '-';
            }
        },
        {
            data: 'return_date',
            render: function(data) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        {
            data: 'status',
            render: function(data) {
                const s = returnStatusMap[data] || [data, 'secondary'];
                return `<span class="badge badge-sm bg-label-${s[1]}">${s[0]}</span>`;
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
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                const editBtn = (['draft','in_transit'].includes(row.status) && canDo('returns', 'write'))
                    ? `<a href="javascript:void(0);" onclick="openReturnFormDrawer(${data})" class="btn text-warning btn-icon" title="Edit"><i class="icon-base bx bxs-edit"></i></a>`
                    : '';
                return (
                    '<div class="d-inline-block">' +
                        editBtn +
                        '<a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a>' +
                        '<ul class="dropdown-menu dropdown-menu-end">' +
                            `<li><a href="/sales/returns/${data}/" class="dropdown-item">View Details</a></li>` +
                        '</ul>' +
                    '</div>'
                );
            }
        }
    ]
};

document.getElementById('applyReturnFilters').addEventListener('click', function() {
    returnFilters.status      = $('#filter_return_status').val() || '';
    returnFilters.date_preset = $('#filter_return_date_preset').val() || '';

    returnFilters.date_from = '';
    returnFilters.date_to   = '';
    if (returnFilters.date_preset === 'custom') {
        const fp = document.getElementById('filter_return_date_range')._flatpickr;
        if (fp && fp.selectedDates.length >= 2) {
            const localDate = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
            returnFilters.date_from = localDate(fp.selectedDates[0]);
            returnFilters.date_to   = localDate(fp.selectedDates[fp.selectedDates.length - 1]);
        }
    }

    returnsDt.ajax.reload();
});

document.getElementById('resetReturnFilters').addEventListener('click', function() {
    $('#filter_return_status').val('').trigger('change');
    $('#filter_return_date_preset').val('').trigger('change');
    returnFilters = { status: '', date_preset: '', date_from: '', date_to: '' };
    returnsDt.ajax.reload();
});

jQuery(document).ready(function() {
    initReturnFilterControls();
    returnsDt = initDataTable('#returns_table', returnsDtOptions);
});
</script>
@endpush
