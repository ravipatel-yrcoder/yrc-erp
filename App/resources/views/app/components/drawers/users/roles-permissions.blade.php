<div class="offcanvas offcanvas-end" tabindex="-1" id="rolePermissionsDrawer" aria-labelledby="rolePermissionsDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width:50%;min-width:680px;">
    <div class="offcanvas-header">
        <div>
            <h5 id="rolePermissionsDrawerTitle" class="offcanvas-title mb-0">Permissions</h5>
            <small id="rolePermissionsRoleName" class="text-muted"></small>
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">

        {{-- Skeleton --}}
        <div id="permSkeleton" class="p-4">
            @for($i = 0; $i < 3; $i++)
            <div class="mb-4">
                <div class="placeholder-glow mb-2"><span class="placeholder col-3"></span></div>
                <div class="placeholder-glow mb-1"><span class="placeholder col-12" style="height:32px;"></span></div>
                <div class="placeholder-glow mb-1"><span class="placeholder col-12" style="height:32px;"></span></div>
                <div class="placeholder-glow"><span class="placeholder col-12" style="height:32px;"></span></div>
            </div>
            @endfor
        </div>

        {{-- Super admin notice --}}
        <div id="permSuperNotice" class="d-none p-4">
            <div class="alert alert-info d-flex align-items-start gap-2 mb-0">
                <i class="bx bx-shield-check fs-5 mt-1 flex-shrink-0"></i>
                <div>
                    <div class="fw-semibold">Full Admin Access</div>
                    <div class="small mt-1">This is an administrator role. It has access to all features and modules — no individual configuration needed.</div>
                </div>
            </div>
        </div>

        {{-- Permissions content --}}
        <div id="permContent" class="d-none">
            {{-- Module activation switches --}}
            <div class="px-4 py-3 border-bottom bg-light">
                <div class="small fw-semibold text-uppercase mb-2" style="font-size:0.7rem;letter-spacing:.05em;">Module Access</div>
                <div id="permModuleSwitches" class="d-flex flex-wrap gap-3"></div>
            </div>
            {{-- Feature permission grid --}}
            <div id="permModuleList"></div>
        </div>

    </div>
    <div class="offcanvas-footer d-none" id="permFooter">
        <div class="d-flex gap-3">
            <button type="button" id="savePermBtn" class="btn btn-primary btn-sm min-w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>


@push('scripts')
<script>
'use strict';

let _permRoleId   = null;
let _permData     = null;
let _activatedSet = new Set(); // currently activated module keys in the drawer

const openRolePermissionsDrawer = async function(roleId) {
    _permRoleId   = roleId;
    _permData     = null;
    _activatedSet = new Set();

    const drawerEl = document.getElementById('rolePermissionsDrawer');
    document.getElementById('rolePermissionsRoleName').textContent = '';
    document.getElementById('permSkeleton').classList.remove('d-none');
    document.getElementById('permSuperNotice').classList.add('d-none');
    document.getElementById('permContent').classList.add('d-none');
    document.getElementById('permFooter').classList.add('d-none');
    document.getElementById('permModuleSwitches').innerHTML = '';
    document.getElementById('permModuleList').innerHTML = '';

    new bootstrap.Offcanvas(drawerEl).show();

    try {
        const res = await api.get(`/users/roles/${roleId}/permissions`);
        _permData = res.data.data;

        document.getElementById('rolePermissionsRoleName').textContent = _permData.role.name;
        document.getElementById('permSkeleton').classList.add('d-none');

        if (parseInt(_permData.role.is_admin) === 1) {
            document.getElementById('permSuperNotice').classList.remove('d-none');
        } else {
            // Seed activated set — system modules always active, others from saved state
            (_permData.modules || []).forEach(function(m) {
                if (m.is_system || m.activated) _activatedSet.add(m.key);
            });

            renderModuleSwitches(_permData.modules);
            renderPermissions();

            document.getElementById('permContent').classList.remove('d-none');
            document.getElementById('permFooter').classList.remove('d-none');
        }
    } catch (err) {
        document.getElementById('permSkeleton').classList.add('d-none');
        handleApiError(err);
    }
};


// ─── Module switches ────────────────────────────────────────────────────────

