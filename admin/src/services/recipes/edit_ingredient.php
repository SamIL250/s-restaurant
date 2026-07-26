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
$quantity_needed = floatval($_POST['quantity_needed'] ?? 0);
$unit = trim($_POST['unit'] ?? '');

if ($recipe_id <= 0 || $quantity_needed <= 0 || $unit === '') {
    setErrorMessage('All fields are required.');
}

$stmt = $conn->prepare('UPDATE recipe_ingredients SET quantity_needed = ?, unit = ? WHERE recipe_id = ?');
$stmt->bind_param('dsi', $quantity_needed, $unit, $recipe_id);
if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to update ingredient. Please try again.');
}
$stmt->close();
setSuccessMessage('Ingredient updated successfully.'); 