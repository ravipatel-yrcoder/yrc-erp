<div class="offcanvas offcanvas-end" tabindex="-1" id="moReturnMaterialsDrawer" aria-labelledby="moReturnMaterialsDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width:58%;">

    <div class="offcanvas-header border-bottom">
        <h5 id="moReturnMaterialsDrawerTitle" class="offcanvas-title">Return Materials to Warehouse</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">        

        <div class="row g-3 mb-4 pb-3 border-bottom">
            <div class="col-md-4">
                <label class="form-label text-muted small mb-1">MO Number</label>
                <div class="fw-semibold" id="moReturnMoNumber">—</div>
            </div>
            <div class="col-md-4">
                <label class="form-label text-muted small mb-1">Source Warehouse</label>
                <div class="fw-semibold" id="moReturnLocation">—</div>
            </div>
            <div class="col-md-4">
                <label class="form-label text-muted small mb-1">MO Status</label>
                <div id="moReturnStatus">—</div>
            </div>
        </div>

        <div class="form-glob-feedback mb-3"></div>

        <div class="mb-4">
            <h6 class="text-uppercase text-muted fw-semibold mb-2" style="font-size:0.72rem;letter-spacing:.05em;">Components to Return</h6>
            <div class="table-responsive border rounded">
                <table class="table table-bordered table-sm align-middle m-0" id="moReturnItemsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="p-2">ITEM</th>
                            <th class="p-2 text-end" style="width:110px;">ALLOCATED</th>
                            <th class="p-2 text-end" style="width:110px;">RETURNED</th>
                            <th class="p-2">RETURN</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea class="form-control" id="moReturnNotes" rows="2" placeholder="Optional notes for this return"></textarea>
        </div>
    </div>

    <div class="offcanvas-footer border-top">
        <div class="d-flex gap-3">
            <button type="button" id="moReturnSaveBtn" class="btn btn-primary btn-sm min-w-px-100">Confirm Return</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>

</div>

