<?php
require_once '../../../config/config.php';
session_start();

function setSuccessMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../customers');
    exit();
}

function setErrorMessage($message)
{
    $_SESSION['notification'] = $message;
    header('Location: ../../../customers');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setErrorMessage('Invalid request method.');
}

$action = $_POST['action'] ?? '';
$customer_id = intval($_POST['customer_id'] ?? 0);

if ($customer_id <= 0) {
    setErrorMessage('Invalid customer ID.');
}

switch ($action) {
    case 'add_subscription':
        addSubscription();
        break;
    case 'renew_subscription':
        renewSubscription();
        break;
    case 'cancel_subscription':
        cancelSubscription();
        break;
    case 'update_payment':
        updatePayment();
        break;
    default:
        setErrorMessage('Invalid action.');
}

function addSubscription() {
    global $conn, $customer_id;
    
    $subscription_type_id = intval($_POST['subscription_type_id'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? '';
    $auto_renewal = isset($_POST['auto_renewal']) ? 1 : 0;
    $start_date = $_POST['start_date'] ?? date('Y-m-d');
    
    if ($subscription_type_id <= 0) {
        setErrorMessage('Please select a subscription type.');
    }
    
    if (empty($payment_method)) {
        setErrorMessage('Please select a payment method.');
    }
    
    // Get subscription type details
    $stmt = $conn->prepare('SELECT duration_days, price FROM subscription_types WHERE subscription_type_id = ? AND is_active = 1');
    $stmt->bind_param('i', $subscription_type_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $subscription_type = $result->fetch_assoc();
    $stmt->close();
    
    if (!$subscription_type) {
        setErrorMessage('Invalid subscription type.');
    }
    
    // Calculate end date
    $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $subscription_type['duration_days'] . ' days'));
    $next_payment_date = $end_date;
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update customer subscription details
        $stmt = $conn->prepare('UPDATE customers SET has_subscription = 1, subscription_status = "active", subscription_start_date = ?, subscription_end_date = ?, subscription_type_id = ?, payment_method = ?, auto_renewal = ?, next_payment_date = ? WHERE customer_id = ?');
        $stmt->bind_param('ssisssi', $start_date, $end_date, $subscription_type_id, $payment_method, $auto_renewal, $next_payment_date, $customer_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to update customer subscription.');
        }
        $stmt->close();
        
        // Record payment
        $stmt = $conn->prepare('INSERT INTO subscription_payments (customer_id, subscription_type_id, amount_paid, payment_date, payment_method, payment_status, transaction_reference) VALUES (?, ?, ?, ?, ?, "completed", ?)');
        $transaction_ref = 'SUB_' . date('YmdHis') . '_' . $customer_id;
        $stmt->bind_param('iidsss', $customer_id, $subscription_type_id, $subscription_type['price'], $start_date, $payment_method, $transaction_ref);
        if (!$stmt->execute()) {
            throw new Exception('Failed to record payment.');
        }
        $stmt->close();
        
        // Create alert for renewal reminder (7 days before expiry)
        $alert_date = date('Y-m-d', strtotime($end_date . ' - 7 days'));
        $stmt = $conn->prepare('INSERT INTO subscription_alerts (customer_id, alert_type, message, alert_date) VALUES (?, "renewal_reminder", ?, ?)');
        $message = "Your subscription will expire on " . date('M d, Y', strtotime($end_date)) . ". Consider renewing to continue enjoying our services.";
        $stmt->bind_param('iss', $customer_id, $message, $alert_date);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        setSuccessMessage('Subscription added successfully. Customer can now use their subscription.');
        
    } catch (Exception $e) {
        $conn->rollback();
        setErrorMessage('Failed to add subscription: ' . $e->getMessage());
    }
}

