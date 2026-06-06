<style>
/* ── Template 2: header column layout (layout only — no typography overrides) ── */
.doc-title {
    font-size: 32px;
}
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
    max-height: 60px;
}
.t2-header-right {
    float: right;
    width: 50%;
    text-align: right;
}

/* ── Template 2: Address block — info-col-label gets accent header bar ── */
.doc-info-table {
    margin-top: 25px;
    margin-bottom: 25px;
}

.doc-info-table td {
    padding: 0px;
    width: 50%;
}

.t2-address-block thead th.bg {
    background: #6487E7;
    padding: 2px 10px;
    color: #fff;
}

.t2-address-block thead td {
    padding-top: 10px;
}


/* ── Template 2: Meta row ── */
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
/* Override shared classes for white-on-blue context */
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

.items-table {
    border: 1px solid #e5e7eb;    
}

.items-table tr td, .items-table tr th {
    border-bottom: 1px solid #e5e7eb;
}
</style>
