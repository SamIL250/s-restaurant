<?php

if (!defined('SITE_WEB_PATH')) {
    $documentRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $siteRoot = str_replace('\\', '/', dirname(__DIR__));
    $webPath = $documentRoot !== '' ? substr($siteRoot, strlen($documentRoot)) : '';
    define('SITE_WEB_PATH', $webPath === '' ? '' : $webPath);
}

define('RESTAURANT_NAME', 'Smart Resto Restaurant');
define('RESTAURANT_EMAIL', 'scamil350@gmail.com');
define('RESTAURANT_PHONE', '+250788277182');
define('RESTAURANT_WHATSAPP', '250788277182');
define('RESTAURANT_ADDRESS', 'Kigali, Rwanda');

define('ORDER_TAX_RATE', 0.10);
define('ORDER_DELIVERY_FEE', 5.00);

?>
