<style>
/* ── Page ── */
@page {
    margin-left:   14mm;
    margin-right:  14mm;
    margin-top:    14mm;
    margin-bottom: 14mm;
}

/* ── Base ── */
body {
    font-family: notosans, sans-serif;
    font-size: 9pt;
    color: #374151;
    background: #ffffff;
}

/* ── Header: 2-col div layout (logo | company info) ── */
.doc-header {
    width: 100%;
    margin-bottom: 0;
    overflow: hidden;
}
.doc-header-logo {
    float: left;
    width: 50%;
    padding-bottom: 12px;
}
.doc-header-logo img {
    width: 100%;
    max-height: 60px;
}
.doc-header-company {
    float: right;
    width: 50%;
    text-align: right;
    padding-bottom: 12px;
}
.company-name {
    display: block;
    font-size: 13pt;
    font-weight: 700;
    color: #111827;
    margin-bottom: 6px;
}
.company-meta {
    font-size: 9pt;
    color: #6b7280;
    line-height: 1.6;
}

/* ── Blue divider line ── */
.header-divider {
    border-top: 1px solid #6487E7;
    margin-bottom: 12px;
}

/* ── 3-col info block ── */
.doc-info-table {
    width: 100%;
    border-collapse: collapse;    
    margin-bottom: 16px;
}
.doc-info-table td {
    vertical-align: top;
    padding: 14px 16px;
    width: 33.33%;    
    font-size: 9pt;
    color: #374151;
    line-height: 1.7;
}
.doc-info-table .border-right {
    border-right: 1px solid #e5e7eb;
}

/* ── Document title ── */
.doc-title {
    font-size: 14pt;
    font-weight: 700;
    color: #6487E7;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 10px;
}

/* ── Meta list ── */
.meta-label {
    color: #6b7280;
    font-size: 9pt;
    font-weight: 400;    
    min-width: 90px;    
}
.meta-val {
    font-size: 9pt;
    font-weight: 700;
    color: #111827;    
}

/* ── Pickup badge ── */
.pickup-badge {
    font-size: 9pt;
    font-weight: 700;
    color: #6487E7;
    margin-top: 4px;
}

/* ── Section labels (BILL TO, SHIP TO) ── */
.info-col-label {
    font-size: 7.5pt;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    color: #6487E7;
    font-weight: 600;
    margin-bottom: 6px;
}
.info-col-name {
    font-size: 9pt;
    font-weight: 600;
    color: #111827;
    margin-bottom: 2px;
}

/* ── Line items table ── */
.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
}
.items-table thead tr {
    background: #6487E7;
}
.items-table thead th {
    padding: 9px 11px;
    font-size: 8pt;
    font-weight: bold;
    color: #ffffff;
    text-align: left;
    white-space: nowrap;
    letter-spacing: 0.5px;
}
.items-table thead th.text-right { text-align: right; }
.items-table tbody tr {
    border-bottom: 1pt solid #e5e7eb;
}
.items-table tbody tr.even-row {
    background: #f9fafb;
}
.items-table tbody td {
    padding: 8px 11px;
    vertical-align: top;
    font-size: 9pt;
    color: #374151;
}
.items-table tbody td.text-right { text-align: right; }
.item-product {
    font-size: 9pt;
    font-weight: 600;
    color: #111827;
}
.item-desc {
    font-size: 7.5pt;
    color: #9ca3af;
    margin-top: 2px;
}

/* ── Bottom section: 2-col (notes | totals) ── */
.bottom-section {
    width: 100%;
    margin-top: 8px;
    overflow: hidden;
}
.bottom-section .totals-col {
    float: right;
    width: 260px;
}

/* ── Totals table ── */
.totals-table {
    width: 260px;
    border-collapse: collapse;
    margin-left: auto;
}
.totals-table td {
    padding: 6px 10px;
    font-size: 9pt;
}
.totals-table tr.grand-total-row {
    background: #6487E7;
}
.totals-table tr.grand-total-row td {
    font-weight: bold;
    color: #fff;
}

/* ── Notes section ── */
.notes-section {
    padding-top: 25px;
    padding-right: 85px;
}
.notes-label {
    font-size: 7.5pt;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 4px;
}
.notes-body {
    font-size: 9pt;
    color: #4b5563;
    line-height: 1.7;
}

/* ── Signature block ── */
.signature-section {
    margin-top: 50px;
}
.signature-inner {
    width: 180px;
    margin-left: auto;
    text-align: center;
}
.signature-img {
    max-height: 55px;
    max-width: 180px;
}
.signature-line {
    border-top: 1pt solid #e5e7eb;
    padding-top: 4px;
    font-size: 7.5pt;
    color: #6b7280;
    margin-top: 6px;
    text-align: center;
}
</style>