function renewSubscription() {
    global $conn, $customer_id;
    
    $subscription_type_id = intval($_POST['subscription_type_id'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? '';
    $auto_renewal = isset($_POST['auto_renewal']) ? 1 : 0;
    
    if ($subscription_type_id <= 0) {
        setErrorMessage('Please select a subscription type.');
    }
    
    // Get current subscription end date
    $stmt = $conn->prepare('SELECT subscription_end_date FROM customers WHERE customer_id = ?');
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $stmt->close();
    
    if (!$customer || !$customer['subscription_end_date']) {
        setErrorMessage('Customer has no active subscription to renew.');
    }
    
    // Get subscription type details
    $stmt = $conn->prepare('SELECT duration_days, price FROM subscription_types WHERE subscription_type_id = ? AND is_active = 1');
    $stmt->bind_param('i', $subscription_type_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $subscription_type = $result->fetch_assoc();
    $stmt->close();
    
    if (!$subscription_type) {
        setErrorMessage('Invalid subscription type.');
    }
    
    // Calculate new end date (extend from current end date)
    $new_end_date = date('Y-m-d', strtotime($customer['subscription_end_date'] . ' + ' . $subscription_type['duration_days'] . ' days'));
    $next_payment_date = $new_end_date;
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update customer subscription details
        $stmt = $conn->prepare('UPDATE customers SET subscription_status = "active", subscription_end_date = ?, subscription_type_id = ?, payment_method = ?, auto_renewal = ?, next_payment_date = ? WHERE customer_id = ?');
        $stmt->bind_param('sisssi', $new_end_date, $subscription_type_id, $payment_method, $auto_renewal, $next_payment_date, $customer_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to update customer subscription.');
        }
        $stmt->close();
        
        // Record payment
        $stmt = $conn->prepare('INSERT INTO subscription_payments (customer_id, subscription_type_id, amount_paid, payment_date, payment_method, payment_status, transaction_reference) VALUES (?, ?, ?, ?, ?, "completed", ?)');
        $transaction_ref = 'REN_' . date('YmdHis') . '_' . $customer_id;
        $payment_date = date('Y-m-d');
        $stmt->bind_param('iidsss', $customer_id, $subscription_type_id, $subscription_type['price'], $payment_date, $payment_method, $transaction_ref);
        if (!$stmt->execute()) {
            throw new Exception('Failed to record payment.');
        }
        $stmt->close();
        
        // Create alert for renewal reminder (7 days before new expiry)
        $alert_date = date('Y-m-d', strtotime($new_end_date . ' - 7 days'));
        $stmt = $conn->prepare('INSERT INTO subscription_alerts (customer_id, alert_type, message, alert_date) VALUES (?, "renewal_reminder", ?, ?)');
        $message = "Your subscription will expire on " . date('M d, Y', strtotime($new_end_date)) . ". Consider renewing to continue enjoying our services.";
        $stmt->bind_param('iss', $customer_id, $message, $alert_date);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        setSuccessMessage('Subscription renewed successfully.');
        
    } catch (Exception $e) {
        $conn->rollback();
        setErrorMessage('Failed to renew subscription: ' . $e->getMessage());
    }
}

function cancelSubscription() {
    global $conn, $customer_id;
    
    $reason = trim($_POST['reason'] ?? 'Customer requested cancellation');
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update customer subscription status
        $stmt = $conn->prepare('UPDATE customers SET subscription_status = "cancelled", auto_renewal = 0 WHERE customer_id = ?');
        $stmt->bind_param('i', $customer_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to cancel subscription.');
        }
        $stmt->close();
        
        // Create cancellation alert
        $stmt = $conn->prepare('INSERT INTO subscription_alerts (customer_id, alert_type, message, alert_date) VALUES (?, "expired", ?, CURDATE())');
        $message = "Subscription cancelled. Reason: " . $reason;
        $stmt->bind_param('is', $customer_id, $message);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        setSuccessMessage('Subscription cancelled successfully.');
        
    } catch (Exception $e) {
        $conn->rollback();
        setErrorMessage('Failed to cancel subscription: ' . $e->getMessage());
    }
}

function updatePayment() {
    global $conn, $customer_id;
    
    $payment_method = $_POST['payment_method'] ?? '';
    $auto_renewal = isset($_POST['auto_renewal']) ? 1 : 0;
    
    if (empty($payment_method)) {
        setErrorMessage('Please select a payment method.');
    }
    
    $stmt = $conn->prepare('UPDATE customers SET payment_method = ?, auto_renewal = ? WHERE customer_id = ?');
    $stmt->bind_param('sii', $payment_method, $auto_renewal, $customer_id);
    if (!$stmt->execute()) {
        setErrorMessage('Failed to update payment information.');
    }
    $stmt->close();
    
    setSuccessMessage('Payment information updated successfully.');
}
