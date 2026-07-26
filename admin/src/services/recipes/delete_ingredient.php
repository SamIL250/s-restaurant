<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../recipes');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../recipes');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$recipe_id = intval($_POST['recipe_id'] ?? 0);

if ($recipe_id <= 0) {
    setErrorMessage('Invalid recipe ID.');
}

// Check if recipe exists and is not already deleted
$check_stmt = $conn->prepare('SELECT recipe_id, menu_item_id, item_id FROM recipe_ingredients WHERE recipe_id = ? AND deleted_at IS NULL');
$check_stmt->bind_param('i', $recipe_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $check_stmt->close();
    setErrorMessage('Recipe ingredient not found or already deleted.');
}
$check_stmt->close();

// Soft delete the recipe ingredient
$delete_stmt = $conn->prepare('UPDATE recipe_ingredients SET deleted_at = NOW() WHERE recipe_id = ?');
$delete_stmt->bind_param('i', $recipe_id);

if (!$delete_stmt->execute()) {
    $delete_stmt->close();
    setErrorMessage('Failed to delete recipe ingredient. Please try again.');
}

$delete_stmt->close();
setSuccessMessage('Recipe ingredient deleted successfully.');
?> 