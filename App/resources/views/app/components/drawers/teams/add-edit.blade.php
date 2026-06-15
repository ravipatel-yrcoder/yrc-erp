{{-- Team Add/Edit Drawer --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditTeam" aria-labelledby="addEditTeamDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="offcanvas-header">
        <h5 id="addEditTeamDrawerTitle" class="offcanvas-title">Add Team</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="addEditTeamForm">
            <div class="form-glob-feedback"></div>
            <input type="hidden" id="teamId" value="" />

            <div class="mb-4">
                <label class="form-label required">Team Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. North Sales Team" />
            </div>

            <div class="mb-4">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Optional description"></textarea>
            </div>
        </form>
    </div>
    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveTeamBtn" class="btn btn-primary btn-sm min-w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>

{{-- Team Members Drawer --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="teamMembersDrawer" aria-labelledby="teamMembersDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width:480px;">
    <div class="offcanvas-header">
        <div>
            <h5 id="teamMembersDrawerTitle" class="offcanvas-title mb-0">Team Members</h5>
            <small id="teamMembersTeamName" class="text-muted"></small>
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-4">
            <label class="form-label">Add Member</label>
            <div class="d-flex gap-2">
                <select id="teamMemberUserSelect" class="form-select" style="flex:1;"></select>
                <button type="button" id="addTeamMemberBtn" class="btn btn-primary btn-sm px-3">Add</button>
            </div>
        </div>
        <hr />
        <div id="teamMembersList">
            <p class="text-muted small">Loading members…</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
'use strict';

let _teamMembersTeamId   = null;
let _teamMembersAllUsers = [];

// ---- Add/Edit Team Drawer ----

const openTeamFormDrawer = async function(id = 0) {
    const isEdit = id > 0;
    document.getElementById('addEditTeamDrawerTitle').textContent = isEdit ? 'Edit Team' : 'Add Team';

    const drawerEl = document.getElementById('addEditTeam');
    const formEl   = document.getElementById('addEditTeamForm');

    cleanFormInputFeedback(formEl);
    formEl.reset();
    document.getElementById('teamId').value = '';

    if (isEdit) {
        try {
            const res  = await api.get(`/company/teams/${id}`);
            const data = res.data.data;
            document.getElementById('teamId').value = data.id || id;
            jQuery('#addEditTeam input[name="name"]').val(data.name || '');
            jQuery('#addEditTeam textarea[name="description"]').val(data.description || '');
        } catch (err) {
            handleApiError(err);
            return;
        }
    }

    new bootstrap.Offcanvas(drawerEl).show();
};

document.getElementById('saveTeamBtn').addEventListener('click', async function() {
    var btn = this;
    const formEl = document.getElementById('addEditTeamForm');
    const id     = document.getElementById('teamId').value || '';

    cleanFormInputFeedback(formEl);

    const payload = formDataToObject(new FormData(formEl));

    setButtonLoading(btn, true);
    try {
        const url = id ? `/company/teams/${id}` : `/company/teams`;
        const res = await api.post(url, payload);

        notyf.success(res.data.message);

        if (typeof teamsDt !== 'undefined') teamsDt.ajax.reload();

        bootstrap.Offcanvas.getInstance(document.getElementById('addEditTeam')).hide();
        formEl.reset();
    } catch (err) {
        handleApiError(err, formEl);
    } finally {
        setButtonLoading(btn, false);
    }
});

// ---- Members Drawer ----

const openTeamMembersDrawer = async function(teamId, teamName) {
    _teamMembersTeamId = teamId;

    document.getElementById('teamMembersTeamName').textContent = teamName;
    document.getElementById('teamMembersList').innerHTML = '<p class="text-muted small">Loading…</p>';

    const drawerEl = document.getElementById('teamMembersDrawer');
    new bootstrap.Offcanvas(drawerEl).show();

    try {
        const [membersRes, contextRes] = await Promise.all([
            api.get(`/company/teams/${teamId}/members`),
            api.get('/company/teams/form-context'),
        ]);

        _teamMembersAllUsers = contextRes.data.data.users || [];
        renderMembersList(membersRes.data.data || []);

        initSelect2('#teamMemberUserSelect', {
            dropdownParent: drawerEl,
            placeholder: 'Select user to add',
            data: buildSelect2Options(_teamMembersAllUsers),
        });
    } catch (err) {
        handleApiError(err);
    }
};

function renderMembersList(members) {
    const listEl = document.getElementById('teamMembersList');

    if (!members.length) {
        listEl.innerHTML = '<p class="text-muted small">No members yet. Add one above.</p>';
        return;
    }

    listEl.innerHTML = members.map(function(m) {
        return `<div class="d-flex align-items-center justify-content-between py-2 border-bottom" id="member-row-${m.id}">
            <div>
                <div class="fw-medium small">${m.name}</div>
                <div class="text-muted" style="font-size:0.75rem;">${m.email}</div>
            </div>
            <button class="btn btn-sm btn-icon btn-label-danger" title="Remove" onclick="removeTeamMember(${m.id})">
                <i class="bx bx-x"></i>
            </button>
        </div>`;
    }).join('');
}

document.getElementById('addTeamMemberBtn').addEventListener('click', async function() {
    const userId = parseInt(jQuery('#teamMemberUserSelect').val());
    if (!userId) return;

    try {
        await api.post(`/company/teams/${_teamMembersTeamId}/members`, { user_id: userId });
        notyf.success('Member added.');

        const res = await api.get(`/company/teams/${_teamMembersTeamId}/members`);
        renderMembersList(res.data.data || []);

        jQuery('#teamMemberUserSelect').val(null).trigger('change');
        if (typeof teamsDt !== 'undefined') teamsDt.ajax.reload();
    } catch (err) {
        handleApiError(err);
    }
});

async function removeTeamMember(userId) {
    try {
        await api.delete(`/company/teams/${_teamMembersTeamId}/members/${userId}`);
        notyf.success('Member removed.');
        document.getElementById(`member-row-${userId}`)?.remove();
        if (typeof teamsDt !== 'undefined') teamsDt.ajax.reload();
    } catch (err) {
        handleApiError(err);
    }
}
</script>
@endpush
