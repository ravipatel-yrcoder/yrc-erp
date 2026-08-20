<?php $settingsPath = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'); ?>

<div class="col-auto settings-sidebar w-px-250">
    <div class="settings-sidebar-sticky">

        <h4 class="fw-bold mb-1 pb-6">Settings</h4>

        <nav class="nav flex-column gap-1">

            <a href="/settings/general/"
               class="nav-link settings-nav-link d-flex align-items-center px-3 py-2 rounded {{ $settingsPath === '/settings/general' ? 'active bg-primary text-white' : 'text-body' }}">
                <i class="bx bx-building me-2"></i>General
            </a>

            <a href="/settings/inventory/"
               class="nav-link settings-nav-link d-flex align-items-center px-3 py-2 rounded {{ $settingsPath === '/settings/inventory' ? 'active bg-primary text-white' : 'text-body' }}">
                <i class="bx bx-box me-2"></i>Inventory
            </a>

            <a href="/settings/sales/"
               class="nav-link settings-nav-link d-flex align-items-center px-3 py-2 rounded {{ $settingsPath === '/settings/sales' ? 'active bg-primary text-white' : 'text-body' }}">
                <i class="bx bx-receipt me-2"></i>Sales
            </a>

            <!--
            Commented this as right now Vendor Quote Comparision feature is not fully finished and there is no other settings for purchasing
            <a href="/settings/purchasing/"
               class="nav-link settings-nav-link d-flex align-items-center px-3 py-2 rounded {{ $settingsPath === '/settings/purchasing' ? 'active bg-primary text-white' : 'text-body' }}">
                <i class="bx bx-cart me-2"></i>Purchasing
            </a>
            -->

            <a href="/settings/accounting/"
               class="nav-link settings-nav-link d-flex align-items-center px-3 py-2 rounded {{ $settingsPath === '/settings/accounting' ? 'active bg-primary text-white' : 'text-body' }}">
                <i class="bx bx-calculator me-2"></i>Accounting
            </a>

            <a href="/settings/documents/quotation/"
               class="nav-link settings-nav-link d-flex align-items-center px-3 py-2 rounded {{ str_starts_with($settingsPath, '/settings/documents') ? 'active bg-primary text-white' : 'text-body' }}">
                <i class="bx bx-file me-2"></i>Documents
            </a>

            <a href="/settings/doc-sequences/"
               class="nav-link settings-nav-link d-flex align-items-center px-3 py-2 rounded {{ $settingsPath === '/settings/doc-sequences' ? 'active bg-primary text-white' : 'text-body' }}">
                <i class="bx bx-sort-alt-2 me-2"></i>Doc Sequences
            </a>

            <a href="/settings/email/"
               class="nav-link settings-nav-link d-flex align-items-center px-3 py-2 rounded {{ str_starts_with($settingsPath, '/settings/email') ? 'active bg-primary text-white' : 'text-body' }}">
                <i class="bx bx-envelope me-2"></i>Email
            </a>

            <a href="/settings/subscription/"
               class="nav-link settings-nav-link d-flex align-items-center px-3 py-2 rounded {{ str_starts_with($settingsPath, '/settings/subscription') ? 'active bg-primary text-white' : 'text-body' }}">
                <i class="bx bx-credit-card me-2"></i>Subscription
            </a>

            <a href="/settings/billing/"
               class="nav-link settings-nav-link d-flex align-items-center px-3 py-2 rounded {{ str_starts_with($settingsPath, '/settings/billing') ? 'active bg-primary text-white' : 'text-body' }}">
                <i class="bx bx-receipt me-2"></i>Billing
            </a>

        </nav>
    </div>
</div>

<div class="col-auto settings-separator">
    <div class="vr h-100 mx-4 opacity-25"></div>
</div>
