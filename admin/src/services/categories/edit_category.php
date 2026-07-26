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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$category_id = intval($_POST['category_id'] ?? 0);
$category_name = trim($_POST['category_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$is_active = isset($_POST['is_active']) ? 1 : 0;

if ($category_id <= 0 || $category_name === '') {
    setErrorMessage('Category ID and name are required.');
}

// Check for duplicate name (excluding self)
$stmt = $conn->prepare('SELECT category_id FROM categories WHERE category_name = ? AND category_id != ?');
$stmt->bind_param('si', $category_name, $category_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    setErrorMessage('Category name already exists.');
}
$stmt->close();

$stmt = $conn->prepare('UPDATE categories SET category_name = ?, description = ?, is_active = ? WHERE category_id = ?');
$stmt->bind_param('ssii', $category_name, $description, $is_active, $category_id);
if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to update category. Please try again.');
}
$stmt->close();
setSuccessMessage('Category updated successfully.'); 