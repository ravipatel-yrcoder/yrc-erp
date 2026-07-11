<div class="modal fade stacked-modal" id="poCostHistoryModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="poCostHistoryTitle">Cost History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="poCostHistoryBody"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const openPoCostHistory = async function(productId, productName, vendorId = '', vendorName = '', currency = '') {

    const modal  = document.getElementById('poCostHistoryModal');
    const bodyEl = document.getElementById('poCostHistoryBody');

    document.getElementById('poCostHistoryTitle').textContent = 'Cost History' + (productName ? ' - ' + productName : '');
    bodyEl.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
    new bootstrap.Modal(modal).show();

    try {
        const params = { product_id: productId };
        if (vendorId) params.vendor_id = vendorId;

        const response = await api.get('/purchase/orders/product-cost-history', { params });
        const { history = [], vendor_comparison = [] } = response.data.data || {};

        const contextLine = vendorId ? `Recent purchase cost from <strong class="text-primary">${vendorName}</strong>` : 'Recent purchase cost across <strong class="text-primary">all vendors</strong>';

        let html = '';

        // ── Purchase history table ──
        if (history.length) {

            html += `<p class="text-muted mb-3">${contextLine}</p>`;

            html += '<div class="table-responsive"><table class="table table-bordered table-sm align-middle mb-0"><thead class="table-light"><tr>';
            html += '<th class="p-2">Vendor</th><th class="p-2">Date</th><th class="p-2">PO#</th><th class="p-2 text-end">Qty</th><th class="p-2 text-end">Unit Cost</th>';
            html += '</tr></thead><tbody>';
            history.forEach(r => {
                html += `<tr>
                    <td class="p-2">${r.vendor_name}</td>
                    <td class="p-2">${formatMySqlDate(r.order_date, window.sysDefaultConfig.dateFormat)}</td>
                    <td class="p-2 fw-medium">${r.po_number}</td>
                    <td class="p-2 text-end">${formatQty(r.ordered_qty)}</td>
                    <td class="p-2 text-end">${formatCurrency(r.unit_price, { currency })}</td>
                </tr>`;
            });
            html += '</tbody></table></div>';
        } else {

            const noHistoryFoundMsg = vendorId ? `No purchase history found from <strong class="text-primary">${vendorName}</strong>.` : 'No purchase history found.';
            html += `<p class="text-muted text-danger">${noHistoryFoundMsg}</p>`;
        }

        // ── Best Price on Record ──
        if (vendor_comparison.length > 0) {
            const lowestPrice = parseFloat(vendor_comparison[0].unit_price);
            const bestVendors = vendor_comparison.filter(r => parseFloat(r.unit_price) === lowestPrice);

            html += '<h6 class="text-uppercase text-muted fw-semibold mt-6 mb-0">Best Price on Record</h6>';
            html += '<p class="text-muted">Lowest unit cost from each vendor\'s last confirmed PO.</p>';

            bestVendors.forEach(r => {
                html += `<div class="d-flex align-items-center justify-content-between bg-success-subtle rounded px-3 py-2 mb-2">
                    <div>
                        <span class="fw-semibold">${r.vendor_name}</span>
                        <div class="text-muted small">${r.po_number} · ${formatMySqlDate(r.order_date, window.sysDefaultConfig.dateFormat)}</div>
                    </div>
                    <span class="fw-bold fs-5 text-success">${formatCurrency(r.unit_price, { currency })}</span>
                </div>`;
            });
        }

        bodyEl.innerHTML = html;

    } catch (_) {
        bodyEl.innerHTML = '<p class="text-danger small">Failed to load cost history.</p>';
    }
};
</script>
@endpush
