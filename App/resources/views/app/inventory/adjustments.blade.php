@extends('layouts.app')
@section('title', 'Stock - Adjustments')

@section('content')

@php
    $productId = request()->getInput("id");
@endphp
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Stock Adjustments</h4>
            <p class="text-muted mb-0 small">View and manage stock adjustments</p>
        </div>
        <div>
            <button class="btn btn-primary btn-sm" type="button" onClick="return alert('Yet to implement');">Adjust Stock</button>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table id="invAdjustments" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Product</th>
                        <th>Adj. Note</th>                        
                        <th>Adj. Qty</th>
                        <th>Adj. Type</th>
                        <th>Adjusted By</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->

@php
/*
@include('app.components.drawers.inventory.products.adjust-stock')
*/
@endphp


@endsection

@push('scripts')
<script>
const invAdjustmentsDtOptions = {
    ajax: {
        url: `/api/inv/adjustments`,
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {'data': 'created_at', 'render': function(data, type, row) {return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);}},
        {'data': 'location'},
        {'data': 'prod_name'},
        {'data': 'notes', 'render': function(data, type, row) {return `<div class="text-truncate d-none d-sm-block w-px-150">${data}</div>`;}},
        {
            'data': 'quantity', 
            'render': function(data, type, row) {
                if( !data ) return '-';
                return (data+" "+row.uom_code).trim();
            }
        },
        {'data': 'adjustment_type', 'render': function(data, type, row) {
            if( data == "increase" ) {
                return '<span class="badge badge-outline-success">Added</span>';
            } else if( data == "decrease" ) {
                return '<span class="badge badge-outline-danger">Reduced</span>';
            }

            return `<span class="badge badge-outline-secondary">${ucFirst(data)}</span>`;
        }},        
        {'data': 'created_by'},        
    ]
}
const invAdjustmentsDt = initDataTable("#invAdjustments", invAdjustmentsDtOptions);
</script>
@endpush