<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../categories');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../categories');
    exit();
}

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit;
    }
    setErrorMessage('Invalid request method.');
}

$category_name = trim($_POST['category_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$is_active = isset($_POST['is_active']) ? 1 : 0;

if ($category_name === '') {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Category name is required.']);
        exit;
    }
    setErrorMessage('Category name is required.');
}

// Check for duplicate
$stmt = $conn->prepare('SELECT category_id FROM categories WHERE category_name = ?');
$stmt->bind_param('s', $category_name);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Category name already exists.']);
        exit;
    }
    setErrorMessage('Category name already exists.');
}
$stmt->close();

$stmt = $conn->prepare('INSERT INTO categories (category_name, description, is_active) VALUES (?, ?, ?)');
$stmt->bind_param('ssi', $category_name, $description, $is_active);
if ($stmt->execute()) {
    $stmt->close();
    if ($is_ajax) {
        echo json_encode(['success' => true, 'message' => 'Category added successfully.']);
        exit;
    }
    setSuccessMessage('Category added successfully.');
} else {
    $stmt->close();
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Failed to add category.']);
        exit;
    }
    setErrorMessage('Failed to add category.');
}
$conn->close(); 