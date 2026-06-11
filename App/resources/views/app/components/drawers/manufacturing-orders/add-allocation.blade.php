<div class="offcanvas offcanvas-end" tabindex="-1" id="moAllocationDrawer" aria-labelledby="moAllocationDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width:58%;">

    <div class="offcanvas-header border-bottom">
        <h5 id="moAllocationDrawerTitle" class="offcanvas-title">Material Allocation</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">
        <div class="form-glob-feedback mb-3"></div>

        <div class="row g-3 mb-4 pb-3 border-bottom">
            <div class="col-md-4">
                <label class="form-label text-muted small mb-1">MO Number</label>
                <div class="fw-semibold" id="moAllocMoNumber">—</div>
            </div>
            <div class="col-md-4">
                <label class="form-label text-muted small mb-1">Source Warehouse</label>
                <div class="fw-semibold" id="moAllocLocation">—</div>
            </div>
            <div class="col-md-4">
                <label class="form-label text-muted small mb-1">Qty to Produce</label>
                <div class="fw-semibold" id="moAllocPlannedQty">—</div>
            </div>
        </div>

        <div class="mb-4">
            <h6 class="text-uppercase text-muted fw-semibold mb-2" style="font-size:0.72rem;letter-spacing:.05em;">Components</h6>
            <div class="table-responsive border rounded">
                <table class="table table-bordered table-sm align-middle m-0" id="moAllocItemsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="p-2">ITEM</th>
                            <th class="p-2 text-end" style="width:110px;">PLANNED QTY</th>
                            <th class="p-2 text-end" style="width:110px;">ISSUED</th>
                            <th class="p-2">ASSIGN</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea class="form-control" id="moAllocNotes" rows="2" placeholder="Optional notes for this allocation"></textarea>
        </div>
    </div>

    <div class="offcanvas-footer border-top">
        <div class="d-flex gap-3">
            <button type="button" id="moAllocSaveBtn" class="btn btn-primary btn-sm min-w-px-100">Confirm Allocation</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>

</div>

