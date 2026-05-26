@extends('layouts.app')
@section('title', 'Pipeline')

@section('content')

<div class="container-fluid">

    <!-- Page Header -->
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Pipeline</h4>
        <p class="text-muted mb-0 small">Drag leads between stages to update their progress.</p>
    </div>

    <!-- Kanban Board -->
    <div id="pipelineBoard" class="d-flex gap-3 pb-3" style="overflow-x: auto; align-items: flex-start; height: calc(100vh - 110px);">
        <div class="text-muted fst-italic p-4">Loading pipeline...</div>
    </div>

</div>

@if(tenantContext()->canDo('crm_leads', 'write'))
@includeOnce('app.components.drawers.crm.leads.add-edit')
@endif

@endsection

@push('scripts')
<script src="{{ asset('/assets/vendor/libs/sortablejs/sortable.js') }}"></script>

<script>
let sortableInstances = [];

const priorityBadge = function(priority) {
    const p = window.crmLeadPriorities.find(x => x.key === priority) || { label: priority, color: 'secondary' };
    return `<span class="badge bg-label-${p.color} fs-tiny">${p.label}</span>`;
};

const statusBadge = function(status) {
    const map = { active: ['Active','primary'], won: ['Won','success'], lost: ['Lost','danger'] };
    const s = map[status] || [status, 'secondary'];
    return `<span class="badge bg-label-${s[1]} fs-tiny">${s[0]}</span>`;
};

const formatRevenue = function(val) {
    if (!val) return '';
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: window.sysDefaultConfig?.currency || 'USD',
        maximumFractionDigits: 0
    }).format(val);
};

const userInitials = function(name) {
    if (!name) return '?';
    return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
};


const buildCard = function(lead) {
    const revenue = lead.expected_revenue ? `<div class="text-muted small mt-1">${formatRevenue(lead.expected_revenue)}</div>` : '';
    const company = lead.company_name ? `<div class="text-muted small text-truncate">${lead.company_name}</div>` : '';
    const assignee = lead.assigned_user_name ? `<span class="avatar avatar-xs rounded-circle bg-label-primary flex-shrink-0" style="width:22px;height:22px;font-size:10px;display:inline-flex;align-items:center;justify-content:center;" title="${lead.assigned_user_name}">${userInitials(lead.assigned_user_name)}</span>` : '';
    const closedClass = (lead.status === 'won' || lead.status === 'lost') ? ' opacity-75' : '';

    const dragCursor = canDo('crm_leads', 'write') ? 'grab' : 'default';
    return `<div class="kanban-card card mb-2${closedClass}" data-lead-id="${lead.id}" style="cursor:${dragCursor};">
        <div class="card-body p-2 px-3">
            <div class="d-flex justify-content-between align-items-start gap-1 mb-1">
                <a href="/crm/leads/${lead.id}/" class="fw-medium text-truncate small" style="max-width:160px;" title="View lead">${lead.display_name}</a>
                ${priorityBadge(lead.priority)}
            </div>
            ${company}
            ${revenue}
            <div class="d-flex justify-content-between align-items-center mt-2">
                ${statusBadge(lead.status)}
                ${assignee}
            </div>
        </div>
    </div>`;
};


const buildColumn = function(stage) {

    const color    = stage.color || '#6c757d';
    const stageId  = stage.id ?? '';
    const revenue  = stage.total_revenue > 0 ? `<span class="text-muted small ms-auto">${formatRevenue(stage.total_revenue)}</span>` : '';
    const cardsHtml = stage.leads.map(buildCard).join('');

    return `<div class="kanban-column flex-shrink-0" style="width:280px;">
        <div class="card h-100">
            <div class="card-header py-2 px-3 d-flex align-items-center gap-2" style="border-top:3px solid ${color};">
                <span class="fw-semibold text-truncate" style="max-width:160px;">${stage.name}</span>
                <span class="badge bg-label-secondary" id="col-count-${stageId}">${stage.lead_count}</span>
                ${revenue}
            </div>
            <div class="card-body p-2 kanban-column-body"
                 data-stage-id="${stageId}"
                 style="min-height:120px;overflow-y:auto;max-height:calc(100vh - 300px);">
                ${cardsHtml}
            </div>
            <div class="card-footer py-2 px-3">
                ${canDo('crm_leads', 'write') ? `<a href="javascript:void(0);" class="text-muted small text-decoration-none"
                   onclick="openLeadFormDrawer(0, ${stageId ? stageId : 'null'});">
                    <i class="bx bx-plus"></i> Add Lead
                </a>` : ''}
            </div>
        </div>
    </div>`;
};


