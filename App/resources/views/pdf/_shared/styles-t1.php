<style>
/* margins set via mPDF constructor in Helpers_Pdf — no @page override */

.fw-bold {
    font-weight: bold;
}

.pt-7 {
    padding-top: 7px !important;
}

/* ── Base ── */
body {
    font-family: notosans, sans-serif;
    font-size: 7.5pt;
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
    padding-bottom: 8px;
}
.doc-header-logo img {
    width: 100%;
    max-height: 45px;
}
.doc-header-company {
    float: right;
    width: 50%;
    text-align: right;
    padding-bottom: 8px;
}
.company-name {
    display: block;
    font-size: 10pt;
    font-weight: 700;
    /*color: #111827;*/
    margin-bottom: 2px;
}
.company-meta {
    font-size: 7.5pt;
    color: #6b7280;
    line-height: 1.35;
}

/* ── Blue divider line ── */
.header-divider {
    border-top: 1px solid #6487E7;
    margin-bottom: 8px;
}

/* ── Info block ── */
.doc-info-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    margin-bottom: 10px;
}
.doc-info-table td {
    vertical-align: top;
    padding: 0px;
    width: 50%;
    font-size: 7.5pt;
    color: #374151;
    line-height: 1.35;
}
.doc-info-table .border-right {
    border-right: 1px solid #e5e7eb;
}

/* ── Document title ── */
.doc-title {
    font-size: 16pt;
    font-weight: 700;
    /*color: #6487E7;*/
    color: #111827;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 3px;
    line-height: 1.2;
}

/* ── Meta list ── */
.meta-label {
    color: #6b7280;
    font-size: 7.5pt;
    font-weight: 400;
    min-width: 80px;
}
.meta-val {
    font-size: 7.5pt;
    font-weight: 700;
    /*color: #111827;*/
}

/* ── Pickup badge ── */
.pickup-badge {
    font-size: 7.5pt;
    font-weight: 700;
    color: #6487E7;
    margin-top: 2px;
}

/* ── Section labels ── */
.info-col-label {
    font-size: 7pt;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    color: #6487E7;
    font-weight: 600;
    margin-bottom: 4px;
}
.info-col-name {
    font-size: 7.5pt;
    font-weight: 600;
    /*color: #111827;*/
    margin-bottom: 1px;
}

/* ── Line items table ── */
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
    padding: 4px 4px;
    font-size: 7pt;
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
    padding: 4px 4px;
    vertical-align: top;
    font-size: 7.5pt;
    color: #374151;
}
.items-table tbody td.text-right { text-align: right; }
.items-table tr td, .items-table tr th {
    border-bottom: 1px solid #e5e7eb;
}
.item-product {
    font-size: 7.5pt;
    font-weight: 600;
    /*color: #111827;*/
}
.item-desc {
    font-size: 7pt;
    color: #9ca3af;
    margin-top: 1px;
}

/* ── Bottom section ── */
.bottom-section {
    width: 100%;
    margin-top: 5px;
    overflow: hidden;
}
.bottom-section .totals-col {
    float: right;
    width: 240px;
}
.bottom-section .notes-col {
    margin-right: 255px;
}

/* ── Totals table ── */
.totals-table {
    width: 240px;
    border-collapse: collapse;
    margin-left: auto;
}
.totals-table td {
    padding: 2px 5px;
    font-size: 7.5pt;
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
    padding-top: 6px;
}
.notes-label {
    font-size: 7pt;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 2px;
}
.notes-body {
    font-size: 7pt;
    color: #4b5563;
    line-height: 1.5;
}

/* ── Terms & conditions ── */
.terms-section {
    margin-top: 10px;
    border-top: 1px solid #e5e7eb;
    padding-top: 5px;
}
.terms-label {
    font-size: 7.5pt;
    font-weight: bold;
    color: #111827;
    margin-bottom: 8px;
}
.terms-body {
    font-size: 7pt;
    color: #4b5563;
    line-height: 1.5;
}
.terms-body p { margin: 0 0 3px 0; }
.terms-body ul, .terms-body ol { padding-left: 16px; margin-top: 0; }
.terms-body h1, .terms-body h2, .terms-body h3, .terms-body h4 { font-size: 8pt; margin: 6px 0 3px 0; }
.terms-body table { border-collapse: collapse; }
.terms-body td, .terms-body th { border: 1px solid #e5e7eb; padding: 2px 5px; }

/* ── Declaration ── */
.declaration-signature .declaration-block {
    margin-top: 10px;
    border-top: 1px solid #e5e7eb;
    padding-top: 5px;
}
.declaration-signature .signature-block {
    margin-top: 10px;
    border-top: 1px solid #e5e7eb;
    padding-top: 5px;
}
.declaration-label {
    font-size: 7.5pt;
    font-weight: bold;
    color: #111827;
    margin-bottom: 8px;
}
.declaration-body {
    font-size: 7pt;
    color: #4b5563;
    line-height: 1.5;
}
.declaration-body p { margin: 0 0 3px 0; }
.declaration-body ul, .declaration-body ol { padding-left: 16px; }
.declaration-body h1, .declaration-body h2, .declaration-body h3, .declaration-body h4 { font-size: 8pt; margin: 6px 0 3px 0; }
.declaration-body table { border-collapse: collapse; }
.declaration-body td, .declaration-body th { border: 1px solid #e5e7eb; padding: 2px 5px; }

/* ── Signature block ── */
.signature-section {
    margin-top: 30px;
}
.signature-inner {
    width: 160px;
    margin-left: auto;
    text-align: center;
}
.signature-img {
    max-height: 45px;
    max-width: 160px;
}
.signature-line {
    border-top: 1pt solid #e5e7eb;
    padding-top: 3px;
    font-size: 7pt;
    color: #6b7280;
    margin-top: 4px;
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
    max-height: 45px;
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
    padding-bottom: 4px;
    border-bottom: 1px dashed #e5e7eb;
}

.t2-address-block thead td {
    padding-top: 8px;
}

/* ── Meta row (blue band) ── */
.t2-meta-row {
    width: 100%;
    border-collapse: collapse;
    margin-top: 5px;
    margin-bottom: 10px;
}
.t2-meta-row td {
    background: #6487E7;
    padding: 5px 12px;
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
    font-size: 6.5pt;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    min-width: auto;
    margin-bottom: 2px;
}
.t2-meta-row .meta-val {
    display: block;
    color: #ffffff;
    font-size: 7.5pt;
}
</style>