@push('scripts')
<script>
(function() {

    var _alloc = {
        moId:             0,
        locationId:       0,
        locationLabel:    '',
        plannedQty:       0,
        items:            [],
        pendingSerials:   {},   // { material_item_id: [serial_number, ...] }
        pendingSerialSet: new Set(),
        pendingQtys:      {},   // { material_item_id: float } — non-serial items
    };

    var renderAllocRows = function() {
        var tbody = document.querySelector('#moAllocItemsTable tbody');
        tbody.innerHTML = '';

        if (!_alloc.items.length) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted p-3">No components found</td></tr>';
            return;
        }

        _alloc.items.forEach(function(item) {
            var isSerial     = item.stock_tracking_method === 'serial';
            var planned      = parseFloat(item.planned_qty) || 0;
            var alreadyAlloc = parseFloat(item.allocated_qty) || 0;
            var remaining    = isSerial ? Math.ceil(planned) - alreadyAlloc : planned - alreadyAlloc;

            var assignCell = '';
            if (isSerial) {
                var sessionPicks  = (_alloc.pendingSerials[item.id] || []).length;
                var chipsHtml     = '';
                (_alloc.pendingSerials[item.id] || []).forEach(function(sn) {
                    chipsHtml += '<span class="badge bg-label-info me-1 mb-1 moalloc-chip" style="font-size:0.78em;" data-mi-id="' + item.id + '" data-sn="' + sn + '">'
                        + sn + ' <i class="bx bx-x ms-1 cursor-pointer moalloc-chip-remove"></i></span>';
                });

                var totalAfter   = alreadyAlloc + sessionPicks;
                var isOverPlan   = totalAfter > Math.ceil(planned);
                var badgeClass   = isOverPlan ? 'bg-warning text-dark' : 'bg-label-secondary';
                var badgeLabel   = remaining > 0 ? (sessionPicks + ' / ' + remaining) : (sessionPicks + ' extra');
                var btnLabel     = remaining > 0 ? 'Assign Serials' : 'Add Extra Serials';
                var pickerQty    = Math.max(0, remaining);

                var pickBtn = '<a href="javascript:void(0);" class="text-primary small d-inline-flex align-items-center gap-1 moalloc-pick-btn"'
                    + ' data-mi-id="' + item.id + '" data-prod-id="' + item.product_id + '" data-prod-name="' + (item.product_name || '') + '" data-needed="' + pickerQty + '">'
                    + '<i class="bx bx-barcode fs-6"></i> ' + btnLabel + ' <span class="badge ' + badgeClass + ' ms-1">' + badgeLabel + '</span>'
                    + '</a>';

                assignCell = pickBtn + (chipsHtml ? '<div class="mt-1">' + chipsHtml + '</div>' : '');
            } else {
                var currentVal = _alloc.pendingQtys[item.id] !== undefined ? _alloc.pendingQtys[item.id] : '';
                assignCell = '<input type="number" min="0" step="any" class="form-control form-control-sm moalloc-qty-input" style="width:90px;"'
                    + ' data-mi-id="' + item.id + '" value="' + currentVal + '" placeholder="0">';
            }

            var isOverAllocated = alreadyAlloc > (isSerial ? Math.ceil(planned) : planned);
            var overBadge       = isOverAllocated ? ' <span class="badge bg-warning text-dark ms-1" style="font-size:0.65em;">Over Plan</span>' : '';
            var uom          = item.uom_code ? ' ' + item.uom_code : '';
            var allocDisplay = alreadyAlloc <= 0 ? '—'
                : formatQty(alreadyAlloc) + uom + overBadge;

            var row = '<tr>'
                + '<td class="p-2"><div class="fw-semibold">' + (item.product_name || '—') + '</div></td>'
                + '<td class="p-2 text-end">' + formatQty(item.planned_qty) + (item.uom_code ? ' <span class="text-muted small fw-semibold">' + item.uom_code + '</span>' : '') + '</td>'
                + '<td class="p-2 text-end text-muted small">' + allocDisplay + '</td>'
                + '<td class="p-2">' + assignCell + '</td>'
                + '</tr>';
            tbody.insertAdjacentHTML('beforeend', row);
        });

        // Re-bind qty input listeners
        document.querySelectorAll('.moalloc-qty-input').forEach(function(input) {
            input.addEventListener('change', function() {
                var miId = parseInt(this.dataset.miId);
                var val  = parseFloat(this.value) || 0;
                _alloc.pendingQtys[miId] = val > 0 ? val : undefined;
            });
        });
    };

    window.openMoAllocationDrawer = function(moId) {
        _alloc.moId             = moId;
        _alloc.locationId       = parseInt(_moDetails.source_location_id) || 0;
        _alloc.locationLabel    = _moDetails.source_location_name || '—';
        _alloc.plannedQty       = parseFloat(_moDetails.planned_qty) || 0;
        _alloc.pendingSerials   = {};
        _alloc.pendingSerialSet = new Set();
        _alloc.pendingQtys      = {};

        _alloc.items = (_moDetails.material_items || []);
        _alloc.items.forEach(function(i) {
            if (i.stock_tracking_method === 'serial') { _alloc.pendingSerials[i.id] = []; }
        });

        document.getElementById('moAllocMoNumber').textContent  = _moDetails.mo_number || '—';
        document.getElementById('moAllocLocation').textContent  = _alloc.locationLabel;
        document.getElementById('moAllocPlannedQty').textContent = formatQty(_alloc.plannedQty);
        document.getElementById('moAllocNotes').value           = '';
        cleanFormInputFeedback(document.getElementById('moAllocationDrawer'));

        renderAllocRows();
        new bootstrap.Offcanvas(document.getElementById('moAllocationDrawer')).show();
    };

    // Serial picker button + chip remove
    document.getElementById('moAllocationDrawer').addEventListener('click', function(e) {

        var pickBtn = e.target.closest('.moalloc-pick-btn');
        if (pickBtn) {
            var miId     = parseInt(pickBtn.dataset.miId);
            var prodId   = parseInt(pickBtn.dataset.prodId);
            var prodName = pickBtn.dataset.prodName || '';
            var needed   = parseInt(pickBtn.dataset.needed) || 0;

            openSerialPicker({
                productId:      prodId,
                productName:    prodName,
                locationId:     _alloc.locationId,
                locationLabel:  _alloc.locationLabel,
                qty:            needed,
                allowPartial:   true,
                currentSerials: _alloc.pendingSerials[miId] || [],
                onConfirm: function(selected) {
                    (_alloc.pendingSerials[miId] || []).forEach(function(sn) { _alloc.pendingSerialSet.delete(sn); });
                    _alloc.pendingSerials[miId] = selected;
                    selected.forEach(function(sn) { _alloc.pendingSerialSet.add(sn); });
                    renderAllocRows();
                }
            });
        }

        var removeChip = e.target.closest('.moalloc-chip-remove');
        if (removeChip) {
            var chip = e.target.closest('.moalloc-chip');
            var miId = parseInt(chip.dataset.miId);
            var sn   = chip.dataset.sn;
            _alloc.pendingSerials[miId] = (_alloc.pendingSerials[miId] || []).filter(function(s) { return s !== sn; });
            _alloc.pendingSerialSet.delete(sn);
            renderAllocRows();
        }
    });

    // Save
    document.getElementById('moAllocSaveBtn').addEventListener('click', async function() {
        var drawer     = document.getElementById('moAllocationDrawer');
        var feedbackEl = drawer.querySelector('.form-glob-feedback');
        feedbackEl.innerHTML = '';

        var items = [];
        _alloc.items.forEach(function(item) {
            if (item.stock_tracking_method === 'serial') {
                var serials = _alloc.pendingSerials[item.id] || [];
                if (serials.length > 0) {
                    items.push({ material_item_id: item.id, serial_numbers: serials });
                }
            } else {
                var qty = parseFloat(_alloc.pendingQtys[item.id]) || 0;
                if (qty > 0) {
                    items.push({ material_item_id: item.id, qty: qty });
                }
            }
        });

        var payload = {
            notes: document.getElementById('moAllocNotes').value.trim(),
            items: items,
        };

        try {
            var response = await api.post('/manufacturing/orders/' + _alloc.moId + '/allocations', payload);
            notyf.success(response.data.message);
            bootstrap.Offcanvas.getInstance(drawer).hide();
            loadMoDetail();
        } catch(err) {
            handleApiError(err, drawer);
        }
    });

})();
</script>
@endpush
