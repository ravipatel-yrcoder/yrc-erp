<div class="modal stacked-modal fade" data-bs-backdrop="static" id="invSerialPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="invSpTitle">Choose Serial/Lot Numbers</h5>                
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>                
            </div>
            <div class="modal-body">

                <!-- Product Info -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>Prod: <div class="fw-semibold" id="prodName">-</div></div>
                    <div>Location: <div class="fw-semibold" id="location">-</div></div>
                </div>

                <hr class="my-4">                
                
                <div class="mb-2">
                    <input type="text" class="form-control form-control-sm" id="invSpSearch" placeholder="Search serial numbers..." autocomplete="off" />
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">Available serials at selected location</small>
                    <small class="fw-bold">
                        <span id="invSpSelectedCount">0</span> / <span id="invSpRequiredCount">0</span> selected
                    </small>
                </div>
                
                <div id="invSpList" style="min-height:100px;max-height:340px;overflow-y:auto;border:1px solid #e0e0e0;border-radius:6px;">
                    <div class="text-center text-muted py-4"><small>Loading...</small></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="invSpConfirmBtn" disabled>Confirm</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var _sp = { qty: 0, selected: [], allSerials: [], onConfirm: null };

    var renderList = function(filter) {
        var listEl = document.getElementById('invSpList');
        var lc = (filter || '').toLowerCase().trim();
        var filtered = lc ? _sp.allSerials.filter(function(s) { return s.toLowerCase().includes(lc); }) : _sp.allSerials;

        if (_sp.allSerials.length === 0) {
            listEl.innerHTML = '<div class="text-center text-muted py-4"><small>No serial numbers available at this location</small></div>';
            return;
        }
        if (filtered.length === 0) {
            listEl.innerHTML = '<div class="text-center text-muted py-4"><small>No results match your search</small></div>';
            return;
        }

        var atLimit = _sp.selected.length >= _sp.qty;
        var html = '';
        filtered.forEach(function(sn) {
            var isSel = _sp.selected.includes(sn);
            var isDis = !isSel && atLimit;
            html += '<div class="d-flex align-items-center px-3 py-2 inv-sp-row" style="border-bottom:1px solid #f5f5f5;cursor:pointer;">'
                  + '<input type="checkbox" class="form-check-input me-2 flex-shrink-0 inv-sp-checkbox" value="' + sn + '"'
                  + (isSel ? ' checked' : '') + (isDis ? ' disabled' : '') + ' />'
                  + '<span class="small font-monospace">' + sn + '</span>'
                  + '</div>';
        });
        listEl.innerHTML = html;
    };

    var updateCounter = function() {
        document.getElementById('invSpSelectedCount').textContent = _sp.selected.length;
        document.getElementById('invSpConfirmBtn').disabled = (_sp.selected.length !== _sp.qty);
    };

    window.openSerialPicker = async function(config) {
        _sp.qty        = config.qty || 0;
        _sp.selected   = (config.currentSerials || []).slice();
        _sp.allSerials = [];
        _sp.onConfirm  = config.onConfirm || null;

        document.querySelector('#invSerialPickerModal #prodName').innerHTML = config.productName || '-';
        document.querySelector('#invSerialPickerModal #location').innerHTML = config.locationLabel || '-';
        document.getElementById('invSpRequiredCount').textContent = _sp.qty;
        document.getElementById('invSpSearch').value = '';
        document.getElementById('invSpList').innerHTML = '<div class="text-center text-muted py-4"><small>Loading...</small></div>';
        updateCounter();

        bootstrap.Modal.getOrCreateInstance(document.getElementById('invSerialPickerModal')).show();

        try {
            var params = { location_id: config.locationId };
            if (config.dnItemId) params.dn_item_id = config.dnItemId;
            var response = await api.get('/inv/products/' + config.productId + '/serial-or-lot-numbers', { params: params });
            _sp.allSerials = response.data?.data || [];
            renderList();
        } catch (err) {
            document.getElementById('invSpList').innerHTML = '<div class="text-center text-danger py-4"><small>Failed to load serial numbers</small></div>';
        }
    };

    document.getElementById('invSpList').addEventListener('change', function(e) {
        var cb = e.target.closest('.inv-sp-checkbox');
        if (!cb) return;
        var sn = cb.value;
        if (cb.checked) {
            if (_sp.selected.length < _sp.qty) { _sp.selected.push(sn); }
            else { cb.checked = false; return; }
        } else {
            _sp.selected = _sp.selected.filter(function(s) { return s !== sn; });
        }
        updateCounter();
        renderList(document.getElementById('invSpSearch').value);
    });

    document.getElementById('invSpList').addEventListener('click', function(e) {
        var row = e.target.closest('.inv-sp-row');
        if (!row || e.target.closest('.inv-sp-checkbox')) return;
        var cb = row.querySelector('.inv-sp-checkbox');
        if (!cb || cb.disabled) return;
        cb.checked = !cb.checked;
        cb.dispatchEvent(new Event('change', { bubbles: true }));
    });

    document.getElementById('invSpSearch').addEventListener('input', function() { renderList(this.value); });

    document.getElementById('invSpConfirmBtn').addEventListener('click', function() {
        if (_sp.selected.length !== _sp.qty) return;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('invSerialPickerModal')).hide();
        if (typeof _sp.onConfirm === 'function') { _sp.onConfirm(_sp.selected.slice()); }
    });
})();
</script>
@endpush