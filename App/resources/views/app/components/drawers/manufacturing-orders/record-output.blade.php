<div class="offcanvas offcanvas-end" tabindex="-1" id="moRecordOutputDrawer" aria-labelledby="moRecordOutputDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width:42%;">

    <div class="offcanvas-header border-bottom">
        <h5 id="moRecordOutputDrawerTitle" class="offcanvas-title">Record Production</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">
        <div class="form-glob-feedback mb-3"></div>

        <div class="row g-3 mb-4 pb-3 border-bottom">
            <div class="col-4">
                <label class="form-label text-muted small mb-1">Finished Product</label>
                <div class="fw-semibold" id="moOutputProductName">—</div>
            </div>
            <div class="col-4">
                <label class="form-label text-muted small mb-1">MO Number</label>
                <div class="fw-semibold" id="moOutputMoNumber">—</div>
            </div>
            <div class="col-4">
                <label class="form-label text-muted small mb-1">Destination Warehouse</label>
                <div class="fw-semibold" id="moOutputDestLabel">—</div>
            </div>
            <div class="col-4">
                <label class="form-label text-muted small mb-1">Planned Qty</label>
                <div class="fw-semibold" id="moOutputPlannedQty">—</div>
            </div>
            <div class="col-4">
                <label class="form-label text-muted small mb-1">Produced So Far</label>
                <div class="fw-semibold" id="moOutputProducedQty">—</div>
            </div>
            <div class="col-4">
                <label class="form-label text-muted small mb-1">Remaining</label>
                <div class="fw-semibold text-primary" id="moOutputRemaining">—</div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Output Qty <span class="text-danger">*</span></label>
            <input type="number" name="output_qty" class="form-control" id="moOutputQty" min="0.0001" step="any" placeholder="Enter qty produced in this batch">
        </div>

        <div class="mb-3" id="moOutputSerialSection" style="display:none;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label mb-0">Finished Goods Serial Numbers <span class="text-danger">*</span></label>
                <div class="d-flex align-items-center gap-2">
                    <small>Count: <span id="moOutputSerialCount">0</span> | <a href="javascript:void(0);" id="moOutputSerialClearAll">Clear All</a></small>
                    <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary py-1" id="moOutputSerialGenerate">
                        <span class="icon-base bx bx-refresh icon-xs me-1"></span>Generate
                    </a>
                </div>
            </div>
            <input class="form-control" name="serial_numbers" id="moOutputSerialTagInput" placeholder="Scan or enter numbers separated by comma" />
            <div class="invalid-feedback" id="serial_numbers-feedback"></div>
        </div>

        <div class="mb-3" id="moOutputMaterialsSection">
            <div class="material_consumption-section-feedback form-section-feedback mb-2"></div>
            <label class="form-label fw-semibold">Material Consumption</label>
            <div class="border rounded px-3 pt-2 pb-1" id="moOutputMaterials"></div>
        </div>

        <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea class="form-control" id="moOutputNotes" rows="2" placeholder="Optional notes for this output"></textarea>
        </div>
    </div>

    <div class="offcanvas-footer border-top">
        <div class="d-flex gap-3">
            <button type="button" id="moOutputSaveBtn" class="btn btn-success btn-sm min-w-px-100">Record Production</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>

</div>

