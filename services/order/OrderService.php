<?php
require_once '../../config/connection.php';
require_once '../../config/app.php';
require_once __DIR__ . '/../notifications/OrderNotifier.php';

class OrderService {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;

        if (!$this->conn) {
            throw new Exception('Database connection not established.');
        }
    }

    public function createOrder(array $data): array
    {
        try {
            mysqli_begin_transaction($this->conn);

            $orderNumber = $this->generateOrderNumber();
            $items = $this->buildItems($data);
            $subtotal = array_sum(array_column($items, 'total_price'));
            $taxAmount = $subtotal * ORDER_TAX_RATE;
            $deliveryFee = ($data['order_type'] === 'delivery') ? ORDER_DELIVERY_FEE : 0.00;
            $totalAmount = $subtotal + $taxAmount + $deliveryFee;

            $orderType = $data['order_type'];
            $deliveryAddress = ($orderType === 'delivery' && !empty($data['delivery_address'])) ? $data['delivery_address'] : null;
            $specialInstructions = $data['special_instructions'] ?? null;

            $query = "INSERT INTO orders (order_number, order_type, order_status, payment_status, payment_method, subtotal, tax_amount, discount_amount, total_amount, delivery_address, delivery_fee, special_instructions)
                      VALUES (?, ?, 'pending', 'pending', 'cash', ?, ?, 0.00, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($this->conn, $query);
            mysqli_stmt_bind_param(
                $stmt,
                'ssddddss',
                $orderNumber,
                $orderType,
                $subtotal,
                $taxAmount,
                $totalAmount,
                $deliveryAddress,
                $deliveryFee,
                $specialInstructions
            );
            mysqli_stmt_execute($stmt);
            $orderId = mysqli_insert_id($this->conn);
            mysqli_stmt_close($stmt);

            foreach ($items as $item) {
                $itemQuery = "INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, total_price, item_status)
                             VALUES (?, ?, ?, ?, ?, 'pending')";
                $itemStmt = mysqli_prepare($this->conn, $itemQuery);
                mysqli_stmt_bind_param(
                    $itemStmt,
                    'iiidd',
                    $orderId,
                    $item['menu_item_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['total_price']
                );
                mysqli_stmt_execute($itemStmt);
                mysqli_stmt_close($itemStmt);
            }

            $customerId = $this->resolveCustomerId($data);
            if ($customerId) {
                $updateQuery = 'UPDATE orders SET customer_id = ? WHERE order_id = ?';
                $updateStmt = mysqli_prepare($this->conn, $updateQuery);
                mysqli_stmt_bind_param($updateStmt, 'ii', $customerId, $orderId);
                mysqli_stmt_execute($updateStmt);
                mysqli_stmt_close($updateStmt);
            }

            mysqli_commit($this->conn);

            $customer = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
            ];

            $order = [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'order_type' => $orderType,
                'total_amount' => $totalAmount,
                'delivery_address' => $deliveryAddress,
                'special_instructions' => $specialInstructions,
            ];

            $notificationItems = array_map(static function (array $item): array {
                return [
                    'name' => $item['item_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ];
            }, $items);

            $notifications = OrderNotifier::notify($order, $customer, $notificationItems);

            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'message' => 'Order placed successfully! Order #' . $orderNumber,
                'whatsapp_url' => $notifications['whatsapp_url'],
                'email_sent' => $notifications['email_sent'],
            ];
        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            return [
                'success' => false,
                'message' => 'Error creating order: ' . $e->getMessage(),
            ];
        }
    }

    private function buildItems(array $data): array
    {
        $items = [];

        if (!isset($data['menu_item_id']) || !is_array($data['menu_item_id'])) {
            return $items;
        }

        foreach ($data['menu_item_id'] as $index => $menuItemId) {
            $quantity = isset($data['quantity'][$index]) ? (int) $data['quantity'][$index] : 1;
            $unitPrice = isset($data['unit_price'][$index]) ? (float) $data['unit_price'][$index] : 0.0;
            $itemName = $data['item_name'][$index] ?? 'Menu item';

            $items[] = [
                'menu_item_id' => (int) $menuItemId,
                'item_name' => $itemName,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $quantity * $unitPrice,
            ];
        }

        return $items;
    }

    private function generateOrderNumber(): string
    {
        return 'ORD' . date('Ymd') . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function resolveCustomerId(array $data): ?int
    {
        if (!empty($data['customer_id'])) {
            return (int) $data['customer_id'];
        }

        $email = $data['email'] ?? '';
        if ($email === '') {
            return null;
        }

        $query = 'SELECT customer_id FROM customers WHERE email = ? AND deleted_at IS NULL LIMIT 1';
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            mysqli_stmt_close($stmt);
            $this->updateCustomerContact((int) $row['customer_id'], $data);
            return (int) $row['customer_id'];
        }
        mysqli_stmt_close($stmt);

        $nameParts = explode(' ', trim($data['name']), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        $insertQuery = 'INSERT INTO customers (first_name, last_name, email, phone, account_status) VALUES (?, ?, ?, ?, \'guest\')';
        $insertStmt = mysqli_prepare($this->conn, $insertQuery);
        mysqli_stmt_bind_param($insertStmt, 'ssss', $firstName, $lastName, $email, $data['phone']);
        mysqli_stmt_execute($insertStmt);
        $customerId = mysqli_insert_id($this->conn);
        mysqli_stmt_close($insertStmt);

        return (int) $customerId;
    }

    private function updateCustomerContact(int $customerId, array $data): void
    {
        $nameParts = explode(' ', trim($data['name']), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        $stmt = mysqli_prepare($this->conn, 'UPDATE customers SET first_name = ?, last_name = ?, phone = ?, updated_at = CURRENT_TIMESTAMP WHERE customer_id = ?');
        mysqli_stmt_bind_param($stmt, 'sssi', $firstName, $lastName, $data['phone'], $customerId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

?>
