<?php
require_once '../../../config/config.php';
header('Content-Type: application/json');

$status = $_GET['status'] ?? 'all';
$where = '';
if ($status === 'active') {
    $where = 'WHERE is_active = 1';
} elseif ($status === 'inactive') {
    $where = 'WHERE is_active = 0';
}

$sql = "SELECT * FROM categories $where ORDER BY is_active DESC, category_name ASC";
$result = $conn->query($sql);
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

echo json_encode(['success' => true, 'categories' => $categories]);
$conn->close(); 