@push('scripts')
<script>
(function() {

    var _out = {
        moId:            0,
        plannedQty:      0,
        producedQty:     0,
        destWhId:       0,
        destWhLabel:       '',
        isSerialProduct: false,
        consumptionQtys:    {}, // { miId: float } — qty items actual used
        consumptionSerials: {}, // { miId: [serial_id, ...] } — selected serial IDs to consume
    };

    // ── Material consumption helpers ─────────────────────────────────────────

    function round4(n) { return Math.round(n * 10000) / 10000; }

    function renderMaterialConsumption() {
        var container = document.getElementById('moOutputMaterials');
        var outputQty = parseFloat(document.getElementById('moOutputQty').value) || 0;
        var items     = (_moDetails && _moDetails.material_items) || [];
        container.innerHTML = '';

        if (!items.length) {
            container.innerHTML = '<div class="text-muted small py-1">No components defined</div>';
            return;
        }

        items.forEach(function(item, itemIdx) {
            var miId      = item.id;
            var isSerial  = item.stock_tracking_method === 'serial';
            var plannedMi = parseFloat(item.planned_qty) || 0;
            var uom       = item.uom_code ? ' ' + item.uom_code : '';
            var isLast    = itemIdx === items.length - 1;

            var wrapper = document.createElement('div');
            wrapper.className = 'py-2' + (isLast ? '' : ' border-bottom');

            var headerHtml = '<div class="mb-2">'
                + '<span class="fw-semibold small">' + (item.product_name || '—') + '</span>'
                + '</div>';

            var bodyHtml = '';

            if (isSerial) {
                var pickedSerials = item.picked_serials || [];

                if (!outputQty) {
                    bodyHtml = '<p class="text-muted small fst-italic mb-0">Enter output qty above to see serial requirements</p>';
                } else if (!pickedSerials.length) {
                    bodyHtml = '<p class="text-warning small mb-0"><i class="bx bx-error-circle me-1"></i>No picked serials — allocate materials before recording production</p>';
                } else {
                    // neededCount derived directly from stored planned_qty (already BOM × MO qty)
                    // round4 before ceil to prevent float imprecision (e.g. 6.000000000000001 → 7)
                    var neededCount = (_out.plannedQty > 0)
                        ? Math.ceil(round4(outputQty / _out.plannedQty * plannedMi))
                        : plannedMi;

                    // Pre-select first neededCount serials FIFO on first render
                    if (!_out.consumptionSerials[miId]) {
                        _out.consumptionSerials[miId] = pickedSerials.slice(0, neededCount).map(function(s) { return s.serial_id; });
                    }
                    var selectedIds     = _out.consumptionSerials[miId];
                    var selectedSerials = pickedSerials.filter(function(s) { return selectedIds.indexOf(s.serial_id) !== -1; });
                    var availableSerials= pickedSerials.filter(function(s) { return selectedIds.indexOf(s.serial_id) === -1; });
                    var selCount    = selectedIds.length;

                    var consumedChips = selectedSerials.map(function(s) {
                        return '<span class="badge bg-label-primary d-inline-flex align-items-center gap-1 me-1 mb-1 moout-serial-chip"'
                            + ' data-mi-id="' + miId + '" data-serial-id="' + s.serial_id + '">'
                            + s.serial_number
                            + ' <i class="bx bx-x moout-chip-remove" style="cursor:pointer;font-size:1rem;line-height:1;" title="Remove from this batch"></i>'
                            + '</span>';
                    }).join('') || '<span class="text-muted small fst-italic">Select serial number</span>';

                    bodyHtml = '<div class="d-flex flex-wrap mb-2">' + consumedChips + '</div>';

                    if (availableSerials.length) {
                        var availableChips = availableSerials.map(function(s) {
                            return '<span class="badge bg-label-secondary moout-serial-available me-1 mb-1" style="cursor:pointer;" title="Click to add to this batch"'
                                + ' data-mi-id="' + miId + '" data-serial-id="' + s.serial_id + '">'
                                + s.serial_number
                                + '</span>';
                        }).join('');
                        bodyHtml += '<div class="text-muted mb-1" style="font-size:0.75rem;">Available to swap</div>'
                            + '<div class="d-flex flex-wrap">' + availableChips + '</div>';
                    }
                }
            } else {
                var proportion = (_out.plannedQty > 0 && outputQty > 0) ? round4((outputQty / _out.plannedQty) * plannedMi) : 0;
                var currentVal = _out.consumptionQtys[miId] !== undefined ? _out.consumptionQtys[miId] : proportion;
                bodyHtml = '<div class="d-flex align-items-center gap-2">'
                    + '<input type="number" min="0" step="any" class="form-control form-control-sm moout-qty-input" style="width:110px;"'
                    + ' data-mi-id="' + miId + '" value="' + currentVal + '" placeholder="0">'
                    + (item.uom_code ? '<span class="text-muted small">' + item.uom_code + '</span>' : '')
                    + '</div>';
            }

            wrapper.innerHTML = headerHtml + bodyHtml;
            container.appendChild(wrapper);
        });

        // Chip × — remove serial from consumed zone, re-render to show it in available pool
        container.querySelectorAll('.moout-chip-remove').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var chip     = this.closest('.moout-serial-chip');
                var miId     = parseInt(chip.dataset.miId);
                var serialId = parseInt(chip.dataset.serialId);
                var list     = _out.consumptionSerials[miId] || [];
                var idx      = list.indexOf(serialId);
                if (idx !== -1) list.splice(idx, 1);
                _out.consumptionSerials[miId] = list;
                renderMaterialConsumption();
            });
        });

        // Available chip — add serial to consumed zone
        container.querySelectorAll('.moout-serial-available').forEach(function(chip) {
            chip.addEventListener('click', function() {
                var miId     = parseInt(this.dataset.miId);
                var serialId = parseInt(this.dataset.serialId);
                var list     = _out.consumptionSerials[miId] || [];
                if (list.indexOf(serialId) === -1) {
                    list.push(serialId);
                    _out.consumptionSerials[miId] = list;
                    renderMaterialConsumption();
                }
            });
        });

        // Qty inputs
        container.querySelectorAll('.moout-qty-input').forEach(function(input) {
            input.addEventListener('change', function() {
                var miId = parseInt(this.dataset.miId);
                _out.consumptionQtys[miId] = parseFloat(this.value) || 0;
            });
        });
    }

    // ── Finished goods serial (Tagify) ───────────────────────────────────────

    var _outTagify = null;

    function initOutputTagify() {
        var input = document.getElementById('moOutputSerialTagInput');
        if (_outTagify) { _outTagify.destroy(); _outTagify = null; }
        input.value = '';
        _outTagify = new Tagify(input);
        _outTagify.on('add remove', updateSerialCounter);
    }

    function updateSerialCounter() {
        var count   = _outTagify ? _outTagify.value.length : 0;
        var countEl = document.getElementById('moOutputSerialCount');
        if (countEl) countEl.textContent = count;
    }

    // ── Drawer open ──────────────────────────────────────────────────────────

    window.openMoRecordOutputDrawer = function(moId) {
        _out.moId            = moId;
        _out.plannedQty      = parseFloat(_moDetails.planned_qty)  || 0;
        _out.producedQty     = parseFloat(_moDetails.produced_qty) || 0;
        _out.destWhId       = parseInt(_moDetails.destination_warehouse_id) || 0;
        _out.destWhLabel       = _moDetails.destination_warehouse_name || '—';
        _out.isSerialProduct = (_moDetails.product_stock_tracking_method === 'serial');

        var remaining = Math.max(0, _out.plannedQty - _out.producedQty);

        document.getElementById('moOutputProductName').textContent = _moDetails.product_name || '—';
        document.getElementById('moOutputMoNumber').textContent    = _moDetails.mo_number    || '—';
        document.getElementById('moOutputPlannedQty').textContent  = formatQty(_out.plannedQty);
        document.getElementById('moOutputProducedQty').textContent = formatQty(_out.producedQty);
        document.getElementById('moOutputRemaining').textContent   = formatQty(remaining);
        document.getElementById('moOutputDestLabel').textContent   = _out.destWhLabel;

        var qtyInput = document.getElementById('moOutputQty');
        qtyInput.value = '';
        qtyInput.max   = remaining;
        qtyInput.step  = _out.isSerialProduct ? '1'      : 'any';
        qtyInput.min   = _out.isSerialProduct ? '1'      : '0.0001';

        document.getElementById('moOutputNotes').value = '';

        // Reset material consumption state — serial selections initialized in renderMaterialConsumption
        _out.consumptionQtys    = {};
        _out.consumptionSerials = {};
        renderMaterialConsumption();

        var serialSection = document.getElementById('moOutputSerialSection');
        serialSection.style.display = _out.isSerialProduct ? '' : 'none';
        if (_out.isSerialProduct) initOutputTagify();
        updateSerialCounter();

        cleanFormInputFeedback(document.getElementById('moRecordOutputDrawer'));
        new bootstrap.Offcanvas(document.getElementById('moRecordOutputDrawer')).show();
    };

    // ── Serial generate / clear ──────────────────────────────────────────────

    document.getElementById('moOutputSerialGenerate').addEventListener('click', async function() {
        var productId = (_moDetails && _moDetails.product_id) ? _moDetails.product_id : 0;
        var required  = parseInt(document.getElementById('moOutputQty').value) || 0;
        var already   = _outTagify ? _outTagify.value.length : 0;
        var needed    = Math.max(0, required - already);
        if (!needed || !productId) return;
        try {
            var response = await api.post('/inv/sequence/generate/', { product_id: productId, count: needed });
            var serials  = response.data.data || [];
            if (_outTagify) serials.forEach(function(sn) { _outTagify.addTags([sn]); });
        } catch(err) {
            handleApiError(err, document.getElementById('moRecordOutputDrawer'));
        }
    });

    document.getElementById('moOutputSerialClearAll').addEventListener('click', function() {
        if (_outTagify) {
            _outTagify.removeAllTags();
            updateSerialCounter();
        }
    });

    document.getElementById('moOutputQty').addEventListener('input', function() {
        _out.consumptionQtys = {};
        _out.consumptionSerials = {};
        renderMaterialConsumption();
    });

    // ── Save ─────────────────────────────────────────────────────────────────

    document.getElementById('moOutputSaveBtn').addEventListener('click', async function() {
        var btn           = this;
        var drawer        = document.getElementById('moRecordOutputDrawer');
        var compSectionEl = drawer.querySelector('.material_consumption-section-feedback');

        // Clear all previous error state
        cleanFormInputFeedback(drawer);

        // Build per-material consumption
        var materialConsumption = [];
        ((_moDetails && _moDetails.material_items) || []).forEach(function(item) {
            var miId = item.id;
            if (item.stock_tracking_method === 'serial') {
                materialConsumption.push({
                    material_item_id: miId,
                    serial_ids: _out.consumptionSerials[miId] || [],
                });
            } else {
                var input = document.querySelector('.moout-qty-input[data-mi-id="' + miId + '"]');
                materialConsumption.push({
                    material_item_id: miId,
                    actual_qty: input ? (parseFloat(input.value) || 0) : 0,
                });
            }
        });

        // Soft confirmation if any allocated qty component has 0 entered
        var zeroQtyComponents = materialConsumption.filter(function(c) {
            
            if (c.serial_ids !== undefined) {return c.serial_ids.length > 0 ? false : true;}
            var item = ((_moDetails && _moDetails.material_items) || []).find(function(i) { return i.id === c.material_item_id; });
            return item && parseFloat(item.allocated_qty) > 0 && c.actual_qty === 0;

        }).map(function(c) {
            var item = ((_moDetails && _moDetails.material_items) || []).find(function(i) { return i.id === c.material_item_id; });
            return item ? (item.product_name || 'Unknown') : 'Unknown';
        });

        var outputQtyVal = parseFloat(document.getElementById('moOutputQty').value) || 0;
        if (outputQtyVal > 0 && zeroQtyComponents.length > 0) {
            var proceed = await new Promise(function(resolve) {
                showConfirmation(
                    'The following components have 0 consumption entered:<br><br><strong>' + zeroQtyComponents.join('<br>') + '</strong><br><br>Are you sure you want to proceed?',
                    'warning',
                    { text: 'Yes, Proceed', class: 'btn-warning',          callback: function() { resolve(true);  } },
                    { text: 'Cancel',       class: 'btn-label-secondary',  callback: function() { resolve(false); } }
                );
            });
            if (!proceed) return;
        }

        var payload = {
            output_qty:              parseFloat(document.getElementById('moOutputQty').value) || 0,
            destination_warehouse_id: _out.destWhId,
            notes:                   document.getElementById('moOutputNotes').value.trim(),
            material_consumption:    materialConsumption,
        };

        if (_out.isSerialProduct) {
            payload.serial_numbers = _outTagify ? _outTagify.value.map(function(t) { return t.value; }) : [];
        }

        setButtonLoading(btn, true);
        try {
            var response = await api.post('/manufacturing/orders/' + _out.moId + '/output', payload);
            notyf.success(response.data.message);
            bootstrap.Offcanvas.getInstance(drawer).hide();
            loadMoDetail();
        } catch(err) {
            // Always route field-level errors (output_qty, serial_numbers, material_consumption) to their elements
            handleApiError(err, drawer);

            // Additionally render shortage items above the components block if present
            var data          = err.response && err.response.data && err.response.data.data;
            var shortageItems = data && data.shortage_items;
            if (shortageItems && shortageItems.length > 0) {
                var itemsHtml = shortageItems.map(function(item) {
                    var suffix = item.is_serial ? ' serial(s)' : '';
                    return '<li class="mb-1"><strong>' + item.name + '</strong> &mdash; ' +
                        'Required: <strong>' + item.required + suffix + '</strong>, ' +
                        'Allocated: <strong>' + item.allocated + suffix + '</strong>, ' +
                        'Shortage: <strong class="text-danger">' + item.shortage + suffix + '</strong></li>';
                }).join('');
                compSectionEl.classList.add('has-feedback');
                compSectionEl.insertAdjacentHTML('beforeend',
                    '<div class="invalid-feedback d-block">' +
                    '<div class="mb-1 fw-semibold">Insufficient allocation — please allocate the missing quantities first:</div>' +
                    '<ul class="mb-0 ps-3">' + itemsHtml + '</ul>' +
                    '</div>'
                );
            }
        } finally {
            setButtonLoading(btn, false);
        }
    });

})();
</script>
@endpush