function renderModuleSwitches(modules) {
    const container = document.getElementById('permModuleSwitches');
    container.innerHTML = '';

    (modules || []).forEach(function(mod) {
        const div = document.createElement('div');
        div.className = 'form-check form-switch mb-0';

        if (mod.is_system) {
            div.innerHTML = `
                <input class="form-check-input" type="checkbox" role="switch"
                    id="modSwitch_${mod.key}" checked disabled>
                <label class="form-check-label small d-flex align-items-center gap-1" for="modSwitch_${mod.key}">
                    ${mod.name} <i class="bx bx-lock-alt text-muted" style="font-size:0.75rem;" title="Always On"></i>
                </label>`;
            container.appendChild(div);
            return; // no change listener for system modules
        }

        const checked = _activatedSet.has(mod.key);
        div.innerHTML = `
            <input class="form-check-input module-switch" type="checkbox" role="switch"
                id="modSwitch_${mod.key}" data-module-key="${mod.key}" ${checked ? 'checked' : ''}>
            <label class="form-check-label small" for="modSwitch_${mod.key}">${mod.name}</label>`;
        container.appendChild(div);

        div.querySelector('.module-switch').addEventListener('change', function() {
            if (this.checked) {
                _activatedSet.add(mod.key);
            } else {
                _activatedSet.delete(mod.key);
            }
            renderPermissions();
        });
    });
}


// ─── Feature permission grid ─────────────────────────────────────────────────

function renderPermissions() {
    const listEl = document.getElementById('permModuleList');

    // Snapshot current unsaved state before clearing the DOM
    const _grantSnapshot = {};
    listEl.querySelectorAll('.perm-cb').forEach(function(cb) {
        _grantSnapshot[cb.dataset.permissionId] = cb.checked;
    });
    const _scopeSnapshot = {};
    listEl.querySelectorAll('.scope-select').forEach(function(sel) {
        _scopeSnapshot[sel.dataset.featureKey] = sel.value;
    });

    listEl.innerHTML = '';
    if (!_permData) return;

    const features       = _permData.features       || [];
    const sharedFeatures = _permData.shared_features || [];
    const modules        = _permData.modules         || [];

    // ── Point-4: assign each feature to its display module ───────────────────
    const moduleSections = {};
    features.forEach(function(f) {
        let displayModule = null;
        if (_activatedSet.has(f.primary_module)) {
            displayModule = f.primary_module;
        } else {
            for (const sharedMk of (f.shared_modules || [])) {
                if (_activatedSet.has(sharedMk)) { displayModule = sharedMk; break; }
            }
        }
        if (displayModule === null) return;
        if (!moduleSections[displayModule]) moduleSections[displayModule] = [];
        const displayName = (f.display_names && f.display_names[displayModule]) || f.name;
        moduleSections[displayModule].push({ ...f, display_name: displayName });
    });

    const BASE_ACTIONS = ['read', 'write', 'delete'];

    // ── Build per-section data (name, features, section-specific extras) ──────
    const sectionList = [];

    // System features first
    if (sharedFeatures.length > 0) {
        const extSet = new Set();
        sharedFeatures.forEach(f => (f.permissions||[]).forEach(p => { if (!BASE_ACTIONS.includes(p.action)) extSet.add(p.action); }));
        sectionList.push({ key: null, name: 'System', features: sharedFeatures, extras: Array.from(extSet) });
    }

    modules.forEach(function(mod) {
        if (!_activatedSet.has(mod.key)) return;
        const sf = moduleSections[mod.key] || [];
        if (!sf.length) return;
        const extSet = new Set();
        sf.forEach(f => (f.permissions||[]).forEach(p => { if (!BASE_ACTIONS.includes(p.action)) extSet.add(p.action); }));
        sectionList.push({ key: mod.key, name: mod.name, features: sf, extras: Array.from(extSet) });
    });

    const adminFeatures = _permData.admin_features || [];
    if (adminFeatures.length > 0) {
        const extSet = new Set();
        adminFeatures.forEach(f => (f.permissions||[]).forEach(p => { if (!BASE_ACTIONS.includes(p.action)) extSet.add(p.action); }));
        sectionList.push({ key: null, name: 'Administration', features: adminFeatures, extras: Array.from(extSet) });
    }

    if (sectionList.length === 0) {
        listEl.innerHTML = _activatedSet.size === 0
            ? '<p class="text-muted small p-4 mb-0">Enable at least one module above to configure feature permissions.</p>'
            : '<p class="text-muted small p-4 mb-0">No features available for the selected modules.</p>';
        return;
    }

    // Max extra-column count across all sections — all tables use the same count
    // so the name column absorbs the difference and base columns stay aligned.
    const maxExtras = Math.max(0, ...sectionList.map(s => s.extras.length));

    sectionList.forEach(function(section) {
        listEl.appendChild(buildModuleSection(section.key, section.name, section.features, BASE_ACTIONS, section.extras, maxExtras, _grantSnapshot, _scopeSnapshot));
    });

    listEl.addEventListener('change', function(e) {
        const cb = e.target;
        if (!cb.classList.contains('perm-cb') || cb.dataset.action !== 'read') return;
        applyReadGating(cb.closest('.feature-row'), cb.checked);
    });
}


