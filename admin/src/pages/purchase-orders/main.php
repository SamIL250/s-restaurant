<style>
.badge-soft-secondary {
    background-color: #e9ecef;
    color: #6c757d;
}

.badge-soft-warning {
    background-color: #fff3cd;
    color: #856404;
}

.badge-soft-success {
    background-color: #d1e7dd;
    color: #0f5132;
}

.badge-soft-danger {
    background-color: #f8d7da;
    color: #721c24;
}

/* PO Number field styling */
#poNumber {
    background-color: #f8f9fa;
    border: 2px solid #dee2e6;
    color: #495057;
    font-weight: 600;
    cursor: not-allowed;
}

#poNumber:focus {
    box-shadow: none;
    border-color: #dee2e6;
}

/* Add a visual indicator for auto-generated field */
.auto-generated-field {
    position: relative;
}

.auto-generated-field::after {
    content: "🔒";
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
    opacity: 0.6;
}

/* Status cards styling */
.status-card {
    transition: all 0.3s ease;
}

.status-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* Action buttons in status cards */
.status-card .btn-sm {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
}

/* Modal enhancements */
.modal-lg .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

/* Status badge enhancements */
.badge.fs-6 {
    font-size: 0.9rem !important;
    padding: 0.5rem 1rem;
}

/* Notification styling */
.alert.position-fixed {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border: none;
}

/* Items section styling */
.po-item-row {
    background-color: #f8f9fa;
    transition: all 0.2s ease;
}

.po-item-row:hover {
    background-color: #e9ecef;
    border-color: #6c757d !important;
}

.po-item-row .form-control:focus,
.po-item-row .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

#poTotalAmount {
    color: #0d6efd;
    font-size: 1.1rem;
}

.remove-item {
    transition: all 0.2s ease;
}

.remove-item:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}
</style>
<nav class="mb-3" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
        <li class="breadcrumb-item active">Purchase Orders</li>
    </ol>
