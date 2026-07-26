<?php

const ROLE_ADMIN = 'admin';
const ROLE_CASHIER = 'cashier';
const ROLE_STOCK_CLERK = 'stock_clerk';

const ALL_ROLES = [
    ROLE_ADMIN,
    ROLE_CASHIER,
    ROLE_STOCK_CLERK,
];

/**
 * Pages each role may open (basename of admin/*.php entry files).
 */
function role_page_permissions(): array
{
    return [
        ROLE_ADMIN => ['*'],
        ROLE_CASHIER => [
            'index.php',
            'orders.php',
            'new-order.php',
            'reservations.php',
            'new-reservation.php',
            'tables.php',
            'customers.php',
            'customers-subscriptions.php',
            'profile.php',
        ],
        ROLE_STOCK_CLERK => [
            'index.php',
            'inventory-items.php',
            'inventory-movements.php',
            'low-inventory-alert.php',
            'suppliers.php',
            'purchase-orders.php',
            'reports.php',
            'profile.php',
        ],
    ];
}

function role_can_access_page(string $role, string $page): bool
{
    $permissions = role_page_permissions();
    $allowed = $permissions[$role] ?? [];

    if (in_array('*', $allowed, true)) {
        return true;
    }

    return in_array($page, $allowed, true);
}

function role_label(string $role): string
{
    switch ($role) {
        case ROLE_ADMIN:
            return 'Admin';
        case ROLE_CASHIER:
            return 'Cashier';
        case ROLE_STOCK_CLERK:
            return 'Stock Clerk';
        default:
            return ucfirst(str_replace('_', ' ', $role));
    }
}

function role_badge_class(string $role): string
{
    switch ($role) {
        case ROLE_ADMIN:
            return 'danger';
        case ROLE_CASHIER:
            return 'info';
        case ROLE_STOCK_CLERK:
            return 'warning';
        default:
            return 'secondary';
    }
}

function report_tabs_for_role(string $role): array
{
    switch ($role) {
        case ROLE_ADMIN:
            return ['overview', 'stock', 'movements', 'loss', 'performance', 'customers'];
        case ROLE_STOCK_CLERK:
            return ['stock', 'movements', 'loss'];
        default:
            return [];
    }
}

function role_default_landing_page(string $role): string
{
    switch ($role) {
        case ROLE_CASHIER:
            return 'orders';
        case ROLE_STOCK_CLERK:
            return 'inventory-items';
        default:
            return 'index';
    }
}

function role_nav_sections(string $role): array
{
    switch ($role) {
        case ROLE_ADMIN:
            return ['dashboard', 'inventory', 'menu', 'reservations', 'orders', 'purchase', 'customers', 'staff', 'promotions', 'reports'];
        case ROLE_CASHIER:
            return ['dashboard', 'reservations', 'orders', 'customers'];
        case ROLE_STOCK_CLERK:
            return ['dashboard', 'inventory', 'purchase', 'reports'];
        default:
            return ['dashboard'];
    }
}

function role_can_access_section(string $role, string $section): bool
{
    return in_array($section, role_nav_sections($role), true);
}

function is_valid_role(string $role): bool
{
    return in_array($role, ALL_ROLES, true);
}

?>
