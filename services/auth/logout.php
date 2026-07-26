<?php
session_start();

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/CustomerSession.php';

header('Content-Type: application/json');

CustomerSession::logout();

echo json_encode([
    'success' => true,
    'message' => 'Signed out successfully.',
    'redirect' => SITE_WEB_PATH . '/',
]);
