<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../menu');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../menu');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$menu_item_id = intval($_POST['menu_item_id'] ?? 0);
$item_name = trim($_POST['item_name'] ?? '');
$category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
$price = floatval($_POST['price'] ?? 0);
$cost = floatval($_POST['cost'] ?? 0);
$preparation_time = intval($_POST['preparation_time'] ?? 0);
$calories = intval($_POST['calories'] ?? 0);
$is_vegetarian = isset($_POST['is_vegetarian']) ? 1 : 0;
$is_vegan = isset($_POST['is_vegan']) ? 1 : 0;
$is_gluten_free = isset($_POST['is_gluten_free']) ? 1 : 0;
$is_available = isset($_POST['is_available']) ? intval($_POST['is_available']) : 1;
$description = trim($_POST['description'] ?? '');

// Handle image upload or URL
$image_url = '';
if (isset($_FILES['image_file']) && !empty($_FILES['image_file']['name'])) {
    $file = $_FILES['image_file'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        $error_msg = $error_messages[$file['error']] ?? 'Unknown upload error (code: ' . $file['error'] . ')';
        setErrorMessage('Upload error: ' . $error_msg);
    }
    
    // Handle file upload
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 2 * 1024 * 1024; // 2MB (matching PHP upload_max_filesize)
    
    // Validate file type
    if (!function_exists('finfo_open')) {
        // Fallback to extension check if finfo not available
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $allowed_extensions)) {
            setErrorMessage('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.');
        }
        $mime_type = 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension);
    } else {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            setErrorMessage('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed. Detected: ' . $mime_type);
        }
    }
    
    // Validate file size
    if ($file['size'] > $max_size) {
        setErrorMessage('File size exceeds 2MB limit. Your file is ' . round($file['size'] / 1024 / 1024, 2) . 'MB.');
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('menu_', true) . '_' . time() . '.' . $extension;
    $upload_dir = __DIR__ . '/uploads/';
    $upload_path = $upload_dir . $filename;
    
    // Ensure upload directory exists and is writable
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            setErrorMessage('Failed to create upload directory. Please check permissions.');
        }
    }
    
    if (!is_writable($upload_dir)) {
        setErrorMessage('Upload directory is not writable. Please check permissions on: ' . $upload_dir);
    }
    
    // Delete old image if it exists and is in our uploads folder
    $old_image_query = mysqli_query($conn, "SELECT image_url FROM menu_items WHERE menu_item_id = $menu_item_id");
    if ($old_image = mysqli_fetch_assoc($old_image_query)) {
        $old_image_path = $old_image['image_url'];
        if ($old_image_path && strpos($old_image_path, '/uploads/') !== false) {
            $old_file = __DIR__ . str_replace('/restaurant_stock/src/services/menu', '', $old_image_path);
            if (file_exists($old_file)) {
                @unlink($old_file);
            }
        }
    }
    
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        $error_details = 'Upload failed. ';
        if (!is_writable($upload_dir)) {
            $error_details .= 'Directory not writable. ';
        }
        if (!file_exists($file['tmp_name'])) {
            $error_details .= 'Temporary file not found. ';
        }
        setErrorMessage('Failed to upload image file. ' . $error_details . 'Please check server permissions.');
    } else {
        $image_url = '/restaurant_stock/src/services/menu/uploads/' . $filename;
    }
} elseif (!empty($_POST['image_url'])) {
    // Use provided URL
    $image_url = trim($_POST['image_url']);
} else {
    // Keep existing image if no new one provided
    $existing_query = mysqli_query($conn, "SELECT image_url FROM menu_items WHERE menu_item_id = $menu_item_id");
    if ($existing = mysqli_fetch_assoc($existing_query)) {
        $image_url = $existing['image_url'] ?? '';
    }
}

if ($menu_item_id <= 0 || $item_name === '' || $price <= 0) {
    setErrorMessage('Menu item ID, name, and price are required.');
}

$stmt = $conn->prepare('UPDATE menu_items SET item_name = ?, category_id = ?, price = ?, cost = ?, preparation_time = ?, calories = ?, is_vegetarian = ?, is_vegan = ?, is_gluten_free = ?, is_available = ?, image_url = ?, description = ? WHERE menu_item_id = ?');
$stmt->bind_param('siddiiiiiissi', $item_name, $category_id, $price, $cost, $preparation_time, $calories, $is_vegetarian, $is_vegan, $is_gluten_free, $is_available, $image_url, $description, $menu_item_id);
if (!$stmt->execute()) {
    $stmt->close();
    setErrorMessage('Failed to update menu item. Please try again.');
}
$stmt->close();
setSuccessMessage('Menu item updated successfully.'); 