<?php
$conn = mysqli_connect('localhost', 'root', '', 'tacos_v2');
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}

echo "Structure of purchase_order_items table:\n";
$result = mysqli_query($conn, 'DESCRIBE purchase_order_items');
while($row = mysqli_fetch_array($result)) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\nStructure of inventory_items table:\n";
$result = mysqli_query($conn, 'DESCRIBE inventory_items');
while($row = mysqli_fetch_array($result)) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

mysqli_close($conn);
?>