const refreshColumnCounts = function() {
    
    document.querySelectorAll('.kanban-column-body').forEach(function(col) {
        
        const stageId = col.dataset.stageId;
        const count = col.querySelectorAll('.kanban-card').length;
        const badge = document.getElementById(`col-count-${stageId}`);
        if (badge) badge.textContent = count;

    });

};


const loadPipeline = async function() {

    const board = document.getElementById('pipelineBoard');
    board.innerHTML = '<div class="text-muted fst-italic p-4">Loading pipeline...</div>';

    sortableInstances.forEach(s => s.destroy());
    sortableInstances = [];

    try {

        const res = await api.get('/crm/leads/pipeline');
        const { stages = [] } = res.data.data;

        if (stages.length === 0) {
            board.innerHTML = '<div class="text-muted fst-italic p-4">No stages found. <a href="/crm/stages">Create stages</a> first.</div>';
            return;
        }

        board.innerHTML = stages.map(buildColumn).join('');

        // Only init SortableJS (drag-and-drop) when user has write permission
        if (!canDo('crm_leads', 'write')) return;

        document.querySelectorAll('.kanban-column-body').forEach(function(el) {

            const instance = Sortable.create(el, {
                group: 'pipeline',
                animation: 150,
                ghostClass: 'kanban-ghost',
                onEnd: async function(evt) {

                    const leadId = evt.item.dataset.leadId;
                    const toStageId = evt.to.dataset.stageId || '';

                    if (evt.from === evt.to && evt.oldIndex === evt.newIndex) return;

                    const getColIds = (colEl) =>
                        [...colEl.querySelectorAll('.kanban-card')].map(c => c.dataset.leadId);

                    const isCrossColumn = evt.from !== evt.to;

                    try {

                        const calls = [];

                        if (isCrossColumn) {
                            calls.push(api.post(`/crm/leads/${leadId}/stage`, { stage_id: toStageId }));
                        }

                        calls.push(api.post('/crm/leads/reorder', { leads: getColIds(evt.to) }));

                        if (isCrossColumn) {
                            calls.push(api.post('/crm/leads/reorder', { leads: getColIds(evt.from) }));
                        }

                        await Promise.all(calls);

                        refreshColumnCounts();

                    } catch(err) {
                        
                        // Revert card to original position
                        const fromEl   = evt.from;
                        const siblings = fromEl.querySelectorAll('.kanban-card');
                        
                        if (evt.oldIndex >= siblings.length) {
                            fromEl.appendChild(evt.item);
                        } else {
                            fromEl.insertBefore(evt.item, siblings[evt.oldIndex]);
                        }
                        
                        handleApiError(err);
                    }
                }
            });

            sortableInstances.push(instance);
        });

    } catch(err) {
        
        board.innerHTML = '<div class="text-danger p-4">Failed to load pipeline.</div>';
        handleApiError(err);
    }
};


document.addEventListener('leadFormSaved', function() {
    loadPipeline();
});

loadPipeline();
</script>

<style>
.kanban-ghost {opacity: 0.4;border: 2px dashed var(--bs-primary) !important;}
.kanban-card { transition: box-shadow 0.15s; }
.kanban-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.12); }
</style>
@endpush