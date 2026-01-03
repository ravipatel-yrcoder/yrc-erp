@extends('layouts.app')
@section('title', 'Inventory - Stock - Locations')

@section('content')

@php
    $productId = request()->getInput("id");
@endphp
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
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
                        <th>Stock</th>
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
        {'data': 'available_qty'},
        {'data': 'reserved_qty'},
        {'data': 'available_qty'},
    ]
}
const prodStockDt = initDataTable("#productStock", productStockDtOptions);
</script>
@endpush