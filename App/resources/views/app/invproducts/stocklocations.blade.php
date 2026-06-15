@extends('layouts.app')
@section('title', 'Inventory - Stock - Locations')

@section('content')

@php
    $productId = request()->getInput("id");
@endphp
<!-- Content -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Stock Locations</h4>
            <p class="text-muted mb-0 small">Stock levels by location for this product</p>
        </div>
        @if(tenantContext()->canDo('inventory_adjustments', 'write'))
        <div>
            <button class="btn btn-primary btn-sm" type="button" onClick="openAddEditProdStockDrawer({{$productId}});">Adjust Stock</button>
        </div>
        @endif
    </div>

    <div class="card">
        <div class="card-datatable text-nowrap">
            <table id="productStock" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Location</th>
                        <th>Product</th>
                        <th>Lot/Serial Number</th>
                        <th>On-Hand Qty</th>
                        <th>Reserved Qty</th>
                        <th>Available Qty</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->

@if(tenantContext()->canDo('inventory_adjustments', 'write'))
@includeOnce('app.components.drawers.inventory.products.adjust-stock')
@endif

@endsection

@push('scripts')
<script>
const productStockDtOptions = {
    order: [[2, 'asc'], [0, 'asc']],
    ajax: {
        url: `/api/inv/products/{{$productId}}/stock-locations`,
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {'data': 'location'},
        {'data': 'prod_name'},
        {'data': 'serial_number', 'render': function(data, type, row){return data || "-";}},
        {
            'data': 'unrestricted_qty',
            'render': function(data, type, row) {
                return (data+" "+row.uom_code || "").trim();
            }
        },
        {
            'data': 'reserved_qty',
            'render': function(data, type, row) {
                return (data+" "+row.uom_code || "").trim();
            }
        },
        {
            'data': 'unrestricted_qty',
            'render': function(data, type, row) {
                const avail = Math.max(0, parseFloat(data || 0) - parseFloat(row.reserved_qty || 0));
                return (avail.toFixed(2)+" "+row.uom_code || "").trim();
            }
        },
    ]
}
const prodStockDt = initDataTable("#productStock", productStockDtOptions);
</script>
@endpush