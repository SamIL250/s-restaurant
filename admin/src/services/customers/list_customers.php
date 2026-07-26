<?php
require_once '../../../config/config.php';
header('Content-Type: application/json');

$search = trim($_GET['search'] ?? '');
$where = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where = 'WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ?';
    $search_param = '%' . $search . '%';
    $params = [$search_param, $search_param, $search_param];
    $types = 'sss';
}

$sql = "SELECT * FROM customers $where ORDER BY first_name, last_name";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$customers = [];
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

echo json_encode(['success' => true, 'customers' => $customers]);
$stmt->close();
$conn->close(); 