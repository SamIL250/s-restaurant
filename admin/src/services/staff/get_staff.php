<?php
require_once '../../../config/config.php';
header('Content-Type: application/json');

$user_id = intval($_GET['user_id'] ?? 0);
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

$stmt = $conn->prepare('SELECT user_id, username, email, first_name, last_name, role, phone, is_active FROM users WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'staff' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'Staff member not found.']);
}
$stmt->close();
$conn->close(); 