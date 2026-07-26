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

$menu_item_id = intval($_POST['menu_item_id'] ?? 0);
$item_id = intval($_POST['item_id'] ?? 0);
$quantity_needed = floatval($_POST['quantity_needed'] ?? 0);
$unit = trim($_POST['unit'] ?? '');

if ($menu_item_id <= 0 || $item_id <= 0 || $quantity_needed <= 0 || $unit === '') {
    setErrorMessage('All fields are required.');
}

// Prevent duplicate ingredient for the same menu item
$stmt = $conn->prepare('SELECT recipe_id FROM recipe_ingredients WHERE menu_item_id = ? AND item_id = ?');
$stmt->bind_param('ii', $menu_item_id, $item_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    setErrorMessage('This ingredient is already added to the recipe.');
}
$stmt->close();

$stmt = $conn->prepare('INSERT INTO recipe_ingredients (menu_item_id, item_id, quantity_needed, unit) VALUES (?, ?, ?, ?)');
$stmt->bind_param('iids', $menu_item_id, $item_id, $quantity_needed, $unit);
if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to add ingredient. Please try again.');
}
$stmt->close();
setSuccessMessage('Ingredient added to recipe.'); 