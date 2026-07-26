<?php
// Simple test to identify 500 error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Simple Database Test</h2>";

try {
    // Test database connection
    $conn = mysqli_connect('localhost', 'root', '', 'tacos_v2');
    
    if (!$conn) {
        echo "<p>❌ Database connection failed: " . mysqli_connect_error() . "</p>";
    } else {
        echo "<p>✅ Database connected successfully</p>";
        
        // Test basic query
        $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM inventory_items LIMIT 1");
        
        if (!$result) {
            echo "<p>❌ Query failed: " . mysqli_error($conn) . "</p>";
        } else {
            $row = mysqli_fetch_assoc($result);
            echo "<p>📊 Total items in database: " . $row['total'] . "</p>";
            
            // Test stock movements table
            $result2 = mysqli_query($conn, "SELECT COUNT(*) as total FROM stock_movements LIMIT 1");
            
            if (!$result2) {
                echo "<p>❌ Stock movements query failed: " . mysqli_error($conn) . "</p>";
            } else {
                $row2 = mysqli_fetch_assoc($result2);
                echo "<p>📋 Total stock movements: " . $row2['total'] . "</p>";
                
                // Get some sample data with proper JOIN
                $result3 = mysqli_query($conn, "SELECT i.item_name, sm.movement_type, sm.quantity, sm.reason FROM stock_movements sm JOIN inventory_items i ON sm.item_id = i.item_id WHERE sm.deleted_at IS NULL LIMIT 3");
                
                if ($result3 && mysqli_num_rows($result3) > 0) {
                    echo "<h4>📋 Sample Movements:</h4>";
                    echo "<table border='1' style='width: 100%;'>";
                    echo "<tr><th>Item</th><th>Type</th><th>Quantity</th><th>Reason</th></tr>";
                    
                    while ($row3 = mysqli_fetch_assoc($result3)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row3['item_name']) . "</td>";
                        echo "<td>" . $row3['movement_type'] . "</td>";
                        echo "<td>" . $row3['quantity'] . "</td>";
                        echo "<td>" . htmlspecialchars($row3['reason']) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>⚠️ No stock movements found</p>";
                }
            }
        }
        
        mysqli_close($conn);
    }
    
} catch (Exception $e) {
    echo "<p>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<h3>🔧 Test Summary:</h3>";
echo "<p>If you see this page without errors, database connection works.</p>";
echo "<p>If you see data above, the database has records.</p>";
echo "<p>If you see 500 error, there's a server configuration issue.</p>";
?>