<!-- Serial picker modal for return -->
<div class="modal fade" id="moReturnSerialPickerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-scrollable" style="max-width:520px;">
        <div class="modal-content">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-semibold">Select Serials to Return</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="px-3 pt-3 pb-2 border-bottom bg-light">
                    <div class="small text-muted mb-1">Prod: <span class="fw-semibold text-body" id="moRetSerProdName"></span></div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-muted">Allocated serials for this order</span>
                        <span class="small fw-semibold" id="moRetSerCountLabel">0 selected</span>
                    </div>
                </div>
                <div class="px-3 py-2 border-bottom">
                    <input type="text" class="form-control form-control-sm" id="moRetSerSearch" placeholder="Search serial numbers...">
                </div>
                <div id="moRetSerList" style="max-height:360px;overflow-y:auto;"></div>
            </div>
            <div class="modal-footer border-top py-2">
                <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="moRetSerConfirmBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {

    var _ret = {
        moId:           0,
        items:          [],
        pendingSerials: {},   // { material_item_id: Set of serial_ids selected for return }
        pendingQtys:    {},   // { material_item_id: float } for non-serial
    };

    var _retSerPicker = {
        miId:        0,
        serials:     [],       // alloc_serials for current item
        tempSelected: null,    // Set of serial_ids
    };

    // ── Serial picker modal ──────────────────────────────────────────────────

    var openReturnSerialPicker = function(miId) {
        var item = _ret.items.find(function(i) { return i.id === miId; });
        if (!item) return;

        _retSerPicker.miId         = miId;
        _retSerPicker.serials      = item.alloc_serials || [];
        _retSerPicker.tempSelected = new Set(_ret.pendingSerials[miId] || new Set());

        document.getElementById('moRetSerProdName').textContent = item.product_name || '—';
        document.getElementById('moRetSerSearch').value = '';
        renderReturnSerialList('');
        updateReturnSerialCount();

        new bootstrap.Modal(document.getElementById('moReturnSerialPickerModal')).show();
    };

    var renderReturnSerialList = function(search) {
        var list = document.getElementById('moRetSerList');
        var lc   = search.toLowerCase();

        var available = _retSerPicker.serials.filter(function(s) { return !s.already_returned; });
        var returned  = _retSerPicker.serials.filter(function(s) { return s.already_returned; });
        var reserved  = available.filter(function(s) { return s.status !== 'consumed'; });
        var consumed  = available.filter(function(s) { return s.status === 'consumed'; });

        // Reserved first, then consumed, then already-returned at the bottom
        var all = reserved.concat(consumed).concat(returned);
        var filtered = lc ? all.filter(function(s) { return s.serial_number.toLowerCase().indexOf(lc) !== -1; }) : all;

        if (!filtered.length) {
            list.innerHTML = '<div class="text-center text-muted small p-4">No serials found</div>';
            return;
        }

        var html = '';
        filtered.forEach(function(s) {
            var isDisabled = s.already_returned;
            var isChecked  = _retSerPicker.tempSelected.has(s.serial_id);

            var labelHtml = '<span class="font-monospace">' + s.serial_number + '</span>';
            if (s.status === 'consumed' && !s.already_returned) {
                labelHtml += ' <span class="badge bg-label-danger ms-1" style="font-size:0.68em;">Consumed</span>';
            }
            if (s.already_returned) {
                labelHtml += ' <span class="badge bg-label-secondary ms-1" style="font-size:0.68em;">Already Returned</span>';
            }

            html += '<div class="d-flex align-items-center px-3 py-2 border-bottom' + (isDisabled ? ' bg-light' : '') + '">'
                + '<input type="checkbox" class="form-check-input moret-ser-check me-2 flex-shrink-0"'
                + (isChecked  ? ' checked'  : '')
                + (isDisabled ? ' disabled' : '')
                + ' data-sid="' + s.serial_id + '" style="cursor:' + (isDisabled ? 'not-allowed' : 'pointer') + ';">'
                + '<label class="small mb-0 w-100' + (isDisabled ? ' text-muted' : '') + '" style="cursor:' + (isDisabled ? 'not-allowed' : 'pointer') + ';">'
                + labelHtml + '</label>'
                + '</div>';
        });

        list.innerHTML = html;

        list.querySelectorAll('.moret-ser-check').forEach(function(cb) {
            cb.addEventListener('change', function() {
                var sid = parseInt(this.dataset.sid);
                if (this.checked) {
                    _retSerPicker.tempSelected.add(sid);
                } else {
                    _retSerPicker.tempSelected.delete(sid);
                }
                updateReturnSerialCount();
            });
        });
    };

    var updateReturnSerialCount = function() {
        var total = _retSerPicker.serials.filter(function(s) { return !s.already_returned; }).length;
        document.getElementById('moRetSerCountLabel').textContent = _retSerPicker.tempSelected.size + ' / ' + total + ' selected';
    };

    document.getElementById('moRetSerSearch').addEventListener('input', function() {
        renderReturnSerialList(this.value.trim());
    });

    document.getElementById('moRetSerConfirmBtn').addEventListener('click', function() {
        _ret.pendingSerials[_retSerPicker.miId] = new Set(_retSerPicker.tempSelected);
        bootstrap.Modal.getInstance(document.getElementById('moReturnSerialPickerModal')).hide();
        renderReturnRows();
    });

    // ── Table rows ───────────────────────────────────────────────────────────

    var renderReturnRows = function() {
        var tbody = document.querySelector('#moReturnItemsTable tbody');
        tbody.innerHTML = '';

        var returnableItems = _ret.items.filter(function(i) {
            return (parseFloat(i.allocated_qty) || 0) > (parseFloat(i.total_returned) || 0);
        });

        if (!returnableItems.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted p-3">No returnable materials</td></tr>';
            return;
        }

        returnableItems.forEach(function(item) {
            var isSerial   = item.stock_tracking_method === 'serial';
            var allocated  = parseFloat(item.allocated_qty) || 0;
            var alreadyRet = parseFloat(item.total_returned) || 0;
            var uom        = item.uom_code ? ' ' + item.uom_code : '';

            var allocDisplay = isSerial
                ? allocated + ' serial' + (allocated !== 1 ? 's' : '')
                : formatQty(allocated) + uom;
            var retDisplay = alreadyRet <= 0 ? '—'
                : (isSerial ? alreadyRet + ' serial' + (alreadyRet !== 1 ? 's' : '') : formatQty(alreadyRet) + uom);

            var assignCell = '';
            if (isSerial) {
                var selected      = _ret.pendingSerials[item.id] || new Set();
                var selectedCount = selected.size;
                var totalReturnable = (item.alloc_serials || []).filter(function(s) { return !s.already_returned; }).length;
                var badgeClass    = 'bg-label-secondary';

                var badge = '<span class="badge ' + badgeClass + ' ms-1">' + selectedCount + ' / ' + totalReturnable + '</span>';

                var pickBtn = '<a href="javascript:void(0);" class="text-primary small d-inline-flex align-items-center gap-1 moret-pick-btn"'
                    + ' data-mi-id="' + item.id + '">'
                    + '<i class="bx bx-barcode fs-6"></i> Select Serials ' + badge
                    + '</a>';

                var chipsHtml = '';
                Array.from(selected).forEach(function(sid) {
                    var s = (item.alloc_serials || []).find(function(x) { return x.serial_id === sid; });
                    var label = s ? s.serial_number : sid;
                    var consumed = s && s.status === 'consumed';
                    chipsHtml += '<span class="badge bg-label-info me-1 mb-1 moret-chip-selected" style="font-size:0.78em;" data-mi-id="' + item.id + '" data-sid="' + sid + '">'
                        + label
                        + (consumed ? ' <span class="opacity-75">· C</span>' : '')
                        + ' <i class="bx bx-x ms-1 cursor-pointer moret-chip-remove"></i></span>';
                });

                assignCell = pickBtn + (chipsHtml ? '<div class="mt-1">' + chipsHtml + '</div>' : '');

            } else {
                var currentVal = _ret.pendingQtys[item.id] !== undefined ? _ret.pendingQtys[item.id] : '';
                assignCell = '<input type="number" min="0" step="any"'
                    + ' class="form-control form-control-sm moret-qty-input" style="width:100px;"'
                    + ' data-mi-id="' + item.id + '" value="' + currentVal + '" placeholder="0">';
            }

            var row = '<tr>'
                + '<td class="p-2"><div class="fw-semibold">' + (item.product_name || '—') + '</div></td>'
                + '<td class="p-2 text-end text-muted small">' + allocDisplay + '</td>'
                + '<td class="p-2 text-end text-muted small">' + retDisplay + '</td>'
                + '<td class="p-2">' + assignCell + '</td>'
                + '</tr>';
            tbody.insertAdjacentHTML('beforeend', row);
        });

        // Bind qty inputs — server validates max
        document.querySelectorAll('.moret-qty-input').forEach(function(input) {
            input.addEventListener('change', function() {
                var miId = parseInt(this.dataset.miId);
                var val  = parseFloat(this.value) || 0;
                if (val < 0) { val = 0; this.value = ''; }
                _ret.pendingQtys[miId] = val > 0 ? val : undefined;
            });
        });
    };

    // ── Drawer open ──────────────────────────────────────────────────────────

    window.openMoReturnMaterialsDrawer = function(moId) {
        _ret.moId           = moId;
        _ret.pendingSerials = {};
        _ret.pendingQtys    = {};

        var items = (_moDetails.material_items || []).filter(function(i) {
            return (parseFloat(i.allocated_qty) || 0) > (parseFloat(i.total_returned) || 0);
        });

        // Collect already-returned serial numbers
        var returnedSerialIds = new Set();
        (_moDetails.returns || []).forEach(function(ret) {
            (ret.items || []).forEach(function(ri) {
                (ri.serials || []).forEach(function(sn) {
                    returnedSerialIds.add(sn);
                });
            });
        });

        // Collect allocated serials per material item
        var allocSerialsByItem = {};
        (_moDetails.allocations || []).forEach(function(alloc) {
            (alloc.items || []).forEach(function(ai) {
                (ai.serials || []).forEach(function(s) {
                    var miId = ai.material_item_id;
                    if (!allocSerialsByItem[miId]) allocSerialsByItem[miId] = [];
                    allocSerialsByItem[miId].push({
                        serial_id:        s.serial_id,
                        serial_number:    s.serial_number,
                        status:           s.status || 'reserved',
                        already_returned: returnedSerialIds.has(s.serial_number),
                    });
                });
            });
        });

        _ret.items = items.map(function(i) {
            return Object.assign({}, i, { alloc_serials: allocSerialsByItem[i.id] || [] });
        });

        _ret.items.forEach(function(i) {
            if (i.stock_tracking_method === 'serial') {
                _ret.pendingSerials[i.id] = new Set();
            }
        });

        var statusMap = { confirmed: 'Confirmed', in_production: 'In Production', completed: 'Completed' };
        document.getElementById('moReturnMoNumber').textContent = _moDetails.mo_number || '—';
        document.getElementById('moReturnLocation').textContent  = _moDetails.source_location_name || '—';
        document.getElementById('moReturnStatus').innerHTML      = '<span class="badge bg-label-primary">' + (statusMap[_moDetails.status] || _moDetails.status) + '</span>';
        document.getElementById('moReturnNotes').value           = '';
        cleanFormInputFeedback(document.getElementById('moReturnMaterialsDrawer'));

        renderReturnRows();
        new bootstrap.Offcanvas(document.getElementById('moReturnMaterialsDrawer')).show();
    };

    // ── Drawer click delegation ──────────────────────────────────────────────

    document.getElementById('moReturnMaterialsDrawer').addEventListener('click', function(e) {

        var pickBtn = e.target.closest('.moret-pick-btn');
        if (pickBtn) {
            openReturnSerialPicker(parseInt(pickBtn.dataset.miId));
            return;
        }

        var removeBtn = e.target.closest('.moret-chip-remove');
        if (removeBtn) {
            var chip  = e.target.closest('.moret-chip-selected');
            var miId  = parseInt(chip.dataset.miId);
            var sid   = parseInt(chip.dataset.sid);
            if (_ret.pendingSerials[miId]) {
                _ret.pendingSerials[miId].delete(sid);
            }
            renderReturnRows();
        }
    });

    // ── Save ─────────────────────────────────────────────────────────────────

    document.getElementById('moReturnSaveBtn').addEventListener('click', async function() {
        var drawer     = document.getElementById('moReturnMaterialsDrawer');
        var feedbackEl = drawer.querySelector('.form-glob-feedback');
        feedbackEl.innerHTML = '';

        var items = [];
        _ret.items.forEach(function(item) {
            if (item.stock_tracking_method === 'serial') {
                var selected = Array.from(_ret.pendingSerials[item.id] || new Set());
                if (selected.length > 0) {
                    items.push({ material_item_id: item.id, serial_ids: selected });
                }
            } else {
                var input = document.querySelector('.moret-qty-input[data-mi-id="' + item.id + '"]');
                var qty   = input ? (parseFloat(input.value) || 0) : (parseFloat(_ret.pendingQtys[item.id]) || 0);
                if (qty > 0) {
                    items.push({ material_item_id: item.id, returned_qty: qty });
                }
            }
        });

        var payload = {
            notes: document.getElementById('moReturnNotes').value.trim(),
            items: items,
        };

        try {
            var response = await api.post('/manufacturing/orders/' + _ret.moId + '/returns', payload);
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
