<?php
    $ctx = tenantContext();
    $currentPath = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

    $menuItem = function(string $prefix, bool $exact = false) use ($currentPath): string {
        if ($exact) return $currentPath === $prefix ? 'active' : '';
        return str_starts_with($currentPath, $prefix) ? 'active' : '';
    };

    $menuGroup = function(array $prefixes) use ($currentPath): string {
        foreach ($prefixes as $prefix) {
            if ($prefix && str_starts_with($currentPath, $prefix)) return 'active open';
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

        {{-- Dashboard --}}
        @if($ctx->canAccess('dashboard'))
        <li class="menu-item {{ $menuItem('/dashboard', true) }}">
            <a href="/dashboard/" class="menu-link">
                <i class="menu-icon icon-base bx bx-home-smile"></i>
                <div>Dashboard</div>
            </a>
        </li>
        @endif

        {{-- CRM --}}
        {{-- Point-4: Customers/Quotations appear under CRM only when Sales role-module is NOT active --}}
        @if($ctx->hasRoleModule('crm'))
        @php
            $salesRoleActive = $ctx->hasRoleModule('sales');
            $crmQuotations   = !$salesRoleActive && $ctx->canAccess('sales_orders');
            $crmCustomers    = !$salesRoleActive && $ctx->canAccess('customers');
            $crmRoutes       = array_values(array_filter([
                $ctx->canAccess('crm_leads') ? '/crm/pipeline'     : null,
                $ctx->canAccess('crm_leads') ? '/crm/leads'        : null,
                $crmQuotations               ? '/sales/quotations' : null,
                $crmCustomers                ? '/customers'        : null,
            ]));
        @endphp
        @if(!empty($crmRoutes))
        <li class="menu-item {{ $menuGroup($crmRoutes) }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-stats"></i>
                <div>CRM</div>
            </a>
            <ul class="menu-sub">
                @if($ctx->canAccess('crm_leads'))
                <li class="menu-item {{ $menuItem('/crm/pipeline') }}">
                    <a href="/crm/pipeline/" class="menu-link"><div>Pipeline</div></a>
                </li>
                <li class="menu-item {{ $menuItem('/crm/leads') }}">
                    <a href="/crm/leads/" class="menu-link"><div>Leads</div></a>
                </li>
                @endif
                @if($crmCustomers)
                <li class="menu-item {{ $menuItem('/customers') }}">
                    <a href="/customers/" class="menu-link"><div>Customers</div></a>
                </li>
                @endif
                @if($crmQuotations)
                <li class="menu-item {{ $menuItem('/sales/quotations') }}">
                    <a href="/sales/quotations/" class="menu-link"><div>Quotations</div></a>
                </li>
                @endif
            </ul>
        </li>
        @endif
        @endif

        {{-- Products --}}
        @php
            $productRoutes = array_values(array_filter([
                $ctx->canAccess('products')            ? '/products'            : null,
                $ctx->canAccess('product_categories')  ? '/products/categories' : null,
            ]));
        @endphp
        @if(!empty($productRoutes))
        <li class="menu-item {{ $menuGroup($productRoutes) }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-box"></i>
                <div>Products</div>
            </a>
            <ul class="menu-sub">
                @if($ctx->canAccess('products'))
                <li class="menu-item {{ $menuItem('/products', true) }}">
                    <a href="/products/" class="menu-link"><div>Products</div></a>
                </li>
                @endif
                @if($ctx->canAccess('product_categories'))
                <li class="menu-item {{ $menuItem('/products/categories') }}">
                    <a href="/products/categories/" class="menu-link"><div>Categories</div></a>
                </li>
                @endif
            </ul>
        </li>
        @endif

        {{-- Sales --}}
        {{-- Point-4: Customers/Sales Orders appear under Sales when Sales role-module IS active --}}
        @if($ctx->hasRoleModule('sales'))
        @php
            $salesRoutes = array_values(array_filter([
                $ctx->canAccess('customers')        ? '/customers'        : null,
                $ctx->canAccess('sales_orders')     ? '/sales/quotations' : null,
                $ctx->canAccess('sales_orders')     ? '/sales/orders'     : null,
                $ctx->canAccess('sales_deliveries') ? '/sales/deliveries' : null,
                $ctx->canAccess('sales_returns')    ? '/sales/returns'    : null,
            ]));
        @endphp
        @if(!empty($salesRoutes))
        <li class="menu-item {{ $menuGroup($salesRoutes) }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-cart"></i>
                <div>Sales</div>
            </a>
            <ul class="menu-sub">
                @if($ctx->canAccess('customers'))
                <li class="menu-item {{ $menuItem('/customers') }}">
                    <a href="/customers/" class="menu-link"><div>Customers</div></a>
                </li>
                @endif
                @if($ctx->canAccess('sales_orders'))
                <li class="menu-item {{ $menuItem('/sales/quotations') }}">
                    <a href="/sales/quotations/" class="menu-link"><div>Quotations</div></a>
                </li>
                <li class="menu-item {{ $menuItem('/sales/orders') }}">
                    <a href="/sales/orders/" class="menu-link"><div>Sales Orders</div></a>
                </li>
                @endif
                @if($ctx->canAccess('sales_deliveries'))
                <li class="menu-item {{ $menuItem('/sales/deliveries') }}">
                    <a href="/sales/deliveries/" class="menu-link"><div>Deliveries</div></a>
                </li>
                @endif
                @if($ctx->canAccess('sales_returns'))
                <li class="menu-item {{ $menuItem('/sales/returns') }}">
                    <a href="/sales/returns/" class="menu-link"><div>Returns</div></a>
                </li>
                @endif
            </ul>
        </li>
        @endif
        @endif

        {{-- Inventory --}}
        @if($ctx->hasRoleModule('inventory'))
        @php
            $invRoutes = array_values(array_filter([
                $ctx->canAccess('inventory_items')       ? '/inv/items'       : null,
                $ctx->canAccess('inventory_movements')   ? '/inv/movements'   : null,
                $ctx->canAccess('inventory_adjustments') ? '/inv/adjustments' : null,
            ]));
        @endphp
        @if(!empty($invRoutes))
        <li class="menu-item {{ $menuGroup($invRoutes) }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-cube"></i>
                <div>Inventory</div>
            </a>
            <ul class="menu-sub">
                @if($ctx->canAccess('inventory_items'))
                <li class="menu-item {{ $menuItem('/inv/items') }}">
                    <a href="/inv/items/" class="menu-link"><div>Items</div></a>
                </li>
                @endif
                @if($ctx->canAccess('inventory_movements'))
                <li class="menu-item {{ $menuItem('/inv/movements') }}">
                    <a href="/inv/movements/" class="menu-link"><div>Movements</div></a>
                </li>
                @endif
                @if($ctx->canAccess('inventory_adjustments'))
                <li class="menu-item {{ $menuItem('/inv/adjustments') }}">
                    <a href="/inv/adjustments/" class="menu-link"><div>Adjustments</div></a>
                </li>
                @endif
            </ul>
        </li>
        @endif
        @endif

        {{-- Purchasing --}}
        @if($ctx->hasRoleModule('purchasing'))
        @php
            $purchaseRoutes = array_values(array_filter([
                $ctx->canAccess('vendors')           ? '/vendors'           : null,
                $ctx->canAccess('purchase_orders')   ? '/purchase/orders'   : null,
                $ctx->canAccess('purchase_receipts') ? '/purchase/receipts' : null,
            ]));
        @endphp
        @if(!empty($purchaseRoutes))
        <li class="menu-item {{ $menuGroup($purchaseRoutes) }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-package"></i>
                <div>Purchasing</div>
            </a>
            <ul class="menu-sub">
                @if($ctx->canAccess('vendors'))
                <li class="menu-item {{ $menuItem('/vendors') }}">
                    <a href="/vendors/" class="menu-link"><div>Vendors</div></a>
                </li>
                @endif
                @if($ctx->canAccess('purchase_orders'))
                <li class="menu-item {{ $menuItem('/purchase/orders') }}">
                    <a href="/purchase/orders/" class="menu-link"><div>Purchase Orders</div></a>
                </li>
                @endif
                @if($ctx->canAccess('purchase_receipts'))
                <li class="menu-item {{ $menuItem('/purchase/receipts') }}">
                    <a href="/purchase/receipts/" class="menu-link"><div>Purchase Receives</div></a>
                </li>
                @endif
            </ul>
        </li>
        @endif
        @endif

        {{-- Manufacturing --}}
        @if($ctx->hasRoleModule('manufacturing'))
        @php
            $mfgRoutes = array_values(array_filter([
                $ctx->canAccess('manufacturing_boms')   ? '/manufacturing/boms'   : null,
                $ctx->canAccess('manufacturing_orders') ? '/manufacturing/orders' : null,
            ]));
        @endphp
        @if(!empty($mfgRoutes))
        <li class="menu-item {{ $menuGroup($mfgRoutes) }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-chip"></i>
                <div>Manufacturing</div>
            </a>
            <ul class="menu-sub">
                @if($ctx->canAccess('manufacturing_boms'))
                <li class="menu-item {{ $menuItem('/manufacturing/boms') }}">
                    <a href="/manufacturing/boms/" class="menu-link"><div>Bill of Materials</div></a>
                </li>
                @endif
                @if($ctx->canAccess('manufacturing_orders'))
                <li class="menu-item {{ $menuItem('/manufacturing/orders') }}">
                    <a href="/manufacturing/orders/" class="menu-link"><div>Manufacturing Orders</div></a>
                </li>
                @endif
            </ul>
        </li>
        @endif
        @endif

        {{-- Reports --}}
        @if($ctx->canAccess('reporting_profit_margin'))
        <li class="menu-item {{ $menuGroup(['/reports']) }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-bar-chart-alt-2"></i>
                <div>Reports</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ $menuItem('/reports/profit-margin') }}">
                    <a href="/reports/profit-margin/" class="menu-link"><div>Profit Margin</div></a>
                </li>
            </ul>
        </li>
        @endif

        {{-- Activities --}}
        @if($ctx->canAccess('activities'))
        <li class="menu-item {{ $menuItem('/activities') }}">
            <a href="/activities/" class="menu-link">
                <i class="menu-icon icon-base bx bx-calendar-check"></i>
                <div>Activities</div>
            </a>
        </li>
        @endif

        {{-- Manage --}}
        @php
            $hasLocations       = $ctx->canAccess('company_locations');
            $hasUsers           = $ctx->canAccess('company_users');
            $hasRoles           = $ctx->canAccess('company_roles_mgmt');
            $hasTeams           = $ctx->canAccess('company_teams');
            $crmModuleActive    = $ctx->hasRoleModule('crm');
            $hasStages          = $crmModuleActive && $ctx->canAccess('crm_stages');
            $hasIntegrations    = $crmModuleActive && $ctx->canAccess('crm_integrations');
            $hasVendorPricelist = $ctx->canAccess('vendor_pricelists');
            $hasManage          = $hasLocations || $hasUsers || $hasRoles || $hasTeams || $hasStages || $hasIntegrations || $hasVendorPricelist;
        @endphp
        @if($hasManage)
        <li class="menu-item {{ $menuGroup(['/company', '/crm/stages', '/crm/integrations', '/purchase/vendor-pricelist']) }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-cog"></i>
                <div>Manage</div>
            </a>
            <ul class="menu-sub">

                @if($hasLocations || $hasUsers || $hasRoles || $hasTeams)
                <li class="menu-item {{ $menuGroup(['/company']) }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <div>Company</div>
                    </a>
                    <ul class="menu-sub">
                        @if($hasLocations)
                        <li class="menu-item {{ $menuItem('/company/locations') }}">
                            <a href="/company/locations/" class="menu-link"><div>Locations</div></a>
                        </li>
                        @endif
                        @if($hasUsers)
                        <li class="menu-item {{ $menuItem('/company/users', true) }}">
                            <a href="/company/users/" class="menu-link"><div>Users</div></a>
                        </li>
                        @endif
                        @if($hasRoles)
                        <li class="menu-item {{ $menuItem('/company/users/roles') }}">
                            <a href="/company/users/roles/" class="menu-link"><div>User Roles</div></a>
                        </li>
                        @endif
                        @if($hasTeams)
                        <li class="menu-item {{ $menuItem('/company/teams') }}">
                            <a href="/company/teams/" class="menu-link"><div>Teams</div></a>
                        </li>
                        @endif
                    </ul>
                </li>
                @endif

                @if($hasVendorPricelist)
                <li class="menu-item {{ $menuGroup(['/purchase/vendor-pricelist']) }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <div>Purchasing</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item {{ $menuItem('/purchase/vendor-pricelist') }}">
                            <a href="/purchase/vendor-pricelist/" class="menu-link"><div>Vendor Pricelist</div></a>
                        </li>
                    </ul>
                </li>
                @endif

                @if($hasStages || $hasIntegrations)
                <li class="menu-item {{ $menuGroup(['/crm/stages', '/crm/integrations']) }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <div>CRM</div>
                    </a>
                    <ul class="menu-sub">
                        @if($hasStages)
                        <li class="menu-item {{ $menuItem('/crm/stages') }}">
                            <a href="/crm/stages/" class="menu-link"><div>Stages</div></a>
                        </li>
                        @endif
                        @if($hasIntegrations)
                        <li class="menu-item {{ $menuItem('/crm/integrations') }}">
                            <a href="/crm/integrations/" class="menu-link"><div>Pull Leads</div></a>
                        </li>
                        @endif
                    </ul>
                </li>
                @endif

            </ul>
        </li>
        @endif

    </ul>

    {{-- Sidebar footer: avatar dropdown + shortcuts + theme --}}
    <div class="sidebar-footer border-top px-2 py-2" style="margin-top:auto;">
        <div class="sidebar-footer-inner d-flex align-items-center gap-1">

            {{-- Avatar dropdown --}}
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
                        <a class="dropdown-item small d-flex align-items-center" href="javascript:void(0);" onclick="openMyProfileDrawer()">
                            <i class="bx bx-user icon-md me-2"></i><span>My Profile</span>
                        </a>
                    </li>
                    @if($ctx->isCompanyUser)
                    <li>
                        <a class="dropdown-item small d-flex align-items-center" href="/settings/general">
                            <i class="bx bx-cog icon-md me-2"></i><span>Settings</span>
                        </a>
                    </li>
                    @endif
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item small d-flex align-items-center text-danger" href="javascript:void(0);" id="sidebarLogoutBtn">
                            <i class="bx bx-power-off icon-md me-2"></i><span>Log Out</span>
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Name + email --}}
            <div class="sidebar-footer-text flex-grow-1 min-width-0 mx-1">
                <div class="fw-medium small text-truncate lh-1 mb-1">{{ auth()->user()->name }}</div>
                <div class="text-muted text-truncate" style="font-size:0.7rem;">{{ auth()->user()->email }}</div>
            </div>

            {{-- Notifications --}}
            <button type="button" class="btn btn-sm btn-icon rounded-circle flex-shrink-0" title="Notifications" disabled>
                <i class="bx bx-bell" style="font-size:1rem;"></i>
            </button>

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
