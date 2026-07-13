<style>
/* ── Page ── */
@page {
    margin-left:   14mm;
    margin-right:  14mm;
    margin-top:    14mm;
    margin-bottom: 14mm;
}

.fw-bold {
    font-weight: bold;
}

.pt-7 {
    padding-top: 7px !important;
}

/* ── Base ── */
body {
    font-family: notosans, sans-serif;
    font-size: 9pt;
    color: #374151;
    background: #ffffff;
}

/* ── Header ── */
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
    max-height: 50px;
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
    /*color: #111827;*/
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

/* ── Info block (merged: margin-top from t2, margin-bottom from t2, padding/width per-td from t2) ── */
.doc-info-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 25px;
    margin-bottom: 25px;
}
.doc-info-table td {
    vertical-align: top;
    padding: 0px;
    width: 50%;
    font-size: 9pt;
    color: #374151;
    line-height: 1.7;
}
.doc-info-table .border-right {
    border-right: 1px solid #e5e7eb;
}

/* ── Document title (merged: font-size from t2, all other properties from base) ── */
.doc-title {
    font-size: 32px;
    font-weight: 700;
    /*color: #6487E7;*/
    color: #111827;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 10px;
    line-height: 2.5rem;
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
    /*color: #111827;*/
}

/* ── Pickup badge ── */
.pickup-badge {
    font-size: 9pt;
    font-weight: 700;
    color: #6487E7;
    margin-top: 4px;
}

/* ── Section labels ── */
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
    /*color: #111827;*/
    margin-bottom: 2px;
}

/* ── Line items table (merged: border added from t2) ── */
.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
    border: 1px solid #e5e7eb;
}
.items-table thead tr {
    /*background: #6487E7;*/
    background: #F9FAFB;
}
.items-table thead th {
    padding: 9px 11px;
    font-size: 8pt;
    font-weight: bold;
    /*color: #ffffff;*/
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
.items-table tr td, .items-table tr th {
    border-bottom: 1px solid #e5e7eb;
}
.item-product {
    font-size: 9pt;
    font-weight: 600;
    /*color: #111827;*/
}
.item-desc {
    font-size: 7.5pt;
    color: #9ca3af;
    margin-top: 2px;
}

/* ── Bottom section ── */
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
    /*background: #6487E7;*/
    background: #F9FAFB;
}
.totals-table tr.grand-total-row td {
    font-weight: bold;
    /*color: #fff;*/
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

/* ── Split header columns ── */
.t2-header-left {
    float: left;
    width: 50%;
}
.t2-header-left .doc-header-logo {
    width: 100%;
    text-align: left;
}
.t2-header-left .doc-header-logo img {
    width: 100%;
    max-height: 50px;
}
.t2-header-right {
    float: right;
    width: 50%;
    text-align: right;
}

/* ── Address block header bar ── */
/*.t2-address-block thead th.bg {
    background: #6487E7;
    padding: 2px 10px;
    color: #fff;
}*/

.t2-address-block thead th.border-bottom {
    padding-left: 0px;
    padding-bottom: 5px;
    border-bottom: 1px dashed #e5e7eb;
}

.t2-address-block thead td {
    padding-top: 10px;
}

/* ── Meta row (blue band) ── */
.t2-meta-row {
    width: 100%;
    border-collapse: collapse;
    margin-top: 8px;
    margin-bottom: 16px;
}
.t2-meta-row td {
    background: #6487E7;
    padding: 7px 16px;
    width: 25%;
    vertical-align: top;
    border-right: 1pt solid rgba(255,255,255,0.25);
}
.t2-meta-row td:last-child {
    border-right: none;
}
.t2-meta-row .meta-label {
    display: block;
    color: rgba(255,255,255,0.85);
    font-size: 7pt;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    min-width: auto;
    margin-bottom: 3px;
}
.t2-meta-row .meta-val {
    display: block;
    color: #ffffff;
    font-size: 8.5pt;
}
</style>

