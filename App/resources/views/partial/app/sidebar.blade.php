<?php
    $currentPath = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

    // Returns 'active' if current path starts with $prefix (or exactly matches for strict mode)
    $menuItem = function(string $prefix, bool $exact = false) use ($currentPath): string {
        if ($exact) {
            return $currentPath === $prefix ? 'active' : '';
        }
        return str_starts_with($currentPath, $prefix) ? 'active' : '';
    };

    // Returns 'active open' if current path matches any of the given prefixes
    $menuGroup = function(array $prefixes) use ($currentPath): string {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($currentPath, $prefix)) return 'active open';
        }
        return '';
    };
?>
<!-- Menu -->
<aside id="layout-menu" class="layout-menu menu-vertical menu">
    <div class="app-brand demo">
    <a href="/dashboard/" class="app-brand-link">
        <span class="app-brand-logo demo">
            <img src="{{asset('/assets/img/logo.png')}}" alt="Zentraq" class="logo-with-text" style="max-width: 100%;" />
            <img src="{{asset('/assets/img/logo-icon.png')}}" alt="Zentraq" class="logo-icon-only" style="max-width: 100%;" />
        </span>        
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
        <i class="icon-base bx bx-chevron-left"></i>
    </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Dashboards -->
        <li class="menu-item {{ $menuItem('/dashboard', true) }}">
            <a href="/dashboard/" class="menu-link">
                <i class="menu-icon icon-base bx bx-home-smile"></i>
                <div>Dashboard</div>
            </a>
        </li>
        <!--
        <li class="menu-item active open">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-home-smile"></i>
                <div data-i18n="Dashboards">Dashboards</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item active">
                    <a href="/dashboard" class="menu-link">
                    <div>Overview</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                    <div>CRM</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                    <div>Inventory</div>
                    </a>
                </li>
            </ul>
        </li>
        -->

        <!-- CRM -->
        <li class="menu-item {{ $menuGroup(['/crm/']) }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-stats me-2"></i>
                <div>CRM</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ $menuItem('/crm/pipeline') }}">
                    <a href="/crm/pipeline/" class="menu-link">
                    <div>Pipeline</div>
                    </a>
                </li>
                <li class="menu-item {{ $menuItem('/crm/leads') }}">
                    <a href="/crm/leads/" class="menu-link">
                    <div>Leads</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Products -->
        <li class="menu-item {{ $menuGroup(['/products', '/product-categories']) }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-box me-2"></i>
                <div>Products</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ $menuItem('/products') }}">
                    <a href="/products/" class="menu-link">
                    <div>Products</div>
                    </a>
                </li>
                <li class="menu-item {{ $menuItem('/product-categories') }}">
                    <a href="/product-categories/" class="menu-link">
                    <div>Categories</div>
                    </a>
                </li>
            </ul>
        </li>


        <!-- Sales -->
        <li class="menu-item {{ $menuGroup(['/customers', '/quotations', '/sales-orders', '/sales-deliveries']) }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-cart me-2"></i>
                <div>Sales</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ $menuItem('/customers') }}">
                    <a href="/customers/" class="menu-link">
                    <div>Customers</div>
                    </a>
                </li>
                <li class="menu-item {{ $menuItem('/quotations') }}">
                    <a href="/quotations/" class="menu-link">
                    <div>Quotations</div>
                    </a>
                </li>
                <li class="menu-item {{ $menuItem('/sales-orders') }}">
                    <a href="/sales-orders/" class="menu-link">
                    <div>Sales Orders</div>
                    </a>
                </li>
                <li class="menu-item {{ $menuItem('/sales-deliveries') }}">
                    <a href="/sales-deliveries/" class="menu-link">
                    <div>Deliveries</div>
                    </a>
                </li>
            </ul>
        </li>
        
        
        <!-- Inventory -->
        <li class="menu-item {{ $menuGroup(['/inv/']) }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-buildings me-2"></i>
                <div>Inventory</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="#" class="menu-link">
                    <div>Transfers</div>
                    </a>
                </li>
                <li class="menu-item {{ $menuItem('/inv/adjustments') }}">
                    <a href="/inv/adjustments/" class="menu-link">
                    <div>Adjustments</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Purchasing -->
        <li class="menu-item {{ $menuGroup(['/vendors', '/purchase-orders', '/purchase-receipts']) }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-purchase-tag me-2"></i>
                <div>Purchasing</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ $menuItem('/vendors') }}">
                    <a href="/vendors/" class="menu-link">
                    <div>Vendors</div>
                    </a>
                </li>
                <li class="menu-item {{ $menuItem('/purchase-orders') }}">
                    <a href="/purchase-orders/" class="menu-link">
                    <div>Purchase Orders</div>
                    </a>
                </li>
                <li class="menu-item {{ $menuItem('/purchase-receipts') }}">
                    <a href="/purchase-receipts/" class="menu-link">
                    <div>Purchase Receives</div>
                    </a>
                </li>
            </ul>
        </li>
        
        <!-- Manage -->
        <li class="menu-item {{ $menuGroup(['/company/locations', '/settings/', '/crm/stages', '/crm/integrations']) }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-cog me-2"></i>
                <div>Manage</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ $menuItem('/company/locations') }}">
                    <a href="/company/locations/" class="menu-link">
                    <div>Locations</div>
                    </a>
                </li>
                <li class="menu-item {{ $menuGroup(['/settings/']) }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <div>Inventory</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item {{ $menuItem('/settings/inventory') }}">
                            <a href="/settings/inventory/" class="menu-link">
                                <div>General</div>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="menu-item {{ $menuGroup(['/crm/stages', '/crm/integrations']) }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <div>CRM</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item {{ $menuItem('/crm/stages') }}">
                            <a href="/crm/stages/" class="menu-link">
                                <div>Stages</div>
                            </a>
                        </li>
                        <li class="menu-item {{ $menuItem('/crm/integrations') }}">
                            <a href="/crm/integrations/" class="menu-link">
                                <div>Pull Leads</div>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </li>
    </ul>

    {{-- Sidebar footer: avatar dropdown + shortcuts + theme --}}
    <div class="sidebar-footer border-top px-2 py-2" style="margin-top:auto;">
        <div class="sidebar-footer-inner d-flex align-items-center gap-1">

            {{-- Avatar — dropdown with full user menu --}}
            <div class="dropdown flex-shrink-0">
                <a href="javascript:void(0);"
                   class="d-flex align-items-center text-decoration-none"
                   data-bs-toggle="dropdown" data-bs-auto-close="outside"
                   title="{{ auth()->user()->name }}">
                    <div class="avatar avatar-sm">
                        <span class="avatar-initial rounded-circle bg-label-primary" style="font-size:0.75rem;">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </span>
                    </div>
                </a>
                <ul class="dropdown-menu mb-1" style="min-width:200px;">
                    <li>
                        <div class="dropdown-item pe-none py-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar avatar-sm flex-shrink-0">
                                    <span class="avatar-initial rounded-circle bg-label-primary" style="font-size:0.75rem;">
                                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                                    </span>
                                </div>
                                <div class="min-width-0">
                                    <div class="fw-medium small lh-1 mb-1">{{ auth()->user()->name }}</div>
                                    <div class="text-muted text-truncate" style="font-size:0.7rem;">{{ auth()->user()->email }}</div>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item small d-flex align-items-center" href="#">
                            <i class="bx bx-user icon-md me-2"></i><span>My Profile</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item small d-flex align-items-center" href="#">
                            <i class="bx bx-cog icon-md me-2"></i><span>Settings</span>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item small d-flex align-items-center text-danger" href="javascript:void(0);" id="sidebarLogoutBtn">
                            <i class="bx bx-power-off icon-md me-2"></i><span>Log Out</span>
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Name + email — hidden in collapsed (non-hover) state --}}
            <div class="sidebar-footer-text flex-grow-1 min-width-0 mx-1">
                <div class="fw-medium small text-truncate lh-1 mb-1">{{ auth()->user()->name }}</div>
                <div class="text-muted text-truncate" style="font-size:0.7rem;">{{ auth()->user()->email }}</div>
            </div>

            {{-- Shortcuts --}}
            <div class="dropdown flex-shrink-0">
                <a href="javascript:void(0);" class="btn btn-sm btn-icon rounded-circle"
                   data-bs-toggle="dropdown" data-bs-auto-close="outside" title="Shortcuts">
                    <i class="bx bx-grid-alt" style="font-size:1rem;"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end p-0" style="min-width:220px;">
                    <div class="dropdown-header border-bottom py-2 px-3">
                        <h6 class="mb-0">Shortcuts</h6>
                    </div>
                    <div class="pb-2">
                        <div class="row g-2">
                            <div class="col-6">
                                <a href="/dashboard/" class="d-flex flex-column align-items-center text-center mt-2">
                                    <i class="bx bx-home-smile d-block fs-4 mb-1 text-primary"></i>
                                    <small>Dashboard</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="/crm/pipeline/" class="d-flex flex-column align-items-center text-center mt-2">
                                    <i class="bx bx-stats d-block fs-4 mb-1 text-success"></i>
                                    <small>Pipeline</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="/crm/leads/" class="d-flex flex-column align-items-center text-center mt-2">
                                    <i class="bx bx-user-check d-block fs-4 mb-1 text-info"></i>
                                    <small>Leads</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="/sales-orders/" class="d-flex flex-column align-items-center text-center mt-2">
                                    <i class="bx bx-cart d-block fs-4 mb-1 text-warning"></i>
                                    <small>Sales Orders</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="/purchase-orders/" class="d-flex flex-column align-items-center text-center mt-2">
                                    <i class="bx bx-package d-block fs-4 mb-1 text-danger"></i>
                                    <small>Purchase<br>Orders</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="/products/" class="d-flex flex-column align-items-center text-center mt-2">
                                    <i class="bx bx-box d-block fs-4 mb-1 text-secondary"></i>
                                    <small>Products</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Theme switcher --}}
            <!--
            <div class="dropdown flex-shrink-0">
                <a href="javascript:void(0);" class="btn btn-sm btn-icon rounded-circle"
                   data-bs-toggle="dropdown" title="Theme">
                    <i class="bx bx-sun theme-icon-active" style="font-size:1rem;"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><button class="dropdown-item small" data-bs-theme-value="light"><i class="bx bx-sun me-2"></i>Light</button></li>
                    <li><button class="dropdown-item small" data-bs-theme-value="dark"><i class="bx bx-moon me-2"></i>Dark</button></li>
                    <li><button class="dropdown-item small" data-bs-theme-value="system"><i class="bx bx-desktop me-2"></i>System</button></li>
                </ul>
            </div>
            -->

        </div>
    </div>

</aside>

<div class="menu-mobile-toggler d-xl-none rounded-1">
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
        <i class="bx bx-menu icon-base"></i>
        <i class="bx bx-chevron-right icon-base"></i>
    </a>
</div>
<!-- / Menu -->

@push('scripts')
<script>
document.getElementById('sidebarLogoutBtn').addEventListener('click', async function(e) {
    e.preventDefault();
    try {
        const response = await api.post('/auth/logout', {}, {headers: {'X-Client-Type': 'web'}});
        if (response.data.status === 'success') {
            window.location.href = '/login';
        } else {
            alert(response.data.message);
        }
    } catch (err) {
        if (err.response && err.response.data) {
            alert(err.response.data.message);
        } else {
            alert("Server unreachable. Please try again.");
        }
    }
});
</script>
@endpush