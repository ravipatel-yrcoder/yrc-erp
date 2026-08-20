<?php $docTabPath = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'); ?>


<ul class="nav nav-tabs border-bottom mb-4">
    <li class="nav-item">
        <a class="nav-link {{ str_ends_with($docTabPath, '/quotation') ? 'active' : '' }}"
           href="/settings/documents/quotation/">Quotation</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ str_ends_with($docTabPath, '/sales-order') ? 'active' : '' }}"
           href="/settings/documents/sales-order/">Sales Order</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ str_ends_with($docTabPath, '/proforma-invoice') ? 'active' : '' }}"
           href="/settings/documents/proforma-invoice/">Proforma Invoice</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ str_ends_with($docTabPath, '/purchase-order') ? 'active' : '' }}"
           href="/settings/documents/purchase-order/">Purchase Order</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ str_ends_with($docTabPath, '/purchase-inquiry') ? 'active' : '' }}"
           href="/settings/documents/purchase-inquiry/">Purchase Inquiry</a>
    </li>
</ul>
