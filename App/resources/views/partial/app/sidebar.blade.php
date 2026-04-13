<!-- Menu -->
<aside id="layout-menu" class="layout-menu menu-vertical menu">
    <div class="app-brand demo">
    <a href="/dashboard" class="app-brand-link">
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
         <li class="menu-item active">
            <a href="/dashboard" class="menu-link">
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
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-stats me-2"></i>
                <div>CRM</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="/crm/pipeline" class="menu-link">
                    <div>Pipeline</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="/crm/leads/" class="menu-link">
                    <div>Leads</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Products -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-box me-2"></i>
                <div>Products</div>
            </a>
            <ul class="menu-sub">
                <!--
                <li class="menu-item">
                    <a href="/product-masters/" class="menu-link">
                    <div>Product Masters</div>
                    </a>
                </li>
                -->
                <li class="menu-item">
                    <a href="/products/" class="menu-link">
                    <div>Products</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="/product-categories/" class="menu-link">
                    <div>Categories</div>
                    </a>
                </li>
                <!--
                <li class="menu-item">
                    <a href="#" class="menu-link">
                    <div>Attributes</div>
                    </a>
                </li>
                -->
            </ul>
        </li>


        <!-- Sales -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-cart me-2"></i>
                <div>Sales</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="/customers/" class="menu-link">
                    <div>Customers</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="/quotations/" class="menu-link">
                    <div>Quotations</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="/sales-orders/" class="menu-link">
                    <div>Sales Orders</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="/sales-deliveries/" class="menu-link">
                    <div>Deliveries</div>
                    </a>
                </li>
            </ul>
        </li>
        
        
        <!-- Inventory -->
        <li class="menu-item">
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
                <li class="menu-item">
                    <a href="/inv/adjustments/" class="menu-link">
                    <div>Adjustments</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Purchasing -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-purchase-tag me-2"></i>
                <div>Purchasing</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="/vendors/" class="menu-link">
                    <div>Vendors</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="/purchase-orders/" class="menu-link">
                    <div>Purchase Orders</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="/purchase-receipts/" class="menu-link">
                    <div>Purchase Receives</div>
                    </a>
                </li>
            </ul>
        </li>
        
        <!-- Manage -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-cog me-2"></i>
                <div>Manage</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    
                    <li class="menu-item">
                        <a href="/company/locations/" class="menu-link">
                        <div>Locations</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <div>Inventory</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="/settings/inventory" class="menu-link">
                                    <div>General</div>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <div>CRM</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="/crm/stages" class="menu-link">
                                    <div>Stages</div>
                                </a>
                            </li>
                        </ul>
                    </li>
                </li>
                <li class="menu-item">
                    <a href="app-access-permission.html" class="menu-link">
                    <div data-i18n="Permission">Permission</div>
                    </a>
                </li>
            </ul>
        </li>    
    </ul>
</aside>

<div class="menu-mobile-toggler d-xl-none rounded-1">
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
    <i class="bx bx-menu icon-base"></i>
    <i class="bx bx-chevron-right icon-base"></i>
    </a>
</div>
<!-- / Menu -->