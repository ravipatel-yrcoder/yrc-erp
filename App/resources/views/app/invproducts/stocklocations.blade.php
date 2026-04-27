@extends('layouts.app')
@section('title', 'Inventory - Stock - Locations')

@section('content')

@php
    $productId = request()->getInput("id");
@endphp
<!-- Content -->
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center pb-0">
            <h5 class="card-title mb-0">Stock - Locations</h5>
            <button class="btn btn-primary btn-sm" type="button" onClick="openAddEditProdStockDrawer({{$productId}});">Adjust Stock</button>
        </div>
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

@include('app.components.drawers.inventory.products.adjust-stock')

@endsection

@push('scripts')
<script>
const productStockDtOptions = {
    order: [[0, 'asc']],
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
            'data': 'on_hand_qty',
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
            'data': 'on_hand_qty',
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