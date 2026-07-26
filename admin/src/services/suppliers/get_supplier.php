<?php
require_once '../../../config/config.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['error' => 'Invalid supplier ID']);
    exit;
}
$stmt = $conn->prepare('SELECT * FROM suppliers WHERE supplier_id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Supplier not found']);
}
$stmt->close(); 