<?php $settingsPath = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'); ?>

<div class="col-auto settings-sidebar w-px-250">
    <div class="settings-sidebar-sticky">

        <h4 class="fw-bold mb-1 pb-6">Settings</h4>

        <nav class="nav flex-column gap-1">

            <a href="/settings/general"
               class="nav-link settings-nav-link px-3 py-2 rounded {{ $settingsPath === '/settings/general' ? 'active bg-primary text-white' : 'text-body' }}">
                <i class="bx bx-building me-2"></i>General
            </a>

            <a href="/settings/accounting"
               class="nav-link settings-nav-link px-3 py-2 rounded {{ $settingsPath === '/settings/accounting' ? 'active bg-primary text-white' : 'text-body' }}">
                <i class="bx bx-calculator me-2"></i>Accounting
            </a>

            <a href="/settings/subscription"
               class="nav-link settings-nav-link px-3 py-2 rounded {{ str_starts_with($settingsPath, '/settings/subscription') ? 'active bg-primary text-white' : 'text-body' }}">
                <i class="bx bx-credit-card me-2"></i>Subscription
            </a>

            <a href="/settings/billing"
               class="nav-link settings-nav-link px-3 py-2 rounded {{ str_starts_with($settingsPath, '/settings/billing') ? 'active bg-primary text-white' : 'text-body' }}">
                <i class="bx bx-receipt me-2"></i>Billing
            </a>

        </nav>
    </div>
</div>

<div class="col-auto settings-separator">
    <div class="vr h-100 mx-4 opacity-25"></div>
</div>
