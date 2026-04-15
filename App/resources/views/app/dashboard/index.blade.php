@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<!-- Content -->
<div class="flex-grow-1 container-p-y container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-6">
        <h4 class="mb-0">Dashboard</h4>
    </div>

    {{-- Row 1: KPI Cards --}}
    <div class="row g-6 mb-6">

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 text-muted small">Open Leads</p>
                            <h3 class="mb-0" id="kpiOpenLeads">—</h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded-2 bg-label-primary">
                                <i class="bx bx-user-check fs-4"></i>
                            </span>
                        </div>
                    </div>
                    <a href="/crm/leads" class="small mt-3 d-block text-muted">View all <i class="bx bx-right-arrow-alt"></i></a>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 text-muted small">Open Purchase Orders</p>
                            <h3 class="mb-0" id="kpiOpenPos">—</h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded-2 bg-label-info">
                                <i class="bx bx-package fs-4"></i>
                            </span>
                        </div>
                    </div>
                    <a href="/purchase/orders" class="small mt-3 d-block text-muted">View all <i class="bx bx-right-arrow-alt"></i></a>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 text-muted small">Open Sales Orders</p>
                            <h3 class="mb-0" id="kpiOpenSos">—</h3>
                            <p class="mb-0 small text-muted" id="kpiOpenSosTotal"></p>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded-2 bg-label-success">
                                <i class="bx bx-cart fs-4"></i>
                            </span>
                        </div>
                    </div>
                    <a href="/sales/orders" class="small mt-3 d-block text-muted">View all <i class="bx bx-right-arrow-alt"></i></a>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 text-muted small">Overdue Activities</p>
                            <h3 class="mb-0" id="kpiOverdueActivities">—</h3>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded-2 bg-label-warning">
                                <i class="bx bx-alarm-exclamation fs-4"></i>
                            </span>
                        </div>
                    </div>
                    <a href="/crm/leads" class="small mt-3 d-block text-muted">View leads <i class="bx bx-right-arrow-alt"></i></a>
                </div>
            </div>
        </div>

    </div>
    {{-- / Row 1 --}}

    {{-- Row 2: CRM Pipeline + Due Activities --}}
    <div class="row g-6 mb-6">

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0">CRM Pipeline</h5>
                    <a href="/crm/pipeline" class="btn btn-sm btn-outline-primary">View Pipeline</a>
                </div>
                <div class="card-body" id="crmPipelineWidget">
                    <div class="text-center text-muted py-4">Loading…</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0">Overdue / Due Today</h5>
                </div>
                <div class="card-body p-0" id="dueActivitiesWidget">
                    <div class="text-center text-muted py-4">Loading…</div>
                </div>
            </div>
        </div>

    </div>
    {{-- / Row 2 --}}

    {{-- Row 3: Recent POs + Recent SOs --}}
    <div class="row g-6 mb-6">

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0">Recent Purchase Orders</h5>
                    <a href="/purchase/orders" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive" id="recentPosWidget">
                    <div class="text-center text-muted py-4">Loading…</div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0">Recent Sales Orders</h5>
                    <a href="/sales/orders" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive" id="recentSosWidget">
                    <div class="text-center text-muted py-4">Loading…</div>
                </div>
            </div>
        </div>

    </div>
    {{-- / Row 3 --}}

    {{-- Row 4: Out of Stock --}}
    <div class="row g-6">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0">Out of Stock Products</h5>
                    <a href="/products" class="btn btn-sm btn-outline-primary">View Products</a>
                </div>
                <div class="table-responsive" id="outOfStockWidget">
                    <div class="text-center text-muted py-4">Loading…</div>
                </div>
            </div>
        </div>
    </div>
    {{-- / Row 4 --}}

</div>
<!-- / Content -->
@endsection

@push('scripts')
<script>

const poStatusMap = {
    draft: ['Draft', 'secondary'],
    confirmed: ['Confirmed', 'primary'],
    partially_received: ['Partially Received',  'warning'],
    received: ['Received', 'success'],
    cancelled: ['Cancelled', 'danger'],
    closed: ['Closed', 'dark'],
};

const soStatusMap = {
    draft: ['Draft', 'secondary'],
    confirmed: ['Confirmed', 'primary'],
    in_progress: ['In Progress', 'info'],
    partially_dispatched: ['Partially Dispatched', 'warning'],
    dispatched: ['Dispatched', 'primary'],
    partially_delivered: ['Partially Delivered',  'warning'],
    delivered: ['Delivered', 'success'],
    cancelled: ['Cancelled', 'danger'],
};

const activityTypeMap = {
    call: { label: 'Call', icon: 'bx-phone', color: 'primary'  },
    email: { label: 'Email', icon: 'bx-envelope', color: 'info'     },
    meeting: { label: 'Meeting', icon: 'bx-calendar', color: 'warning'  },
    task: { label: 'Task', icon: 'bx-task', color: 'success'  },
    deadline: { label: 'Deadline', icon: 'bx-alarm', color: 'danger'   },
    other: { label: 'Other', icon: 'bx-circle', color: 'secondary'},
};

const statusBadge = function(map, status) {
    const s = map[status] || [status, 'secondary'];
    return `<span class="badge bg-label-${s[1]}">${s[0]}</span>`;
};

const renderKpis = function(kpis) {
    
    document.getElementById('kpiOpenLeads').innerHTML = kpis.open_leads;
    document.getElementById('kpiOpenPos').innerHTML = kpis.open_pos;
    document.getElementById('kpiOpenSos').innerHTML = kpis.open_sos;
    document.getElementById('kpiOverdueActivities').innerHTML = kpis.overdue_activities;

    if (kpis.open_sos_total > 0) {
        document.getElementById('kpiOpenSosTotal').innerHTML = formatCurrency(kpis.open_sos_total);
    }

    // Highlight overdue count in danger if > 0
    if (kpis.overdue_activities > 0) {
        document.getElementById('kpiOverdueActivities').classList.add('text-danger');
    }
};

