<?php
require_once '../../config/connection.php';

class OrderService {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
        
        if (!$this->conn) {
            throw new Exception("Database connection not established. Please check your database configuration and ensure the database server is running.");
        }
    }

    public function createOrder($data) {
        try {
            // Start transaction
            mysqli_begin_transaction($this->conn);

            // Generate order number
            $orderNumber = $this->generateOrderNumber();
            
            // Calculate totals
            $subtotal = 0;
            $items = [];
            
            if (isset($data['menu_item_id']) && is_array($data['menu_item_id'])) {
                foreach ($data['menu_item_id'] as $index => $menuItemId) {
                    $quantity = isset($data['quantity'][$index]) ? intval($data['quantity'][$index]) : 1;
                    $unitPrice = isset($data['unit_price'][$index]) ? floatval($data['unit_price'][$index]) : 0;
                    
                    $items[] = [
                        'menu_item_id' => intval($menuItemId),
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $quantity * $unitPrice
                    ];
                    
                    $subtotal += $quantity * $unitPrice;
                }
            }
            
            $taxAmount = $subtotal * 0.10; // 10% tax
            $deliveryFee = ($data['order_type'] === 'delivery') ? 5.00 : 0.00;
            $totalAmount = $subtotal + $taxAmount + $deliveryFee;
            
            // Insert order
            $query = "INSERT INTO orders (order_number, order_type, order_status, payment_status, payment_method, subtotal, tax_amount, discount_amount, total_amount, delivery_address, delivery_fee, special_instructions) 
                      VALUES (?, ?, 'pending', 'pending', 'cash', ?, ?, 0.00, ?, ?, ?, ?)";
            
            $orderType = $data['order_type'];
            $deliveryAddress = ($orderType === 'delivery' && isset($data['delivery_address'])) ? $data['delivery_address'] : null;
            $specialInstructions = isset($data['special_instructions']) ? $data['special_instructions'] : null;
            
            $stmt = mysqli_prepare($this->conn, $query);
            mysqli_stmt_bind_param($stmt, 'ssddddss', 
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

            // Insert order items
            foreach ($items as $item) {
                $itemQuery = "INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, total_price, item_status) 
                             VALUES (?, ?, ?, ?, ?, 'pending')";
                $itemStmt = mysqli_prepare($this->conn, $itemQuery);
                mysqli_stmt_bind_param($itemStmt, 'iiidd', 
                    $orderId,
                    $item['menu_item_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['total_price']
                );
                mysqli_stmt_execute($itemStmt);
                mysqli_stmt_close($itemStmt);
            }

            // Create or get customer
            $customerId = $this->getOrCreateCustomer($data);
            
            // Update order with customer_id
            if ($customerId) {
                $updateQuery = "UPDATE orders SET customer_id = ? WHERE order_id = ?";
                $updateStmt = mysqli_prepare($this->conn, $updateQuery);
                mysqli_stmt_bind_param($updateStmt, 'ii', $customerId, $orderId);
                mysqli_stmt_execute($updateStmt);
                mysqli_stmt_close($updateStmt);
            }

            mysqli_commit($this->conn);

            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'message' => 'Order placed successfully! Order #' . $orderNumber
            ];

        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            return [
                'success' => false,
                'message' => 'Error creating order: ' . $e->getMessage()
            ];
        }
    }

    public function getAvailableMenuItems() {
        try {
            $query = "SELECT menu_item_id, item_name, price, description, image_url FROM menu_items WHERE is_available = 1 ORDER BY category_id, item_name";
            $result = mysqli_query($this->conn, $query);
            
            if ($result) {
                $menuItems = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $menuItems[] = $row;
                }
                return $menuItems;
            }
            return [];
        } catch (Exception $e) {
            return [];
        }
    }

    private function generateOrderNumber() {
        $prefix = 'ORD';
        $date = date('Ymd');
        $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $date . $random;
    }

    private function getOrCreateCustomer($data) {
        try {
            // Check if customer exists by email
            $email = $data['email'];
            $query = "SELECT customer_id FROM customers WHERE email = ? AND deleted_at IS NULL LIMIT 1";
            $stmt = mysqli_prepare($this->conn, $query);
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                mysqli_stmt_close($stmt);
                return $row['customer_id'];
            }
            mysqli_stmt_close($stmt);
            
            // Create new customer
            $nameParts = explode(' ', $data['name'], 2);
            $firstName = $nameParts[0];
            $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
            
            $insertQuery = "INSERT INTO customers (first_name, last_name, email, phone) VALUES (?, ?, ?, ?)";
            $insertStmt = mysqli_prepare($this->conn, $insertQuery);
            mysqli_stmt_bind_param($insertStmt, 'ssss', $firstName, $lastName, $email, $data['phone']);
            mysqli_stmt_execute($insertStmt);
            $customerId = mysqli_insert_id($this->conn);
            mysqli_stmt_close($insertStmt);
            
            return $customerId;
        } catch (Exception $e) {
            error_log("Error creating/getting customer: " . $e->getMessage());
            return null;
        }
    }
}
?>