function buildModuleSection(moduleKey, moduleName, features, BASE_ACTIONS, sectionExtras, maxExtras, grantSnapshot, scopeSnapshot) {
    const blankCount     = maxExtras - sectionExtras.length;
    const orderedActions = [...BASE_ACTIONS, ...sectionExtras];

    // Each section is its own table. All tables share the same colgroup widths
    // (name=auto, scope=120px, base=58px×3, extras=58px×maxExtras) so that
    // Data Access / Read / Write / Delete land at the same pixel position in every section.
    const table = document.createElement('table');
    table.className = 'border-bottom';
    table.style.cssText = 'width:100%;table-layout:fixed;border-collapse:collapse;';
    if (moduleKey) table.dataset.moduleKey = moduleKey;

    const extraColHtml = Array(maxExtras).fill('<col style="width:58px;">').join('');
    table.innerHTML = `<colgroup>
        <col>
        <col style="width:120px;">
        ${BASE_ACTIONS.map(() => '<col style="width:58px;">').join('')}
        ${extraColHtml}
    </colgroup>`;

    // Header row: base columns always labeled, section extras in sequence, blank at end
    const blankHdrTds = Array(blankCount).fill(`<td style="background:#f8f9fa;"></td>`).join('');
    const hdrTr = document.createElement('tr');
    hdrTr.innerHTML =
        `<td class="px-4 py-2 fw-semibold text-uppercase text-muted" style="font-size:0.7rem;letter-spacing:.05em;background:#f8f9fa;">${moduleName}</td>` +
        `<td class="py-2 text-muted" style="font-size:0.7rem;background:#f8f9fa;">Data Access</td>` +
        orderedActions.map(a =>
            `<td class="py-2 text-center text-muted" style="font-size:0.7rem;background:#f8f9fa;">${actionLabel(a)}</td>`
        ).join('') +
        blankHdrTds;
    table.appendChild(hdrTr);

    // Which actions have at least one checkbox anywhere in this section
    const activeActionSet = new Set();
    features.forEach(f => (f.permissions||[]).forEach(p => activeActionSet.add(p.action)));

    // Whether any feature in this section is scopeable (drives — vs empty for scope col)
    const anyScopeable = features.some(f => f.is_scopeable);

    // Feature rows
    features.forEach(function(f) {
        const permMap = {};
        (f.permissions || []).forEach(function(p) { permMap[p.action] = p; });
        const readPerm = permMap['read'];
        const readGranted = readPerm
            ? (grantSnapshot && String(readPerm.permission_id) in grantSnapshot
                ? grantSnapshot[String(readPerm.permission_id)]
                : readPerm.granted)
            : false;
        const currentScope = (scopeSnapshot && f.key in scopeSnapshot)
            ? scopeSnapshot[f.key]
            : ((readPerm && readPerm.data_scope) || 'all');

        const scopeTd = f.is_scopeable
            ? `<td class="py-2 pe-2">
                <select class="form-select form-select-sm scope-select" data-feature-key="${f.key}" ${!readGranted ? 'disabled' : ''}>
                    <option value="all"  ${currentScope === 'all'  ? 'selected' : ''}>All</option>
                    <option value="team" ${currentScope === 'team' ? 'selected' : ''}>Team</option>
                    <option value="own"  ${currentScope === 'own'  ? 'selected' : ''}>Own</option>
                </select></td>`
            : `<td class="py-2 text-center text-muted">—</td>`;

        const actionTds = orderedActions.map(function(action) {
            const perm = permMap[action];
            if (!perm) {
                // Show — only if another row in this section has this action
                return activeActionSet.has(action)
                    ? `<td class="py-2 text-center text-muted">—</td>`
                    : `<td></td>`;
            }
            const isGranted = (grantSnapshot && String(perm.permission_id) in grantSnapshot)
                ? grantSnapshot[String(perm.permission_id)]
                : perm.granted;
            return `<td class="py-2 text-center">
                <input type="checkbox" class="form-check-input perm-cb"
                    data-permission-id="${perm.permission_id}"
                    data-action="${action}"
                    data-feature-key="${f.key}"
                    ${isGranted ? 'checked' : ''}
                    ${action !== 'read' && !readGranted ? 'disabled' : ''}>
            </td>`;
        }).join('');

        const tr = document.createElement('tr');
        tr.className = 'feature-row border-top';
        tr.dataset.featureKey = f.key;
        tr.innerHTML =
            `<td class="px-4 py-2 small">${f.display_name || f.name}</td>` +
            scopeTd + actionTds +
            Array(blankCount).fill('<td></td>').join('');
        table.appendChild(tr);
    });

    return table;
}


