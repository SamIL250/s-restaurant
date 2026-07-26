<?php
$conn = mysqli_connect('localhost', 'root', '', 'tacos');
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}

echo "Available tables in tacos database:\n";
$result = mysqli_query($conn, 'SHOW TABLES');
while($row = mysqli_fetch_array($result)) {
    echo "- " . $row[0] . "\n";
}

mysqli_close($conn);
?>
