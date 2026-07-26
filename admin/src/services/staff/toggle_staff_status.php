<?php
require_once __DIR__ . '/../auth/service_guard.php';
requireServiceRoles([ROLE_ADMIN]);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$user_id = intval($_POST['user_id'] ?? 0);
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

if ($user_id == ($_SESSION['user_id'] ?? 0)) {
    echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own account.']);
    exit;
}

$stmt = $conn->prepare('UPDATE users SET is_active = NOT is_active WHERE user_id = ? AND deleted_at IS NULL');
$stmt->bind_param('i', $user_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Staff status updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update staff status.']);
}
$stmt->close();
