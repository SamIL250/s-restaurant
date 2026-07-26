<?php
    $current_page = basename($_SERVER['PHP_SELF']);
    $nav_role = currentUserRole();

    $showNav = static function (string $section) use ($nav_role): bool {
        return role_can_access_section($nav_role, $section);
    };
?>

<nav class="navbar navbar-vertical navbar-expand-lg" style="display:none;">
    <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
        <!-- scrollbar removed-->
        <div class="navbar-vertical-content">
            <ul class="navbar-nav flex-column" id="navbarVerticalNav">
                <li class="nav-item">
                    <!-- parent pages-->
                    <div class="nav-item-wrapper"><a class="nav-link dropdown-indicator label-1" href="#nv-home" role="button" data-bs-toggle="collapse" aria-expanded="true" aria-controls="nv-home">
                            <div class="d-flex align-items-center">
                                <div class="dropdown-indicator-icon-wrapper"><span class="fas fa-caret-right dropdown-indicator-icon"></span></div><span class="nav-link-icon"><span data-feather="pie-chart"></span></span><span class="nav-link-text">Dashboard</span>
                            </div>
                        </a>
                        <div class="parent-wrapper label-1">
                            <ul class="nav collapse parent show" data-bs-parent="#navbarVerticalCollapse" id="nv-home">
                                <li class="collapsed-nav-item-title d-none">Dashboard</li>
                                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'index.php' ? "active" : ""  ?>" href="index">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">Overview</span></div>
                                    </a><!-- more inner pages-->
                                </li>
                            </ul>
                        </div>
                    </div>
                </li>
                <li class="nav-item">
                    <!-- label-->
                    <?php if ($showNav('inventory') || $showNav('menu') || $showNav('reservations') || $showNav('orders') || $showNav('purchase')): ?>
                    <p class="navbar-vertical-label">Restaurant Management</p>
                    <hr class="navbar-vertical-line" />
                    <?php endif; ?>
                    
                    <!-- Inventory Management Section -->
                    <?php if ($showNav('inventory')): ?>
                    <div class="nav-item-wrapper"><a class="nav-link dropdown-indicator label-1" href="#nv-inventory" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="nv-inventory">
                            <div class="d-flex align-items-center">
                                <div class="dropdown-indicator-icon-wrapper"><span class="fas fa-caret-right dropdown-indicator-icon"></span></div><span class="nav-link-icon"><span data-feather="package"></span></span><span class="nav-link-text">Inventory</span>
                            </div>
                        </a>
                        <div class="parent-wrapper label-1">
                            <ul class="nav collapse parent" data-bs-parent="#navbarVerticalCollapse" id="nv-inventory">
                                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'inventory-items.php' ? "active" : ""  ?>" href="inventory-items">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">Stock Items</span></div>
                                    </a>
                                </li>
                                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'inventory-movements.php' ? "active" : ""  ?>" href="inventory-movements">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">Stock Movements</span></div>
                                    </a>
                                </li>
                                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'low-inventory-alert.php' ? "active" : ""  ?>" href="low-inventory-alert">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">Low Stock Alert</span></div>
                                    </a>
                                </li>
                                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'suppliers.php' ? "active" : ""  ?>" href="suppliers">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">Suppliers</span></div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Menu Management Section -->
                    <?php if ($showNav('menu')): ?>
                    <div class="nav-item-wrapper"><a class="nav-link dropdown-indicator label-1" href="#nv-menu" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="nv-menu">
                            <div class="d-flex align-items-center">
                                <div class="dropdown-indicator-icon-wrapper"><span class="fas fa-caret-right dropdown-indicator-icon"></span></div><span class="nav-link-icon"><span data-feather="book-open"></span></span><span class="nav-link-text">Menu</span>
                            </div>
                        </a>
                        <div class="parent-wrapper label-1">
                            <ul class="nav collapse parent" data-bs-parent="#navbarVerticalCollapse" id="nv-menu">
                                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'menu.php' ? "active" : ""  ?>" href="menu">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">Menu Items</span></div>
                                    </a>
                                </li>
                                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'recipes.php' ? "active" : ""  ?>" href="recipes">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">Recipes</span></div>
                                    </a>
                                </li>
                                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'categories.php' ? "active" : ""  ?>" href="categories">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">Categories</span></div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Reservations Section -->
                    <?php if ($showNav('reservations')): ?>
                    <div class="nav-item-wrapper"><a class="nav-link dropdown-indicator label-1" href="#nv-reservations" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="nv-reservations">
                            <div class="d-flex align-items-center">
                                <div class="dropdown-indicator-icon-wrapper"><span class="fas fa-caret-right dropdown-indicator-icon"></span></div><span class="nav-link-icon"><span data-feather="calendar"></span></span><span class="nav-link-text">Reservations</span>
                            </div>
                        </a>
                        <div class="parent-wrapper label-1">
                            <ul class="nav collapse parent" data-bs-parent="#navbarVerticalCollapse" id="nv-reservations">
                                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'reservations.php' ? "active" : ""  ?>" href="reservations">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">All Reservations</span></div>
                                    </a>
                                </li>
                                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'new-reservation.php' ? "active" : ""  ?>" href="new-reservation">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">New Reservation</span></div>
                                    </a>
                                </li>
                                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'tables.php' ? "active" : ""  ?>" href="tables">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">Restaurant Tables</span></div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Orders Section -->
                    <?php if ($showNav('orders')): ?>
                    <div class="nav-item-wrapper"><a class="nav-link dropdown-indicator label-1" href="#nv-orders" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="nv-orders">
                            <div class="d-flex align-items-center">
                                <div class="dropdown-indicator-icon-wrapper"><span class="fas fa-caret-right dropdown-indicator-icon"></span></div><span class="nav-link-icon"><span data-feather="shopping-cart"></span></span><span class="nav-link-text">Orders</span>
                            </div>
                        </a>
                        <div class="parent-wrapper label-1">
                            <ul class="nav collapse parent" data-bs-parent="#navbarVerticalCollapse" id="nv-orders">
                                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'orders.php' ? "active" : ""  ?>" href="orders">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">All Orders</span></div>
                                    </a>
                                </li>
                                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'new-order.php' ? "active" : ""  ?>" href="new-order">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">New Order</span></div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Purchase Orders Section -->
                    <?php if ($showNav('purchase')): ?>
                    <div class="nav-item-wrapper"><a class="nav-link dropdown-indicator label-1" href="#nv-purchase" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="nv-purchase">
                            <div class="d-flex align-items-center">
                                <div class="dropdown-indicator-icon-wrapper"><span class="fas fa-caret-right dropdown-indicator-icon"></span></div><span class="nav-link-icon"><span data-feather="truck"></span></span><span class="nav-link-text">Purchase Orders</span>
                            </div>
                        </a>
                        <div class="parent-wrapper label-1">
                            <ul class="nav collapse parent" data-bs-parent="#navbarVerticalCollapse" id="nv-purchase">
                                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'purchase-orders.php' ? "active" : ""  ?>" href="purchase-orders">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">All Purchase Orders</span></div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Management Section -->
                    <?php if ($showNav('customers') || $showNav('staff')): ?>
                    <p class="navbar-vertical-label mt-3">Management</p>
                    <hr class="navbar-vertical-line" />
                    
                    <div class="nav-item-wrapper"><a class="nav-link dropdown-indicator label-1" href="#nv-management" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="nv-management">
                            <div class="d-flex align-items-center">
                                <div class="dropdown-indicator-icon-wrapper"><span class="fas fa-caret-right dropdown-indicator-icon"></span></div><span class="nav-link-icon"><span data-feather="users"></span></span><span class="nav-link-text">People</span>
                            </div>
                        </a>
                        <div class="parent-wrapper label-1">
                            <ul class="nav collapse parent" data-bs-parent="#navbarVerticalCollapse" id="nv-management">
                                <?php if ($showNav('customers')): ?>
                                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'customers.php' ? "active" : ""  ?>" href="customers">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">Customers</span></div>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if ($showNav('staff')): ?>
                                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'staff.php' ? "active" : ""  ?>" href="staff">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">Staff</span></div>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Promotions Section -->
                    <?php if ($showNav('promotions')): ?>
                    <div class="nav-item-wrapper"><a class="nav-link dropdown-indicator label-1" href="#nv-promotions" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="nv-promotions">
                            <div class="d-flex align-items-center">
                                <div class="dropdown-indicator-icon-wrapper"><span class="fas fa-caret-right dropdown-indicator-icon"></span></div><span class="nav-link-icon"><span data-feather="gift"></span></span><span class="nav-link-text">Promotions</span>
                            </div>
                        </a>
                        <div class="parent-wrapper label-1">
                            <ul class="nav collapse parent" data-bs-parent="#navbarVerticalCollapse" id="nv-promotions">
                                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'promotions.php' ? "active" : ""  ?>" href="promotions">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">All Promotions</span></div>
                                    </a>
                                </li>
                              
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Reports Section -->
                    <?php if ($showNav('reports')): ?>
                    <div class="nav-item-wrapper"><a class="nav-link dropdown-indicator label-1" href="#nv-reports" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="nv-reports">
                            <div class="d-flex align-items-center">
                                <div class="dropdown-indicator-icon-wrapper"><span class="fas fa-caret-right dropdown-indicator-icon"></span></div><span class="nav-link-icon"><span data-feather="bar-chart-2"></span></span><span class="nav-link-text">Reports</span>
                            </div>
                        </a>
                        <div class="parent-wrapper label-1">
                            <ul class="nav collapse parent" data-bs-parent="#navbarVerticalCollapse" id="nv-reports">
                                <li class="nav-item"><a class="nav-link <?php echo $current_page == 'reports.php' ? "active" : ""  ?>" href="reports">
                                        <div class="d-flex align-items-center"><span class="nav-link-text">Reports</span></div>
                                    </a>
                                </li>

                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>
    <div class="navbar-vertical-footer"><button class="btn navbar-vertical-toggle border-0 fw-semibold w-100 white-space-nowrap d-flex align-items-center"><span class="fas fa-caret-right dropdown-indicator-icon fs-8"></span><span class="navbar-vertical-footer-text ms-2">Collapsed View</span></button></div>
</nav>