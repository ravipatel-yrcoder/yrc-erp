@extends('layouts.app')
@section('title', 'Settings - Inventory')

@section('content')
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <div class="card-title mb-0"><h5 class="m-0">Stock Serial/Sequance</h5></div>
        </div>
        <div class="card-body">
            <div class="row g-6">
                <div class="col-12 col-md-4">
                    <label class="form-label mb-1">Global sequance pattern</label>
                    <input type="text" class="form-control" name="global_sequance_pattern" placeholder="{PREFIX}-{YYYY}-{MM}">
                    <p class="mb-0 pt-2"></p>
                </div>                
            </div>
        </div>
    </div>
</div>
<!-- / Content -->
@endsection

@push('scripts')
<script>
</script>
@endpush