<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// PHP functions for badge styling
function getTypeBadgeClass($text) {
    switch (strtolower($text)) {
        case 'delivery':
            return 'text-primary';
        case 'dine_in':
            return 'text-success';
        case 'takeaway':
            return 'text-info';
        case 'online':
            return 'text-warning';
        default:
            return 'text-secondary';
    }
}

function getPaymentBadgeClass($text) {
    switch (strtolower($text)) {
        case 'paid':
            return 'text-success';
        case 'pending':
            return 'text-warning';
        case 'refunded':
            return 'text-danger';
        default:
            return 'text-secondary';
    }
}

function getBadgeClass($text) {
    switch (strtolower($text)) {
        case 'pending':
        case 'preparing':
            return 'text-warning';
        case 'completed':
        case 'paid':
        case 'delivery':
        case 'dine_in':
        case 'takeaway':
            return 'text-success';
        case 'cancelled':
        case 'failed':
            return 'text-danger';
        case 'confirmed':
        case 'ready':
            return 'text-info';
        default:
            return 'text-secondary';
    }
}
?>
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item active">Orders</li>
    </ol>
</nav>
<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Orders</h2>
        </div>
    </div>
    <?php
    if (!empty($_SESSION['notification'])) {
        echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['notification']) . '</div>';
        unset($_SESSION['notification']);
    }
    // Count orders by status
    $status_counts = [
        'all' => 0,
        'pending' => 0,
        'preparing' => 0,
        'ready' => 0,
        'completed' => 0,
        'cancelled' => 0
    ];
    $orders_count_query = mysqli_query($conn, "SELECT order_status, COUNT(*) as cnt FROM orders GROUP BY order_status");
    while ($row = mysqli_fetch_assoc($orders_count_query)) {
        $status = $row['order_status'];
        $cnt = (int)$row['cnt'];
        if (isset($status_counts[$status])) $status_counts[$status] = $cnt;
        $status_counts['all'] += $cnt;
    }
    ?>
    <ul class="nav nav-links mb-3 mb-lg-2 mx-n3" id="order-filters">
        <li class="nav-item">
            <a class="nav-link active" data-filter="all" aria-current="page" href="#">
                All <span class="text-body-tertiary fw-semibold">(<?= $status_counts['all'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="pending" href="#">
                Pending <span class="text-body-tertiary fw-semibold">(<?= $status_counts['pending'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="preparing" href="#">
                Preparing <span class="text-body-tertiary fw-semibold">(<?= $status_counts['preparing'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="ready" href="#">
                Ready <span class="text-body-tertiary fw-semibold">(<?= $status_counts['ready'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="completed" href="#">
                Completed <span class="text-body-tertiary fw-semibold">(<?= $status_counts['completed'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="cancelled" href="#">
                Cancelled <span class="text-body-tertiary fw-semibold">(<?= $status_counts['cancelled'] ?>)</span>
            </a>
        </li>
    </ul>
    <div class="card shadow-sm">
        <div class="card-body" style="margin-left: 15px;">
            <div class="table-responsive" style="overflow-x: auto;">
                <table class="table table-hover mb-0" style="min-width: 1200px;">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 120px;" class="border-0">Order #</th>
                            <th style="min-width: 150px;" class="border-0">Customer</th>
                            <th style="min-width: 80px;" class="border-0">Table</th>
                            <th style="min-width: 100px;" class="border-0">Type</th>
                            <th style="min-width: 100px;" class="border-0">Status</th>
                            <th style="min-width: 100px;" class="border-0">Payment</th>
                            <th style="min-width: 120px;" class="border-0">Amount</th>
                            <th style="min-width: 150px;" class="border-0">Address</th>
                            <th style="min-width: 150px;" class="border-0">Date</th>
                            <th style="min-width: 100px;" class="border-0 text-end">Actions</th>
                        </tr>
                    </thead>
            <tbody id="orders-table-body">
                <?php
                $orders_query = mysqli_query($conn, "
                    SELECT o.*, c.first_name, c.last_name, c.email, c.phone, t.table_number
                    FROM orders o
                    LEFT JOIN customers c ON o.customer_id = c.customer_id AND c.deleted_at IS NULL
                    LEFT JOIN restaurant_tables t ON o.table_id = t.table_id AND t.deleted_at IS NULL
                    WHERE o.deleted_at IS NULL
                    ORDER BY o.order_date DESC
                ");
                $has_orders = false;
                while ($order = mysqli_fetch_assoc($orders_query)) {
                    $has_orders = true;
                    $customer_name = $order['first_name'] ? $order['first_name'] . ' ' . $order['last_name'] : 'Walk-in Customer';
                    $table_number = $order['table_number'] ? $order['table_number'] : '-';
                    $type_class = getTypeBadgeClass($order['order_type']);
                    $status_class = getBadgeClass($order['order_status']);
                    $payment_badge = '<span class="badge badge-soft-' . ($order['payment_status'] == 'paid' ? 'success' : ($order['payment_status'] == 'pending' ? 'warning' : 'danger')) . '">' . ucfirst($order['payment_status']) . '</span>';
                ?>
                <tr data-status="<?= $order['order_status'] ?>">
                    <td class="white-space-nowrap">
                        <a href="#" class="fw-bold text-900" onclick="viewOrderDetails(<?= $order['order_id'] ?>)"><?php echo $order['order_number']; ?></a>
                    </td>
                    <td class="white-space-nowrap"><?php echo htmlspecialchars($customer_name); ?></td>
                    <td class="white-space-nowrap"><?php echo htmlspecialchars($table_number); ?></td>
                    <td class="white-space-nowrap">
                        <span class="<?php echo $type_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $order['order_type'])); ?></span>
                    </td>
                    <td class="white-space-nowrap">
                        <span class="<?php echo $status_class; ?>" id="status-<?= $order['order_id'] ?>"><?php echo ucfirst($order['order_status']); ?></span>
                    </td>
                    <td class="white-space-nowrap">
                        <span class="<?php echo getPaymentBadgeClass($order['payment_status']); ?>" id="payment-<?= $order['order_id'] ?>"><?php echo ucfirst($order['payment_status']); ?></span>
                    </td>
                    <td class="white-space-nowrap fw-bold">Frw <?php echo number_format($order['total_amount'], 2); ?></td>
                    <td class="white-space-nowrap">
                        <?php 
                        if ($order['order_type'] === 'delivery' && $order['delivery_address']) {
                            echo htmlspecialchars(substr($order['delivery_address'], 0, 30)) . (strlen($order['delivery_address']) > 30 ? '...' : '');
                        } elseif ($order['order_type'] === 'delivery') {
                            echo '<span class="text-muted">No address</span>';
                        } else {
                            echo '<span class="text-muted">-</span>';
                        }
                        ?>
                    </td>
                    <td class="white-space-nowrap"><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                    <td class="text-end white-space-nowrap">
                        <div class="dropdown">
                            <button class="btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Actions
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="viewOrderDetails(<?= $order['order_id'] ?>)">View Details</a></li>
                                <?php if ($order['order_status'] != 'completed' && $order['order_status'] != 'cancelled'): ?>
                                    <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?= $order['order_id'] ?>, 'confirmed')">Confirm</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?= $order['order_id'] ?>, 'preparing')">Start Preparing</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?= $order['order_id'] ?>, 'ready')">Mark Ready</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="updateOrderStatus(<?= $order['order_id'] ?>, 'completed')">Complete</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="cancelOrder(<?= $order['order_id'] ?>)">Cancel Order</a></li>
                                <?php endif; ?>
                                <?php if ($order['payment_status'] == 'pending'): ?>
                                    <li><a class="dropdown-item" href="#" onclick="markAsPaid(<?= $order['order_id'] ?>)">Mark as Paid</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php }
                if (!$has_orders): ?>
                <tr><td colspan="10" class="text-center p-4"><div class='alert alert-info'>No orders found. <a href="new-order" class="alert-link">Create your first order</a></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
            </div>
        </div>
    </div>
<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Order Modal -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cancelOrderForm">
                <input type="hidden" name="order_id" id="cancelOrderId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cancelReason" class="form-label">Cancellation Reason</label>
                        <textarea class="form-control" id="cancelReason" name="reason" rows="3" required placeholder="Enter reason for cancellation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Cancel Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter logic for orders (client-side only)
    document.querySelectorAll('#order-filters .nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#order-filters .nav-link').forEach(function(l) { l.classList.remove('active'); });
            link.classList.add('active');
            var filter = link.getAttribute('data-filter');
            document.querySelectorAll('#orders-table-body tr').forEach(function(row) {
                if (filter === 'all') {
                    row.style.display = '';
                } else if (row.getAttribute('data-status') === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});

function viewOrderDetails(orderId) {
    fetch('./src/services/orders/get_order_details.php?id=' + orderId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayOrderDetails(data.order);
                new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading order details');
        });
}

function displayOrderDetails(order) {
    let html = `
        <div class="row mb-3">
            <div class="col-md-6">
                <h6>Order Information</h6>
                <p><strong>Order #:</strong> ${order.order_number}</p>
                <p><strong>Date:</strong> ${new Date(order.order_date).toLocaleString()}</p>
                <p><strong>Type:</strong> ${order.order_type.replace('_', ' ').toUpperCase()}</p>
                <p><strong>Status:</strong> <span class="badge badge-soft-${getStatusClass(order.order_status)}">${order.order_status.toUpperCase()}</span></p>
                <p><strong>Payment Status:</strong> <span class="badge badge-soft-${order.payment_status === 'paid' ? 'success' : 'warning'}">${order.payment_status.toUpperCase()}</span></p>
            </div>
            <div class="col-md-6">
                <h6>Customer Information</h6>
                <p><strong>Name:</strong> ${order.first_name ? order.first_name + ' ' + order.last_name : 'Walk-in Customer'}</p>
                <p><strong>Email:</strong> ${order.email || 'N/A'}</p>
                <p><strong>Phone:</strong> ${order.phone || 'N/A'}</p>
                <p><strong>Table:</strong> ${order.table_number || 'N/A'}</p>
                <p><strong>Address:</strong> ${order.delivery_address || (order.order_type === 'delivery' ? 'No address provided' : 'N/A')}</p>
            </div>
        </div>
        
        <h6>Order Items</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    order.items.forEach(item => {
        html += `
            <tr>
                <td>${item.item_name}</td>
                <td>${item.quantity}</td>
                <td>Frw ${parseFloat(item.unit_price).toFixed(2)}</td>
                <td>Frw ${parseFloat(item.total_price).toFixed(2)}</td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <p><strong>Subtotal:</strong> Frw ${parseFloat(order.subtotal).toFixed(2)}</p>
                <p><strong>Tax:</strong> Frw ${parseFloat(order.tax_amount).toFixed(2)}</p>
                <p><strong>Delivery Fee:</strong> Frw ${parseFloat(order.delivery_fee).toFixed(2)}</p>
            </div>
            <div class="col-md-6">
                <h5><strong>Total Amount:</strong> Frw ${parseFloat(order.total_amount).toFixed(2)}</h5>
            </div>
        </div>
        
        ${order.special_instructions ? `
            <div class="mt-3">
                <h6>Special Instructions</h6>
                <p>${order.special_instructions}</p>
            </div>
        ` : ''}
    `;
    
    document.getElementById('orderDetailsContent').innerHTML = html;
}

function updateOrderStatus(orderId, newStatus) {
    if (!confirm(`Are you sure you want to update order status to "${newStatus}"?`)) {
        return;
    }
    
    fetch('./src/services/orders/update_order_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `order_id=${orderId}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the status badge
            const statusElement = document.getElementById(`status-${orderId}`);
            if (statusElement) {
                statusElement.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                statusElement.className = `badge badge-soft-${getStatusClass(newStatus)}`;
            }
            
            // Update row data-status attribute
            const row = statusElement.closest('tr');
            if (row) {
                row.setAttribute('data-status', newStatus);
            }
            
            // Show success message
            showNotification('Order status updated successfully', 'success');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating order status');
    });
}

function markAsPaid(orderId) {
    if (!confirm('Are you sure you want to mark this order as paid?')) {
        return;
    }
    
    fetch('./src/services/orders/update_payment_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `order_id=${orderId}&payment_status=paid`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the payment status badge
            const paymentElement = document.getElementById(`payment-${orderId}`);
            if (paymentElement) {
                paymentElement.textContent = 'Paid';
                paymentElement.className = 'badge badge-soft-success';
            }
            
            showNotification('Payment status updated successfully', 'success');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating payment status');
    });
}

function cancelOrder(orderId) {
    document.getElementById('cancelOrderId').value = orderId;
    new bootstrap.Modal(document.getElementById('cancelOrderModal')).show();
}

document.getElementById('cancelOrderForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const orderId = document.getElementById('cancelOrderId').value;
    const reason = document.getElementById('cancelReason').value;
    
    fetch('./src/services/orders/cancel_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `order_id=${orderId}&reason=${encodeURIComponent(reason)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the status badge
            const statusElement = document.getElementById(`status-${orderId}`);
            if (statusElement) {
                statusElement.textContent = 'Cancelled';
                statusElement.className = 'badge badge-soft-danger';
            }
            
            // Update row data-status attribute
            const row = statusElement.closest('tr');
            if (row) {
                row.setAttribute('data-status', 'cancelled');
            }
            
            bootstrap.Modal.getInstance(document.getElementById('cancelOrderModal')).hide();
            showNotification('Order cancelled successfully', 'success');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error cancelling order');
    });
});

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}
</script>