const renderCrmPipeline = function(pipeline) {
    const el = document.getElementById('crmPipelineWidget');
    if (!pipeline || pipeline.length === 0) {
        el.innerHTML = `<div class="text-center text-muted py-4">No pipeline stages configured.</div>`;
        return;
    }

    const total = pipeline.reduce((sum, s) => sum + parseInt(s.lead_count || 0), 0);

    let html = '';
    pipeline.forEach(stage => {
        const count = parseInt(stage.lead_count || 0);
        const color = stage.color || '#6c757d';
        const pct = total > 0 ? Math.round((count / total) * 100) : 0;

        html += `
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-medium small">${stage.stage_name}</span>
                    <span class="small text-muted">${count} lead${count !== 1 ? 's' : ''}</span>
                </div>
                <div class="progress" style="height: 10px; border-radius: 6px;">
                    <div class="progress-bar" role="progressbar"
                        style="width: ${pct}%; background-color: ${color}; border-radius: 6px;"
                        aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
            </div>`;
    });

    html += `<div class="small text-muted mt-3 text-end">${total} total active lead${total !== 1 ? 's' : ''}</div>`;

    el.innerHTML = html;
};

const renderDueActivities = function(activities) {
    
    const el = document.getElementById('dueActivitiesWidget');
    if (!activities || activities.length === 0) {
        el.innerHTML = `<div class="text-center text-muted py-4 px-3">No overdue or due-today activities.</div>`;
        return;
    }

    const today = new Date().toISOString().slice(0, 10);
    let html = `<ul class="list-group list-group-flush">`;

    activities.forEach(a => {
        const t          = activityTypeMap[a.type] || activityTypeMap.other;
        const isOverdue  = a.due_date < today;
        const dateClass  = isOverdue ? 'text-danger fw-medium' : 'text-warning fw-medium';
        const dateLabel  = isOverdue ? `Overdue: ${a.due_date}` : `Due today`;

        html += `
            <li class="list-group-item px-4 py-3">
                <div class="d-flex align-items-start gap-3">
                    <span class="avatar avatar-xs rounded-circle bg-label-${t.color} flex-shrink-0 mt-1"
                        style="width:30px;height:30px;display:flex;align-items:center;justify-content:center;"
                        title="${t.label}">
                        <i class="bx ${t.icon}" style="font-size:0.9rem;"></i>
                    </span>
                    <div class="flex-grow-1 min-width-0">
                        <div class="fw-medium small text-truncate">${a.summary}</div>
                        ${a.lead_name ? `<div class="small text-muted text-truncate">${a.lead_name}</div>` : ''}
                        <div class="small ${dateClass} mt-1">${dateLabel}</div>
                    </div>
                </div>
            </li>`;
    });

    html += `</ul>`;
    el.innerHTML = html;
};

const renderRecentPos = function(pos) {
    const el = document.getElementById('recentPosWidget');
    if (!pos || pos.length === 0) {
        el.innerHTML = `<div class="text-center text-muted py-4">No purchase orders yet.</div>`;
        return;
    }

    let rows = '';
    pos.forEach(po => {
        rows += `
            <tr>
                <td><a href="/purchase/orders/${po.id}" class="fw-medium">${po.po_number}</a></td>
                <td class="text-muted small">${po.vendor_name || '—'}</td>
                <td>${statusBadge(poStatusMap, po.status)}</td>
                <td class="small text-muted">${po.order_date ? formatMySqlDate(po.order_date) : '—'}</td>
            </tr>`;
    });

    el.innerHTML = `
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>PO #</th>
                    <th>Vendor</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>`;
};

const renderRecentSos = function(sos) {
    const el = document.getElementById('recentSosWidget');
    if (!sos || sos.length === 0) {
        el.innerHTML = `<div class="text-center text-muted py-4">No sales orders yet.</div>`;
        return;
    }

    let rows = '';
    sos.forEach(so => {
        rows += `
            <tr>
                <td><a href="/sales/orders/${so.id}" class="fw-medium">${so.so_number}</a></td>
                <td class="text-muted small">${so.customer_name || '—'}</td>
                <td>${statusBadge(soStatusMap, so.status)}</td>
                <td class="small text-muted">${so.total_amount ? formatCurrency(so.total_amount) : '—'}</td>
            </tr>`;
    });

    el.innerHTML = `
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>SO #</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>`;
};

const renderOutOfStock = function(products) {
    const el = document.getElementById('outOfStockWidget');
    if (!products || products.length === 0) {
        el.innerHTML = `<div class="text-center text-muted py-4">No out-of-stock products.</div>`;
        return;
    }

    let rows = '';
    products.forEach(p => {
        rows += `
            <tr>
                <td class="fw-medium">${p.name}</td>
                <td class="small text-muted">${p.sku || '—'}</td>
                <td><span class="badge bg-label-danger">Out of Stock</span></td>
            </tr>`;
    });

    el.innerHTML = `
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>`;
};

document.addEventListener('DOMContentLoaded', async function() {
    try {
        const res  = await api.get('/dashboard/summary');
        const data = res.data.data;

        renderKpis(data.kpis);
        renderCrmPipeline(data.crm_pipeline);
        renderDueActivities(data.due_activities);
        renderRecentPos(data.recent_pos);
        renderRecentSos(data.recent_sos);
        renderOutOfStock(data.out_of_stock);

    } catch (e) {
        notyf.error('Failed to load dashboard data.');
    }
});
</script>
@endpush