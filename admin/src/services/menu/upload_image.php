<?php
require_once '../../../config/config.php';
require_once '../../../src/services/auth/service_guard.php';
session_start();

header('Content-Type: application/json');

// Check authentication and service access
$role = $_SESSION['user_role'] ?? null;
if (!$role || !role_can_access_service_directory($role, 'menu')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Check if file upload or URL
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    // Handle file upload
    $file = $_FILES['image'];
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.']);
        exit();
    }
    
    // Validate file size
    if ($file['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit.']);
        exit();
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('menu_', true) . '_' . time() . '.' . $extension;
    $upload_dir = __DIR__ . '/uploads/';
    $upload_path = $upload_dir . $filename;
    
    // Ensure upload directory exists and is writable
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    if (!is_writable($upload_dir)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Upload directory is not writable.']);
        exit();
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Return relative URL path
        $relative_path = '/restaurant_stock/src/services/menu/uploads/' . $filename;
        echo json_encode([
            'success' => true,
            'message' => 'Image uploaded successfully',
            'image_url' => $relative_path,
            'filename' => $filename
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
    }
    
} elseif (isset($_POST['image_url']) && !empty($_POST['image_url'])) {
    // Handle URL validation
    $url = trim($_POST['image_url']);
    
    // Validate URL format
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid URL format.']);
        exit();
    }
    
    // Check if URL is accessible and is an image
    $headers = @get_headers($url, 1);
    if (!$headers || strpos($headers[0], '200') === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Image URL is not accessible.']);
        exit();
    }
    
    // Check content type
    $content_type = $headers['Content-Type'] ?? '';
    if (is_array($content_type)) {
        $content_type = end($content_type);
    }
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array(strtolower($content_type), $allowed_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'URL does not point to a valid image.']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Image URL validated successfully',
        'image_url' => $url
    ]);
    
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No image file or URL provided.']);
}
?>

