<div class="offcanvas offcanvas-end" tabindex="-1" id="rolePermissionsDrawer" aria-labelledby="rolePermissionsDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width:520px;">
    <div class="offcanvas-header">
        <div>
            <h5 id="rolePermissionsDrawerTitle" class="offcanvas-title mb-0">Permissions</h5>
            <small id="rolePermissionsRoleName" class="text-muted"></small>
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">

        {{-- Skeleton --}}
        <div id="permSkeleton">
            @for($i = 0; $i < 3; $i++)
            <div class="mb-4">
                <div class="placeholder-glow mb-2"><span class="placeholder col-4"></span></div>
                <div class="placeholder-glow mb-1"><span class="placeholder col-7"></span></div>
                <div class="placeholder-glow mb-1"><span class="placeholder col-6"></span></div>
                <div class="placeholder-glow"><span class="placeholder col-5"></span></div>
            </div>
            @endfor
        </div>

        {{-- Super admin notice --}}
        <div id="permSuperNotice" class="d-none">
            <div class="alert alert-info d-flex align-items-start gap-2">
                <i class="bx bx-shield-check fs-5 mt-1 flex-shrink-0"></i>
                <div>
                    <div class="fw-semibold">Full Admin Access</div>
                    <div class="small mt-1">This is an administrator role. It has access to all features available under the company's subscription — no individual grants needed.</div>
                </div>
            </div>
        </div>

        {{-- Permissions list --}}
        <div id="permContent" class="d-none">
            <p class="text-muted small mb-4">Check modules or individual features to grant access. Granting a full module covers all its features automatically.</p>
            <div id="permModuleList"></div>
        </div>

    </div>
    <div class="offcanvas-footer d-none" id="permFooter">
        <div class="d-flex gap-3">
            <button type="button" id="savePermBtn" class="btn btn-primary btn-sm w-px-120">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
'use strict';

let _permRoleId = null;

const openRolePermissionsDrawer = async function(roleId) {
    _permRoleId = roleId;

    const drawerEl = document.getElementById('rolePermissionsDrawer');

    document.getElementById('rolePermissionsRoleName').textContent = '';
    document.getElementById('permSkeleton').classList.remove('d-none');
    document.getElementById('permSuperNotice').classList.add('d-none');
    document.getElementById('permContent').classList.add('d-none');
    document.getElementById('permFooter').classList.add('d-none');
    document.getElementById('permModuleList').innerHTML = '';

    new bootstrap.Offcanvas(drawerEl).show();

    try {
        const res  = await api.get(`/users/roles/${roleId}/permissions`);
        const data = res.data.data;

        document.getElementById('rolePermissionsRoleName').textContent = data.role.name;
        document.getElementById('permSkeleton').classList.add('d-none');

        if (parseInt(data.role.is_super) === 1) {
            document.getElementById('permSuperNotice').classList.remove('d-none');
        } else {
            renderPermissions(data);
            document.getElementById('permContent').classList.remove('d-none');
            document.getElementById('permFooter').classList.remove('d-none');
        }
    } catch (err) {
        document.getElementById('permSkeleton').classList.add('d-none');
        handleApiError(err);
    }
};

function renderPermissions(data) {
    const grantedModules  = new Set(data.grants.modules.map(Number));
    const grantedFeatures = new Set(data.grants.features.map(Number));
    const listEl          = document.getElementById('permModuleList');

    if (!data.modules || data.modules.length === 0) {
        listEl.innerHTML = '<p class="text-muted small">No features available for your subscription.</p>';
        return;
    }

    data.modules.forEach(function(mod) {
        const modGranted      = grantedModules.has(mod.id);
        const featureCheckboxes = mod.features.map(function(f) {
            const featureGranted = modGranted || grantedFeatures.has(f.id);
            return `
                <div class="form-check ms-3 mb-1">
                    <input class="form-check-input feature-cb feature-cb-${mod.id}"
                           type="checkbox"
                           id="feature_${f.id}"
                           data-feature-id="${f.id}"
                           ${featureGranted ? 'checked' : ''}
                           ${modGranted ? 'disabled' : ''}>
                    <label class="form-check-label small" for="feature_${f.id}">${f.name}</label>
                </div>`;
        }).join('');

        const block = document.createElement('div');
        block.className = 'mb-4';
        block.innerHTML = `
            <div class="form-check mb-2">
                <input class="form-check-input module-cb"
                       type="checkbox"
                       id="module_${mod.id}"
                       data-module-id="${mod.id}"
                       ${modGranted ? 'checked' : ''}
                       onchange="onModuleCheckboxChange(${mod.id})">
                <label class="form-check-label fw-semibold" for="module_${mod.id}">${mod.name} <span class="text-muted fw-normal small">— full access</span></label>
            </div>
            ${featureCheckboxes}`;

        listEl.appendChild(block);
    });
}

function onModuleCheckboxChange(moduleId) {
    const modCb      = document.getElementById(`module_${moduleId}`);
    const featureCbs = document.querySelectorAll(`.feature-cb-${moduleId}`);

    featureCbs.forEach(function(cb) {
        cb.checked  = modCb.checked;
        cb.disabled = modCb.checked;
    });
}

function collectGrants() {
    const grants = [];

    document.querySelectorAll('.module-cb:checked').forEach(function(cb) {
        grants.push({ type: 'module', id: parseInt(cb.dataset.moduleId) });
    });

    document.querySelectorAll('.feature-cb:not([disabled]):checked').forEach(function(cb) {
        grants.push({ type: 'feature', id: parseInt(cb.dataset.featureId) });
    });

    return grants;
}

document.getElementById('savePermBtn').addEventListener('click', async function() {
    const btn    = document.getElementById('savePermBtn');
    const grants = collectGrants();

    btn.disabled    = true;
    btn.textContent = 'Saving…';

    try {
        await api.post(`/users/roles/${_permRoleId}/permissions`, { grants });
        notyf.success('Permissions saved successfully.');
        bootstrap.Offcanvas.getInstance(document.getElementById('rolePermissionsDrawer')).hide();
    } catch (err) {
        handleApiError(err);
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Save';
    }
});
</script>
@endpush
