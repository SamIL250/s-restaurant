<?php
// Test script to verify dashboard filtering
include '../config/config.php';

echo "<h2>Dashboard Filtering Test Results</h2>";

// Test 1: Orders should exclude deleted and cancelled
echo "<h3>Test 1: Orders Filtering</h3>";
$test1 = mysqli_query($conn, "
    SELECT COUNT(*) as total, 
           SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) as deleted_count,
           SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
    FROM orders
");
$result1 = mysqli_fetch_assoc($test1);
echo "<p>Total orders in database: {$result1['total']}</p>";
echo "<p>Deleted orders: {$result1['deleted_count']}</p>";
echo "<p>Cancelled orders: {$result1['cancelled_count']}</p>";

$active_orders = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM orders 
    WHERE deleted_at IS NULL AND order_status NOT IN ('cancelled')
");
$active_result = mysqli_fetch_assoc($active_orders);
echo "<p><strong>Active orders (should be shown): {$active_result['count']}</strong></p>";

// Test 2: Reservations should exclude deleted and cancelled
echo "<h3>Test 2: Reservations Filtering</h3>";
$test2 = mysqli_query($conn, "
    SELECT COUNT(*) as total, 
           SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) as deleted_count,
           SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
    FROM reservations
");
$result2 = mysqli_fetch_assoc($test2);
echo "<p>Total reservations in database: {$result2['total']}</p>";
echo "<p>Deleted reservations: {$result2['deleted_count']}</p>";
echo "<p>Cancelled reservations: {$result2['cancelled_count']}</p>";

$active_reservations = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM reservations 
    WHERE deleted_at IS NULL AND status NOT IN ('cancelled')
");
$active_res_result = mysqli_fetch_assoc($active_reservations);
echo "<p><strong>Active reservations (should be shown): {$active_res_result['count']}</strong></p>";

// Test 3: Menu items should exclude deleted and unavailable
echo "<h3>Test 3: Menu Items Filtering</h3>";
$test3 = mysqli_query($conn, "
    SELECT COUNT(*) as total, 
           SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) as deleted_count,
           SUM(CASE WHEN is_available = 0 THEN 1 ELSE 0 END) as unavailable_count
    FROM menu_items
");
$result3 = mysqli_fetch_assoc($test3);
echo "<p>Total menu items in database: {$result3['total']}</p>";
echo "<p>Deleted menu items: {$result3['deleted_count']}</p>";
echo "<p>Unavailable menu items: {$result3['unavailable_count']}</p>";

$available_items = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM menu_items 
    WHERE deleted_at IS NULL AND is_available = 1
");
$available_result = mysqli_fetch_assoc($available_items);
echo "<p><strong>Available menu items (should be shown): {$available_result['count']}</strong></p>";

// Test 4: Inventory items should exclude deleted and inactive
echo "<h3>Test 4: Inventory Items Filtering</h3>";
$test4 = mysqli_query($conn, "
    SELECT COUNT(*) as total, 
           SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) as deleted_count,
           SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_count
    FROM inventory_items
");
$result4 = mysqli_fetch_assoc($test4);
echo "<p>Total inventory items in database: {$result4['total']}</p>";
echo "<p>Deleted inventory items: {$result4['deleted_count']}</p>";
echo "<p>Inactive inventory items: {$result4['inactive_count']}</p>";

$active_items = mysqli_query($conn, "
    SELECT COUNT(*) as count FROM inventory_items 
    WHERE deleted_at IS NULL AND is_active = 1
");
$active_items_result = mysqli_fetch_assoc($active_items);
echo "<p><strong>Active inventory items (should be shown): {$active_items_result['count']}</strong></p>";

// Test 5: Revenue should only include completed orders
echo "<h3>Test 5: Revenue Filtering</h3>";
$revenue_test = mysqli_query($conn, "
    SELECT COUNT(*) as total, 
           SUM(CASE WHEN order_status = 'completed' THEN total_amount ELSE 0 END) as completed_revenue,
           SUM(CASE WHEN order_status != 'completed' THEN total_amount ELSE 0 END) as other_revenue
    FROM orders 
    WHERE deleted_at IS NULL
");
$revenue_result = mysqli_fetch_assoc($revenue_test);
echo "<p>Orders with revenue data: {$revenue_result['total']}</p>";
echo "<p>Revenue from completed orders: Frw " . number_format($revenue_result['completed_revenue'], 2) . "</p>";
echo "<p>Revenue from other statuses: Frw " . number_format($revenue_result['other_revenue'], 2) . "</p>";

echo "<ul>
    <li>Active orders (not deleted, not cancelled)</li>
    <li>Active reservations (not deleted, not cancelled)</li>
    <li>Available menu items (not deleted, is_available = 1)</li>
    <li>Active inventory items (not deleted, is_active = 1)</li>
    <li>Revenue only from completed orders</li>
</ul>";

mysqli_close($conn);
?>
