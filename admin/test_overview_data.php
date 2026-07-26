<?php
// Test the data functions directly
require_once '../config/config.php';

echo "<h2>🔍 Testing Overview Data Functions</h2>";

// Test each function
echo "<h3>📊 Function Results:</h3>";

// Test getTotalItems
$totalItems = getTotalItems();
echo "<p><strong>getTotalItems():</strong> " . $totalItems . "</p>";

// Test getLowStockItems  
$lowStockItems = getLowStockItems();
echo "<p><strong>getLowStockItems():</strong> " . $lowStockItems . "</p>";

// Test getTotalStockValue
$totalValue = getTotalStockValue();
echo "<p><strong>getTotalStockValue():</strong> $" . number_format($totalValue, 2) . "</p>";

// Test getExpiringItems
$expiringItems = getExpiringItems();
echo "<p><strong>getExpiringItems():</strong> " . $expiringItems . "</p>";

// Test getRecentMovements
$recentMovements = getRecentMovements();
echo "<p><strong>getRecentMovements():</strong> " . (is_array($recentMovements) ? count($recentMovements) . " items" : "NOT ARRAY") . "</p>";

if (is_array($recentMovements) && !empty($recentMovements)) {
    echo "<h4>📋 Recent Movements Data:</h4>";
    echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr><th>Item</th><th>Type</th><th>Quantity</th><th>Value</th><th>Date</th><th>Reason</th></tr>";
    foreach ($recentMovements as $movement) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($movement['item_name']) . "</td>";
        echo "<td>" . ucfirst($movement['movement_type']) . "</td>";
        echo "<td>" . $movement['quantity'] . "</td>";
        echo "<td>$" . number_format($movement['total_cost'], 2) . "</td>";
        echo "<td>" . date('M d, Y', strtotime($movement['movement_date'])) . "</td>";
        echo "<td>" . htmlspecialchars($movement['reason']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Define the functions here for testing
function getTotalItems() {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM inventory_items WHERE deleted_at IS NULL AND is_active = 1";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return (int)$row['total'];
}

function getLowStockItems() {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM inventory_items WHERE current_stock <= minimum_stock AND deleted_at IS NULL AND is_active = 1";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return (int)$row['total'];
}

function getTotalStockValue() {
    global $conn;
    $query = "SELECT SUM(current_stock * unit_cost) as total_value FROM inventory_items WHERE deleted_at IS NULL AND is_active = 1";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return (float)$row['total_value'];
}

function getExpiringItems() {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM inventory_items WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND deleted_at IS NULL AND is_active = 1";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return (int)$row['total'];
}

function getRecentMovements() {
    global $conn;
    $query = "SELECT sm.movement_date, sm.movement_type, sm.quantity, sm.total_cost, sm.reason, i.item_name FROM stock_movements sm JOIN inventory_items i ON sm.item_id = i.item_id WHERE i.deleted_at IS NULL ORDER BY sm.movement_date DESC LIMIT 10";
    $result = mysqli_query($conn, $query);
    $movements = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $movements[] = $row;
    }
    return $movements;
}

echo "<h3>🔧 Database Connection:</h3>";
echo "<p><strong>Connection:</strong> " . ($conn ? "✅ Connected" : "❌ Failed") . "</p>";

echo "<h3>📊 Summary:</h3>";
echo "<p>If you see data above, then the functions work. If not, there's a database issue.</p>";
echo "<p><strong>Next:</strong> Try viewing the Overview report again to see if data loads correctly.</p>";

mysqli_close($conn);
?>