function applyReadGating(row, readChecked) {
    if (!row) return;
    row.querySelectorAll('.perm-cb').forEach(function(cb) {
        if (cb.dataset.action !== 'read') {
            cb.disabled = !readChecked;
            if (!readChecked) cb.checked = false;
        }
    });
    const scopeSel = row.querySelector('.scope-select');
    if (scopeSel) {
        scopeSel.disabled = !readChecked;
        if (!readChecked) scopeSel.value = 'all';
    }
}


function actionLabel(action) {
    const labels = {
        read: 'Read', write: 'Write', delete: 'Delete',
        confirm: 'Confirm', cancel: 'Cancel', convert: 'Convert',
        mark_complete: 'Done', send_email: 'Email', receive: 'Receive',
        material_allocation: 'Material Allocate', produce: 'Produce', material_return: 'Material Return',
    };
    return labels[action] || action.charAt(0).toUpperCase() + action.slice(1);
}


// ─── Collect grants for save ─────────────────────────────────────────────────

function collectGrants() {
    const grants  = new Map(); // permission_id → grant object (deduplicates shared features)
    const listEl  = document.getElementById('permModuleList');

    const scopeMap = {};
    listEl.querySelectorAll('.scope-select').forEach(function(sel) {
        if (!(sel.dataset.featureKey in scopeMap)) {
            scopeMap[sel.dataset.featureKey] = sel.value;
        }
    });

    listEl.querySelectorAll('.perm-cb').forEach(function(cb) {
        const permissionId = parseInt(cb.dataset.permissionId);
        if (grants.has(permissionId)) return;

        const featureKey = cb.dataset.featureKey;
        const action     = cb.dataset.action;
        const granted    = cb.checked;
        const dataScope  = action === 'read' ? (scopeMap[featureKey] || 'all') : 'all';

        grants.set(permissionId, { permission_id: permissionId, granted, data_scope: dataScope });
    });

    return Array.from(grants.values());
}


// ─── Save ────────────────────────────────────────────────────────────────────

document.getElementById('savePermBtn').addEventListener('click', async function() {
    const btn    = document.getElementById('savePermBtn');
    const grants = collectGrants();

    // Auto-deactivate any non-system module whose section has no checked permissions
    const activatedModules = [];
    _activatedSet.forEach(function(moduleKey) {
        // System modules are always active server-side — never include in payload
        const modData = (_permData.modules || []).find(m => m.key === moduleKey);
        if (modData && modData.is_system) return;

        const table    = document.querySelector(`#permModuleList table[data-module-key="${moduleKey}"]`);
        const hasGrant = table && Array.from(table.querySelectorAll('.perm-cb')).some(cb => cb.checked);
        if (hasGrant) {
            activatedModules.push(moduleKey);
        } else {
            _activatedSet.delete(moduleKey);
            const sw = document.querySelector(`.module-switch[data-module-key="${moduleKey}"]`);
            if (sw) sw.checked = false;
        }
    });

    setButtonLoading(btn, true);
    try {
        await api.post(`/users/roles/${_permRoleId}/permissions`, { grants, activated_modules: activatedModules });
        notyf.success('Permissions saved successfully.');
        bootstrap.Offcanvas.getInstance(document.getElementById('rolePermissionsDrawer')).hide();
    } catch (err) {
        handleApiError(err);
    } finally {
        setButtonLoading(btn, false);
    }
});
</script>
@endpush
