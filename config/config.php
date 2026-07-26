<?php
/**
 * Smart Restaurant — single application configuration.
 * Edit settings here only.
 */

if (defined('APP_CONFIG_LOADED')) {
    return;
}

define('APP_CONFIG_LOADED', true);

// ---------------------------------------------------------------------------
// Database
// ---------------------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tacos_v2');
define('DB_CHARSET', 'utf8');

// ---------------------------------------------------------------------------
// Restaurant / site
// ---------------------------------------------------------------------------
define('RESTAURANT_NAME', 'Smart Resto Restaurant');
define('RESTAURANT_EMAIL', 'scamil350@gmail.com');
define('RESTAURANT_PHONE', '+250788277182');
define('RESTAURANT_WHATSAPP', '250788277182');
define('RESTAURANT_ADDRESS', 'Kigali, Rwanda');
define('RESTAURANT_WEBSITE', 'www.smartresto.rw');

define('ORDER_TAX_RATE', 0.10);
define('ORDER_DELIVERY_FEE', 5.00);

// ---------------------------------------------------------------------------
// Email (SMTP)
// ---------------------------------------------------------------------------
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'scamil350@gmail.com');
define('MAIL_PASSWORD', 'zwcz bzsi ilxa wggn');
define('MAIL_FROM_NAME', RESTAURANT_NAME);
define('MAIL_FROM_EMAIL', RESTAURANT_EMAIL);
define('MAIL_SECURE', 'tls');
define('MAIL_AUTH', true);
define('MAIL_DEBUG', 0);
define('MAIL_TEMPLATES_PATH', './src/templates/emails/');

// Legacy aliases used by admin purchase-order emails
define('COMPANY_NAME', RESTAURANT_NAME);
define('COMPANY_ADDRESS', RESTAURANT_ADDRESS);
define('COMPANY_PHONE', RESTAURANT_PHONE);
define('COMPANY_EMAIL', RESTAURANT_EMAIL);
define('COMPANY_WEBSITE', RESTAURANT_WEBSITE);

// ---------------------------------------------------------------------------
// Web paths (auto-detected from document root)
// ---------------------------------------------------------------------------
if (!defined('SITE_WEB_PATH')) {
    $documentRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $siteRoot = str_replace('\\', '/', dirname(__DIR__));
    $webPath = $documentRoot !== '' ? substr($siteRoot, strlen($documentRoot)) : '';
    define('SITE_WEB_PATH', $webPath === '' ? '' : $webPath);
}

if (!defined('ADMIN_WEB_PATH')) {
    $documentRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $adminRoot = str_replace('\\', '/', dirname(__DIR__) . '/admin');
    $webPath = $documentRoot !== '' ? substr($adminRoot, strlen($documentRoot)) : '/admin';
    define('ADMIN_WEB_PATH', $webPath === '' ? '/admin' : $webPath);
}

// ---------------------------------------------------------------------------
// Database connection
// ---------------------------------------------------------------------------
$conn = null;

try {
    $options = [
        MYSQLI_OPT_CONNECT_TIMEOUT => 10,
        MYSQLI_OPT_READ_TIMEOUT => 30,
        MYSQLI_INIT_COMMAND => 'SET NAMES ' . DB_CHARSET,
    ];

    $conn = mysqli_init();
    if (!$conn) {
        throw new Exception('mysqli_init failed');
    }

    foreach ($options as $option => $value) {
        mysqli_options($conn, $option, $value);
    }

    if (!mysqli_real_connect($conn, DB_HOST, DB_USER, DB_PASS, DB_NAME)) {
        throw new Exception('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
    }

    mysqli_set_charset($conn, DB_CHARSET);

    if (mysqli_connect_error()) {
        throw new Exception('Database connection failed: ' . mysqli_connect_error());
    }
} catch (Exception $e) {
    error_log('Database connection error: ' . $e->getMessage());
    $conn = null;
}

global $conn;
