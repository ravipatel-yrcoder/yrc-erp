<div class="offcanvas offcanvas-end" tabindex="-1" id="linkCustomerDrawer" aria-labelledby="linkCustomerDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 32%;">

    <div class="offcanvas-header">
        <h5 id="linkCustomerDrawerTitle" class="offcanvas-title">Link to Existing Customer</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">

        {{-- Suggested matches --}}
        <div id="link_cust_suggestions_section" style="display:none;">
            <p class="text-muted small mb-2">Suggested matches based on lead contact details:</p>
            <div id="link_cust_suggestions_list" class="mb-4"></div>
        </div>

        {{-- Search --}}
        <div class="mb-3">
            <label class="form-label">Search customers</label>
            <select class="form-select" id="link_cust_search_select">
                <option value=""></option>
            </select>
        </div>

        {{-- Selected customer preview --}}
        <div id="link_cust_selected_preview" style="display:none;" class="alert alert-light border mb-3 p-3">
            <div class="small text-muted mb-1">Selected customer</div>
            <div id="link_cust_selected_name" class="fw-semibold"></div>
            <div id="link_cust_selected_meta" class="small text-muted"></div>
        </div>

    </div>

    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveLinkCustomer" class="btn btn-primary btn-sm w-px-150" disabled>Link Customer</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>


@push('scripts')
<script>
let _linkCustLeadId = null;
let _linkCustSelectedId = null;

const setLinkCustomerSelection = function(id, name, email, phone) {
    _linkCustSelectedId = id || null;

    const preview = document.getElementById('link_cust_selected_preview');
    const saveBtn = document.getElementById('saveLinkCustomer');

    if (id) {
        document.getElementById('link_cust_selected_name').textContent = name || '';
        const meta = [email, phone].filter(Boolean).join(' · ');
        document.getElementById('link_cust_selected_meta').textContent = meta;
        preview.style.display = '';
        saveBtn.disabled = false;
    } else {
        preview.style.display = 'none';
        saveBtn.disabled = true;
    }
};

const openLinkCustomerDrawer = async function(leadId) {

    _linkCustLeadId = leadId;
    _linkCustSelectedId = null;

    const drawerEl = document.getElementById('linkCustomerDrawer');
    const saveBtn  = document.getElementById('saveLinkCustomer');

    saveBtn.disabled = true;
    document.getElementById('link_cust_selected_preview').style.display = 'none';
    document.getElementById('link_cust_suggestions_section').style.display = 'none';
    document.getElementById('link_cust_suggestions_list').innerHTML = '';

    // Reset Select2
    initSelect2('#link_cust_search_select', {
        dropdownParent: drawerEl,
        placeholder: 'Search by name, email or phone...',
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: '/api/customers/search',
            dataType: 'json',
            delay: 300,
            data: function(params) { return { q: params.term }; },
            processResults: function(response) {
                const rows = response.data || [];
                return {
                    results: rows.map(r => ({
                        id: r.id,
                        text: r.display_name,
                        email: r.email,
                        phone: r.phone,
                    }))
                };
            },
        },
        onChange: function(el) {
            const selected = jQuery('#link_cust_search_select').select2('data')[0];
            if (selected && selected.id) {
                setLinkCustomerSelection(selected.id, selected.text, selected.email, selected.phone);
            } else {
                setLinkCustomerSelection(null);
            }
        },
    });

    // Load convert context for duplicate suggestions
    try {
        const response = await api.get(`/crm/leads/${leadId}/convert-context`);
        const { data } = response.data;
        const suggestions = data.duplicate_suggestions || [];

        if (suggestions.length > 0) {
            const section = document.getElementById('link_cust_suggestions_section');
            const list    = document.getElementById('link_cust_suggestions_list');
            list.innerHTML = '';

            suggestions.forEach(c => {
                const meta = [c.email, c.phone].filter(Boolean).join(' · ');
                const card = document.createElement('div');
                card.className = 'border rounded p-3 mb-2 d-flex justify-content-between align-items-center';
                card.innerHTML = `
                    <div>
                        <div class="fw-semibold">${c.display_name}</div>
                        ${meta ? `<div class="small text-muted">${meta}</div>` : ''}
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary link-cust-suggestion-btn flex-shrink-0 ms-3"
                        data-id="${c.id}" data-name="${c.display_name.replace(/"/g, '&quot;')}"
                        data-email="${c.email || ''}" data-phone="${c.phone || ''}">
                        Select
                    </button>
                `;
                list.appendChild(card);
            });

            section.style.display = '';
        }
    } catch(e) {
        // suggestions not critical — continue
    }

    new bootstrap.Offcanvas(drawerEl).show();
};

document.getElementById('link_cust_suggestions_list').addEventListener('click', function(e) {
    const btn = e.target.closest('.link-cust-suggestion-btn');
    if (!btn) return;
    setLinkCustomerSelection(
        parseInt(btn.dataset.id),
        btn.dataset.name,
        btn.dataset.email,
        btn.dataset.phone
    );
});

document.getElementById('saveLinkCustomer').addEventListener('click', async function() {

    if (!_linkCustLeadId || !_linkCustSelectedId) return;

    const btn = this;
    btn.disabled = true;

    try {
        const response = await api.post(`/crm/leads/${_linkCustLeadId}/convert`, {
            action: 'link',
            customer_id: _linkCustSelectedId,
        });

        const { message, data } = response.data;
        notyf.success(message);

        bootstrap.Offcanvas.getInstance(document.getElementById('linkCustomerDrawer')).hide();
        document.dispatchEvent(new CustomEvent('leadConverted', { detail: data }));

    } catch (error) {
        btn.disabled = false;
        handleApiError(error);
    }
});
</script>
@endpush
