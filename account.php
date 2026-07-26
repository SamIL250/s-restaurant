<?php
include './config/config.php';
require_once './services/auth/CustomerSession.php';
CustomerSession::start();
$currentCustomer = CustomerSession::user();
if (!$currentCustomer) {
    header('Location: ' . SITE_WEB_PATH . '/login');
    exit();
}

$customerProfile = [
    'first_name' => '',
    'last_name' => '',
    'email' => $currentCustomer['email'],
    'phone' => $currentCustomer['phone'],
];

$profileStmt = $conn->prepare('SELECT first_name, last_name, email, phone FROM customers WHERE customer_id = ? AND deleted_at IS NULL LIMIT 1');
if ($profileStmt) {
    $profileStmt->bind_param('i', $currentCustomer['customer_id']);
    $profileStmt->execute();
    $profileRow = $profileStmt->get_result()->fetch_assoc();
    $profileStmt->close();
    if ($profileRow) {
        $customerProfile = $profileRow;
        $currentCustomer['name'] = trim(($profileRow['first_name'] ?? '') . ' ' . ($profileRow['last_name'] ?? ''));
    }
}

$pageBodyClass = 'inner-page scrolled';
include './src/layout_start.php';
?>
<main class="main">
  <?php include './src/pages/account/main.php'; ?>
</main>
<?php include './src/layout_end.php'; ?>
