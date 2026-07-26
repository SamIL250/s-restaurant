<?php
// Clean test file
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Clean Database Test</h2>";

try {
    // Test database connection
    $conn = mysqli_connect('localhost', 'root', '', 'tacos_v2');
    
    if (!$conn) {
        echo "<p>❌ Database connection failed: " . mysqli_connect_error() . "</p>";
        exit();
    }
    
    echo "<p>✅ Database connected successfully</p>";
    
    // Test 1: Simple inventory count
    $query1 = "SELECT COUNT(*) as total FROM inventory_items WHERE deleted_at IS NULL AND is_active = 1";
    $result1 = mysqli_query($conn, $query1);
    if (!$result1) {
        echo "<p>❌ Query 1 failed: " . mysqli_error($conn) . "</p>";
    } else {
        $row1 = mysqli_fetch_assoc($result1);
        echo "<p>📊 Total items: " . $row1['total'] . "</p>";
    }
    
    // Test 2: Stock movements count
    $query2 = "SELECT COUNT(*) as total FROM stock_movements WHERE deleted_at IS NULL";
    $result2 = mysqli_query($conn, $query2);
    if (!$result2) {
        echo "<p>❌ Query 2 failed: " . mysqli_error($conn) . "</p>";
    } else {
        $row2 = mysqli_fetch_assoc($result2);
        echo "<p>📋 Total stock movements: " . $row2['total'] . "</p>";
    }
    
    // Test 3: Get actual movement data with JOIN
    $query3 = "SELECT 
        i.item_name, 
        sm.movement_type, 
        sm.quantity, 
        sm.total_cost, 
        sm.reason, 
        sm.movement_date
    FROM stock_movements sm 
    JOIN inventory_items i ON sm.item_id = i.item_id 
    WHERE sm.deleted_at IS NULL AND i.deleted_at IS NULL 
    ORDER BY sm.movement_date DESC 
    LIMIT 3";
    
    $result3 = mysqli_query($conn, $query3);
    if (!$result3) {
        echo "<p>❌ Query 3 failed: " . mysqli_error($conn) . "</p>";
    } else {
        if (mysqli_num_rows($result3) > 0) {
            echo "<h4>📋 Sample Movements:</h4>";
            echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
            echo "<tr style='background: #f0f0f0;'><th>Item</th><th>Type</th><th>Quantity</th><th>Cost</th><th>Date</th><th>Reason</th></tr>";
            
            while ($row3 = mysqli_fetch_assoc($result3)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row3['item_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row3['movement_type']) . "</td>";
                echo "<td>" . $row3['quantity'] . "</td>";
                echo "<td>$" . number_format($row3['total_cost'], 2) . "</td>";
                echo "<td>" . date('M d, Y', strtotime($row3['movement_date'])) . "</td>";
                echo "<td>" . htmlspecialchars($row3['reason']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>⚠️ No stock movements found</p>";
        }
    }
    
    mysqli_close($conn);
    
} catch (Exception $e) {
    echo "<p>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<h3>✅ Test Complete</h3>";
echo "<p>If you see this page with data above, everything works!</p>";
?>