</nav>
<div class="mb-9">
    <div class="row g-3 mb-4">
        <div class="col-auto">
            <h2 class="mb-0">Purchase Orders</h2>
        </div>
        <div class="col-auto ms-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPOModal"><span class="fas fa-plus me-2"></span>New Purchase Order</button>
        </div>
    </div>
    <?php
    if (!empty($_SESSION['notification'])) {
        echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['notification']) . '</div>';
        unset($_SESSION['notification']);
    }
    // Count POs by status
    $status_counts = [
        'all' => 0,
        'draft' => 0,
        'sent' => 0,
        'received' => 0,
        'cancelled' => 0
    ];
    $po_count_query = mysqli_query($conn, "SELECT po_status, COUNT(*) as cnt FROM purchase_orders GROUP BY po_status");
    while ($row = mysqli_fetch_assoc($po_count_query)) {
        $status = $row['po_status'];
        $cnt = (int)$row['cnt'];
        if (isset($status_counts[$status])) $status_counts[$status] = $cnt;
        $status_counts['all'] += $cnt;
    }
    ?>
    <ul class="nav nav-links mb-3 mb-lg-2 mx-n3" id="po-filters">
        <li class="nav-item">
            <a class="nav-link active" data-filter="all" aria-current="page" href="#">
                All <span class="text-body-tertiary fw-semibold">(<?= $status_counts['all'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="draft" href="#">
                Draft <span class="text-body-tertiary fw-semibold">(<?= $status_counts['draft'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="sent" href="#">
                Sent <span class="text-body-tertiary fw-semibold">(<?= $status_counts['sent'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="received" href="#">
                Received <span class="text-body-tertiary fw-semibold">(<?= $status_counts['received'] ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-filter="cancelled" href="#">
                Cancelled <span class="text-body-tertiary fw-semibold">(<?= $status_counts['cancelled'] ?>)</span>
            </a>
        </li>
    </ul>
    <div class="table-responsive bg-body-emphasis border-top border-bottom border-translucent position-relative top-1 p-3">
        <table class="table fs-9 mb-0">
            <thead>
                <tr>
                    <th>PO #</th>
                    <th>Supplier</th>
                    <th>Order Date</th>
                    <th>Expected Delivery</th>
                    <th>Status</th>
                    <th>Total Amount</th>
                    <th>Created By</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="po-table-body">
                <?php
                $po_query = mysqli_query($conn, "
                    SELECT po.*, s.supplier_name, u.first_name, u.last_name
                    FROM purchase_orders po
                    LEFT JOIN suppliers s ON po.supplier_id = s.supplier_id
                    LEFT JOIN users u ON po.user_id = u.user_id
                    ORDER BY po.created_at DESC
                ");
                $has_pos = false;
                while ($row = mysqli_fetch_assoc($po_query)) {
                    $has_pos = true;
                    $status_class = '';
                    switch ($row['po_status']) {
                        case 'draft': $status_class = 'badge-soft-secondary'; break;
                        case 'sent': $status_class = 'badge-soft-warning'; break;
                        case 'received': $status_class = 'badge-soft-success'; break;
                        case 'cancelled': $status_class = 'badge-soft-danger'; break;
                        default: $status_class = 'badge-soft-secondary'; break;
                    }
                    $created_by = $row['first_name'] ? $row['first_name'] . ' ' . $row['last_name'] : 'Unknown';
                ?>
                <tr data-status="<?= $row['po_status'] ?>">
                    <td class="fw-bold"><?= htmlspecialchars($row['po_number']) ?></td>
                    <td><?= htmlspecialchars($row['supplier_name']) ?></td>
                    <td><?= date('Y-m-d', strtotime($row['order_date'])) ?></td>
                    <td><?= $row['expected_delivery_date'] ? date('Y-m-d', strtotime($row['expected_delivery_date'])) : '-' ?></td>
                    <td><span class="badge <?= $status_class ?>"><?= ucfirst($row['po_status']) ?></span></td>
                    <td>Frw <?= number_format($row['total_amount'] ?? 0, 2) ?></td>
                    <td><?= htmlspecialchars($created_by) ?></td>
                    <td class="text-end">
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-info me-1" title="View Details" data-bs-toggle="modal" data-bs-target="#poDetailsModal<?= (int)$row['purchase_order_id'] ?>"><i class="fa fa-eye"></i></button>
                            <?php if ($row['po_status'] == 'draft'): ?>
                            <button class="btn btn-sm btn-outline-warning me-1" title="Mark as Sent" onclick="updatePOStatus(<?= (int)$row['purchase_order_id'] ?>, 'sent')"><i class="fa fa-paper-plane"></i></button>
                            <?php elseif ($row['po_status'] == 'sent'): ?>
                            <button class="btn btn-sm btn-outline-success me-1" title="Mark as Received" onclick="updatePOStatus(<?= (int)$row['purchase_order_id'] ?>, 'received')"><i class="fa fa-check"></i></button>
                            <?php endif; ?>
                            <?php if (in_array($row['po_status'], ['draft', 'sent'])): ?>
                            <button class="btn btn-sm btn-outline-danger me-1" title="Cancel" onclick="updatePOStatus(<?= (int)$row['purchase_order_id'] ?>, 'cancelled')"><i class="fa fa-times"></i></button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php }
                if (!$has_pos): ?>
                <tr><td colspan="8"><div class='alert alert-warning text-center p-2 rounded-2 mt-2 mb-2'>No purchase orders found</div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Purchase Order Detail Modals -->
<?php
// Reset the query to create modals
mysqli_data_seek($po_query, 0);
while ($row = mysqli_fetch_assoc($po_query)) {
    $status_class = '';
    switch ($row['po_status']) {
        case 'draft': $status_class = 'badge-soft-secondary'; break;
        case 'sent': $status_class = 'badge-soft-warning'; break;
        case 'received': $status_class = 'badge-soft-success'; break;
        case 'cancelled': $status_class = 'badge-soft-danger'; break;
        default: $status_class = 'badge-soft-secondary'; break;
    }
    $created_by = $row['first_name'] ? $row['first_name'] . ' ' . $row['last_name'] : 'Unknown';
?>
<div class="modal fade" id="poDetailsModal<?= (int)$row['purchase_order_id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Purchase Order #<?= htmlspecialchars($row['po_number']) ?> Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Supplier:</strong> <?= htmlspecialchars($row['supplier_name']) ?></p>
                        <p><strong>Order Date:</strong> <?= date('M d, Y', strtotime($row['order_date'])) ?></p>
                        <p><strong>Status:</strong> <span class="badge <?= $status_class ?>"><?= ucfirst($row['po_status']) ?></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Expected Delivery:</strong> <?= $row['expected_delivery_date'] ? date('M d, Y', strtotime($row['expected_delivery_date'])) : 'Not specified' ?></p>
                        <p><strong>Total Amount:</strong> Frw <?= number_format($row['total_amount'] ?? 0, 2) ?></p>
                        <p><strong>Created By:</strong> <?= htmlspecialchars($created_by) ?></p>
                    </div>
                </div>
                <?php if (!empty($row['notes'])): ?>
                <div class="mt-3">
                    <p><strong>Notes:</strong></p>
                    <p><?= nl2br(htmlspecialchars($row['notes'])) ?></p>
                </div>
                <?php endif; ?>
                <div class="mt-3">
                    <h6>Order Items</h6>
                    <?php
                    // Get PO items
                    $po_items_query = mysqli_prepare($conn, "
                        SELECT poi.*, i.item_name
                        FROM purchase_order_items poi
                        LEFT JOIN inventory_items i ON poi.item_id = i.item_id
                        WHERE poi.purchase_order_id = ? AND poi.deleted_at IS NULL
                    ");
                    $po_items_query->bind_param('i', $row['purchase_order_id']);
                    $po_items_query->execute();
                    $po_items_result = $po_items_query->get_result();
                    
                    if ($po_items_result->num_rows > 0):
                    ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Unit Cost</th>
                                    <th>Total Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_amount = 0;
                                while ($item = $po_items_result->fetch_assoc()): 
                                    $item_total = $item['quantity_ordered'] * $item['unit_cost'];
                                    $total_amount += $item_total;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                                    <td><?= $item['quantity_ordered'] ?></td>
                                    <td>Frw <?= number_format($item['unit_cost'], 2) ?></td>
                                    <td>Frw <?= number_format($item_total, 2) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end">Total Amount:</th>
                                    <th>Frw <?= number_format($total_amount, 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">No items added to this purchase order.</p>
                    <?php 
                    endif;
                    $po_items_query->close();
                    ?>
                </div>
                
                <!-- Status Information Section -->
                <div class="mt-4">
                    <h6>Current Status & Actions</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light status-card">
                                <div class="card-body">
                                    <h6 class="card-title">Current Status</h6>
                                    <span class="badge <?= $status_class ?> fs-6"><?= ucfirst($row['po_status']) ?></span>
                                    <p class="text-muted mt-2 mb-0">
                                        <?php
                                        switch($row['po_status']) {
                                            case 'draft': echo 'PO is in draft mode and can be sent to supplier'; break;
                                            case 'sent': echo 'PO has been sent to supplier and is awaiting delivery'; break;
                                            case 'received': echo 'PO items have been received and processed'; break;
                                            case 'cancelled': echo 'PO has been cancelled and is no longer active'; break;
                                            default: echo 'Status information not available'; break;
                                        }
                                        ?>
                                    </p>
                                    <small class="text-muted">
                                        Last updated: <?= date('M d, Y H:i', strtotime($row['updated_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light status-card">
                                <div class="card-body">
                                    <h6 class="card-title">Available Actions</h6>
                                    <div class="d-grid gap-2">
                                        <?php if ($row['po_status'] == 'draft'): ?>
                                        <button type="button" class="btn btn-warning btn-sm" onclick="updatePOStatus(<?= (int)$row['purchase_order_id'] ?>, 'sent')">
                                            <i class="fa fa-paper-plane me-1"></i>Send to Supplier
                                        </button>
                                        <button type="button" class="btn btn-info btn-sm" onclick="sendPOEmail(<?= (int)$row['purchase_order_id'] ?>)">
                                            <i class="fa fa-envelope me-1"></i>Send Email
                                        </button>
                                        <?php elseif ($row['po_status'] == 'sent'): ?>
                                        <button type="button" class="btn btn-success btn-sm" onclick="updatePOStatus(<?= (int)$row['purchase_order_id'] ?>, 'received')">
                                            <i class="fa fa-check me-1"></i>Mark as Received
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="updatePOStatus(<?= (int)$row['purchase_order_id'] ?>, 'draft')">
                                            <i class="fa fa-undo me-1"></i>Return to Draft
                                        </button>
                                        <?php elseif ($row['po_status'] == 'received'): ?>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="updatePOStatus(<?= (int)$row['purchase_order_id'] ?>, 'sent')">
                                            <i class="fa fa-undo me-1"></i>Return to Sent
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($row['po_status'], ['draft', 'sent'])): ?>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="updatePOStatus(<?= (int)$row['purchase_order_id'] ?>, 'cancelled')">
                                            <i class="fa fa-times me-1"></i>Cancel PO
                                        </button>
                                        <?php elseif ($row['po_status'] == 'cancelled'): ?>
                                        <button type="button" class="btn btn-success btn-sm" onclick="updatePOStatus(<?= (int)$row['purchase_order_id'] ?>, 'draft')">
                                            <i class="fa fa-undo me-1"></i>Reactivate PO
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex justify-content-between w-100">
                    <div>
                        <?php if ($row['po_status'] == 'draft'): ?>
                        <button type="button" class="btn btn-warning me-2" onclick="updatePOStatus(<?= (int)$row['purchase_order_id'] ?>, 'sent')">
                            <i class="fa fa-paper-plane me-1"></i>Mark as Sent
                        </button>
                        <button type="button" class="btn btn-info me-2" onclick="sendPOEmail(<?= (int)$row['purchase_order_id'] ?>)">
                            <i class="fa fa-envelope me-1"></i>Send Email
                        </button>
                        <?php elseif ($row['po_status'] == 'sent'): ?>
                        <button type="button" class="btn btn-success me-2" onclick="updatePOStatus(<?= (int)$row['purchase_order_id'] ?>, 'received')">
                            <i class="fa fa-check me-1"></i>Mark as Received
                        </button>
                        <button type="button" class="btn btn-secondary me-2" onclick="updatePOStatus(<?= (int)$row['purchase_order_id'] ?>, 'draft')">
                            <i class="fa fa-undo me-1"></i>Undo to Draft
                        </button>
                        <?php elseif ($row['po_status'] == 'received'): ?>
                        <button type="button" class="btn btn-secondary me-2" onclick="updatePOStatus(<?= (int)$row['purchase_order_id'] ?>, 'sent')">
                            <i class="fa fa-undo me-1"></i>Undo to Sent
                        </button>
                        <?php endif; ?>
                        
                        <?php if (in_array($row['po_status'], ['draft', 'sent'])): ?>
                        <button type="button" class="btn btn-danger me-2" onclick="updatePOStatus(<?= (int)$row['purchase_order_id'] ?>, 'cancelled')">
                            <i class="fa fa-times me-1"></i>Cancel PO
                        </button>
                        <?php elseif ($row['po_status'] == 'cancelled'): ?>
                        <button type="button" class="btn btn-success me-2" onclick="updatePOStatus(<?= (int)$row['purchase_order_id'] ?>, 'draft')">
                            <i class="fa fa-undo me-1"></i>Uncancel PO
                        </button>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<!-- Add Purchase Order Modal -->
<div class="modal fade" id="addPOModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="src/services/purchase_orders/add_purchase_order.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">New Purchase Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">PO Number</label>
                        <div class="input-group">
                            <input type="text" class="form-control auto-generated-field" name="po_number" id="poNumber" readonly required>
                            <span class="input-group-text" id="poNumberStatus">
                                <i class="fas fa-spinner fa-spin" id="poNumberSpinner" style="display: none;"></i>
                                <i class="fas fa-lock" id="poNumberLock"></i>
                            </span>
                        </div>
                        <small class="text-muted">PO number is automatically generated</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Supplier</label>
                        <select class="form-select" name="supplier_id" required>
                            <option value="">Select Supplier</option>
                            <?php
                            $suppliers_query = mysqli_query($conn, "SELECT supplier_id, supplier_name FROM suppliers WHERE is_active = 1 ORDER BY supplier_name");
                            while ($supplier = mysqli_fetch_assoc($suppliers_query)) {
                                echo '<option value="' . (int)$supplier['supplier_id'] . '">' . htmlspecialchars($supplier['supplier_name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Expected Delivery Date</label>
                        <input type="date" class="form-control" name="expected_delivery_date">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                    
                    <!-- Items Section -->
                    <div class="mb-3">
                        <label class="form-label">Order Items</label>
                        <div id="poItemsContainer">
                            <div class="po-item-row border rounded p-3 mb-2">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <select class="form-select" name="items[0][item_id]" required>
                                            <option value="">Select Item</option>
                                            <?php
                                            $items_query = mysqli_query($conn, "SELECT item_id, item_name, unit_cost FROM inventory_items WHERE is_active = 1 ORDER BY item_name");
                                            while ($item = mysqli_fetch_assoc($items_query)) {
                                                echo '<option value="' . (int)$item['item_id'] . '" data-cost="' . $item['unit_cost'] . '">' . htmlspecialchars($item['item_name']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" class="form-control" name="items[0][quantity]" placeholder="Qty" min="0.01" step="0.01" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" class="form-control" name="items[0][unit_cost]" placeholder="Unit Cost" min="0.01" step="0.01" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control" name="items[0][total_cost]" placeholder="Total" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-item" style="display: none;">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addItemBtn">
                            <i class="fa fa-plus me-1"></i>Add Item
                        </button>
                        <div class="mt-2">
                            <strong>Total Amount: <span id="poTotalAmount">Frw 0.00</span></strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Purchase Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updatePOStatus(poId, status) {
    const statusMessages = {
        'draft': 'mark as Draft',
        'sent': 'mark as Sent',
        'received': 'mark as Received',
        'cancelled': 'Cancel'
    };
    
    const actionMessages = {
        'draft': 'This will undo the PO back to draft status. Are you sure?',
        'sent': 'This will mark the PO as sent to the supplier. Are you sure?',
        'received': 'This will mark the PO as received. Are you sure?',
        'cancelled': 'This will cancel the PO. This action can be undone later. Are you sure?'
    };
    
    const statusText = statusMessages[status] || status;
    const actionMessage = actionMessages[status] || `Are you sure you want to ${statusText} this purchase order?`;
    
    if (confirm(actionMessage)) {
        const formData = new FormData();
        formData.append('purchase_order_id', poId);
        formData.append('po_status', status);
        
        // Show loading state
        const buttons = document.querySelectorAll(`[onclick*="updatePOStatus(${poId}"]`);
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Updating...';
        });
        
        fetch('src/services/purchase_orders/update_po_status.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showNotification('PO status updated successfully!', 'success');
                // Close modal and reload page after a short delay
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showNotification(data.message || 'Failed to update status.', 'error');
                // Re-enable buttons
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.innerHTML = getButtonText(poId, status);
                });
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            showNotification('An error occurred while updating the status.', 'error');
            // Re-enable buttons
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.innerHTML = getButtonText(poId, status);
            });
        });
    }
}

// Helper function to get button text based on current status
function getButtonText(poId, status) {
    const buttonTexts = {
        'draft': '<i class="fa fa-undo me-1"></i>Undo to Draft',
        'sent': '<i class="fa fa-paper-plane me-1"></i>Mark as Sent',
        'received': '<i class="fa fa-check me-1"></i>Mark as Received',
        'cancelled': '<i class="fa fa-undo me-1"></i>Uncancel PO'
    };
    return buttonTexts[status] || 'Update Status';
}

// Function to show notifications
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Function to send purchase order email
function sendPOEmail(poId) {
    if (confirm('Are you sure you want to send this purchase order email to the supplier?')) {
        // Show loading state
        const buttons = document.querySelectorAll(`[onclick*="sendPOEmail(${poId}"]`);
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i>Sending...';
        });
        
        const formData = new FormData();
        formData.append('purchase_order_id', poId);
        
        fetch('src/services/purchase_orders/send_po_email_simple.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification('Purchase order email sent successfully!', 'success');
                
                // If status was updated, reload the page after a delay
                if (data.data && data.data.status_updated) {
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
            } else {
                showNotification(data.message || 'Failed to send email.', 'error');
                // Re-enable buttons
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-envelope me-1"></i>Send Email';
                });
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            showNotification('An error occurred while sending the email.', 'error');
            // Re-enable buttons
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-envelope me-1"></i>Send Email';
            });
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Filter logic for purchase orders (client-side only)
    document.querySelectorAll('#po-filters .nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#po-filters .nav-link').forEach(function(l) { l.classList.remove('active'); });
            link.classList.add('active');
            var filter = link.getAttribute('data-filter');
            document.querySelectorAll('#po-table-body tr').forEach(function(row) {
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

    // Auto-generate PO number when modal opens
    const addPOModal = document.getElementById('addPOModal');
    if (addPOModal) {
        addPOModal.addEventListener('show.bs.modal', function() {
            generateNextPONumber();
        });
        
        // Reset form when modal is hidden
        addPOModal.addEventListener('hidden.bs.modal', function() {
            const form = addPOModal.querySelector('form');
            if (form) {
                form.reset();
            }
            // Clear the PO number field
            document.getElementById('poNumber').value = '';
        });
    }
});

// Function to generate next PO number
function generateNextPONumber() {
    const poNumberField = document.getElementById('poNumber');
    const spinner = document.getElementById('poNumberSpinner');
    const lock = document.getElementById('poNumberLock');
    
    // Show loading state
    poNumberField.value = 'Generating...';
    spinner.style.display = 'inline-block';
    lock.style.display = 'none';
    
    fetch('src/services/purchase_orders/get_next_po_number.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                poNumberField.value = data.data.next_po_number;
                // Show success state briefly
                poNumberField.style.borderColor = '#28a745';
                setTimeout(() => {
                    poNumberField.style.borderColor = '#dee2e6';
                }, 1000);
            } else {
                console.error('Failed to generate PO number:', data.message);
                // Fallback to a timestamp-based number
                const timestamp = Date.now();
                poNumberField.value = 'PO' + timestamp.toString().slice(-6);
                poNumberField.style.borderColor = '#ffc107';
            }
        })
        .catch(error => {
            console.error('Error generating PO number:', error);
            // Fallback to a timestamp-based number
            const timestamp = Date.now();
            poNumberField.value = 'PO' + timestamp.toString().slice(-6);
            poNumberField.style.borderColor = '#dc3545';
        })
        .finally(() => {
            // Hide loading state
            spinner.style.display = 'none';
            lock.style.display = 'inline-block';
        });
}

// Items management functionality
let itemCounter = 1;

// Add new item row
document.getElementById('addItemBtn').addEventListener('click', function() {
    const container = document.getElementById('poItemsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'po-item-row border rounded p-3 mb-2';
    newRow.innerHTML = `
        <div class="row g-2">
            <div class="col-md-4">
                <select class="form-select" name="items[${itemCounter}][item_id]" required>
                    <option value="">Select Item</option>
                    ${document.querySelector('select[name="items[0][item_id]"]').innerHTML}
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control" name="items[${itemCounter}][quantity]" placeholder="Qty" min="0.01" step="0.01" required>
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control" name="items[${itemCounter}][unit_cost]" placeholder="Unit Cost" min="0.01" step="0.01" required>
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control" name="items[${itemCounter}][total_cost]" placeholder="Total" readonly>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(newRow);
    itemCounter++;
    
    // Show remove button on first row if we have more than one row
    if (itemCounter > 1) {
        document.querySelector('.po-item-row .remove-item').style.display = 'inline-block';
    }
    
    // Add event listeners to new row
    addItemEventListeners(newRow);
});

// Remove item row
document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-item')) {
        const itemRow = e.target.closest('.po-item-row');
        itemRow.remove();
        itemCounter--;
        
        // Hide remove button on first row if we only have one row left
        if (itemCounter === 1) {
            document.querySelector('.po-item-row .remove-item').style.display = 'none';
        }
        
        calculateTotal();
    }
});

// Add event listeners to item inputs
function addItemEventListeners(itemRow) {
    const itemSelect = itemRow.querySelector('select[name*="[item_id]"]');
    const quantityInput = itemRow.querySelector('input[name*="[quantity]"]');
    const unitCostInput = itemRow.querySelector('input[name*="[unit_cost]"]');
    const totalInput = itemRow.querySelector('input[name*="[total_cost]"]');
    
    // Auto-fill unit cost when item is selected
    itemSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.dataset.cost) {
            unitCostInput.value = selectedOption.dataset.cost;
            calculateItemTotal(quantityInput, unitCostInput, totalInput);
        }
    });
    
    // Calculate total when quantity or unit cost changes
    quantityInput.addEventListener('input', function() {
        calculateItemTotal(quantityInput, unitCostInput, totalInput);
    });
    
    unitCostInput.addEventListener('input', function() {
        calculateItemTotal(quantityInput, unitCostInput, totalInput);
    });
}

// Calculate total for a single item
function calculateItemTotal(quantityInput, unitCostInput, totalInput) {
    const quantity = parseFloat(quantityInput.value) || 0;
    const unitCost = parseFloat(unitCostInput.value) || 0;
    const total = quantity * unitCost;
    totalInput.value = total.toFixed(2);
    calculateTotal();
}

// Calculate total for all items
function calculateTotal() {
    let total = 0;
    document.querySelectorAll('input[name*="[total_cost]"]').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('poTotalAmount').textContent = 'Frw ' + total.toFixed(2);
}

// Add event listeners to initial row
document.addEventListener('DOMContentLoaded', function() {
    const initialRow = document.querySelector('.po-item-row');
    if (initialRow) {
        addItemEventListeners(initialRow);
    }
});
</script> 