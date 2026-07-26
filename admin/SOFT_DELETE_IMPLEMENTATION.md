# Soft Delete Implementation Guide

## Overview
This guide explains how to implement soft deletes across your restaurant management system. Instead of permanently removing records, soft deletes mark records as deleted by setting a `deleted_at` timestamp.

## Benefits of Soft Deletes
- **Data Recovery**: Accidentally deleted records can be restored
- **Audit Trail**: Maintains complete history of all operations
- **Data Integrity**: Prevents loss of important business data
- **Compliance**: Meets data retention requirements
- **Business Continuity**: No disruption from accidental deletions

## Implementation Steps

### 1. Database Schema Changes
Run the SQL script `add_deleted_at_columns.sql` to add `deleted_at` columns to all tables:

```sql
-- Example for reservations table
ALTER TABLE reservations ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;
CREATE INDEX idx_reservations_deleted_at ON reservations(deleted_at);
```

### 2. Update All SELECT Queries
Add `WHERE deleted_at IS NULL` to all SELECT queries to exclude deleted records:

```sql
-- Before (shows all records including deleted ones)
SELECT * FROM reservations;

-- After (excludes deleted records)
SELECT * FROM reservations WHERE deleted_at IS NULL;
```

### 3. Update Delete Operations
Replace `DELETE` statements with `UPDATE` statements that set `deleted_at`:

```php
// Before (hard delete)
$stmt = $conn->prepare('DELETE FROM reservations WHERE reservation_id = ?');

// After (soft delete)
$stmt = $conn->prepare('UPDATE reservations SET deleted_at = NOW() WHERE reservation_id = ?');
```

### 4. Use the SoftDelete Utility Class
The `src/services/utils/soft_delete.php` file provides helper functions:

```php
require_once 'src/services/utils/soft_delete.php';

$softDelete = getSoftDelete();

// Soft delete a record
$softDelete->softDelete('reservations', 'reservation_id', $reservation_id);

// Restore a deleted record
$softDelete->restore('reservations', 'reservation_id', $reservation_id);

// Check if record is deleted
if ($softDelete->isDeleted('reservations', 'reservation_id', $reservation_id)) {
    // Handle deleted record
}
```

## Tables That Need Soft Delete Implementation

### Core Business Tables
- [x] `reservations` - Customer reservations
- [x] `reservation_items` - Items in reservations
- [ ] `inventory_items` - Stock items
- [ ] `menu_items` - Menu offerings
- [ ] `categories` - Product categories
- [ ] `suppliers` - Vendor information

### Management Tables
- [ ] `customers` - Customer records
- [ ] `users` - Staff accounts
- [ ] `restaurant_tables` - Table management
- [ ] `promotions` - Marketing promotions
- [ ] `purchase_orders` - Supplier orders

### Transaction Tables
- [ ] `orders` - Customer orders
- [ ] `order_items` - Items in orders
- [ ] `stock_movements` - Inventory changes
- [ ] `subscription_types` - Subscription plans
- [ ] `subscription_payments` - Payment records

## Example Implementation

### Before (Hard Delete)
```php
// Delete reservation permanently
$stmt = $conn->prepare('DELETE FROM reservations WHERE reservation_id = ?');
$stmt->bind_param('i', $reservation_id);
$stmt->execute();
```

### After (Soft Delete)
```php
// Mark reservation as deleted
$stmt = $conn->prepare('UPDATE reservations SET deleted_at = NOW() WHERE reservation_id = ?');
$stmt->bind_param('i', $reservation_id);
$stmt->execute();

// Also soft delete related items
$stmt = $conn->prepare('UPDATE reservation_items SET deleted_at = NOW() WHERE reservation_id = ?');
$stmt->bind_param('i', $reservation_id);
$stmt->execute();
```

## Query Updates Required

### Main Listing Pages
Update these files to exclude deleted records:
- `src/pages/reservations/main.php` ✅ (Already updated)
- `src/pages/inventory-items/main.php`
- `src/pages/menu/main.php`
- `src/pages/customers/main.php`
- `src/pages/staff/main.php`
- `src/pages/suppliers/main.php`

### Count Queries
Update status counts and other aggregations:
```sql
-- Before
SELECT status, COUNT(*) as cnt FROM reservations GROUP BY status;

-- After
SELECT status, COUNT(*) as cnt FROM reservations WHERE deleted_at IS NULL GROUP BY status;
```

### Search and Filter Queries
Ensure all search queries include the soft delete filter:
```sql
SELECT * FROM reservations 
WHERE customer_name LIKE ? 
AND deleted_at IS NULL
ORDER BY created_at DESC;
```

## Advanced Features

### 1. Restore Deleted Records
Add restore functionality to admin panels:
```php
if (isset($_POST['restore_id'])) {
    $softDelete = getSoftDelete();
    $softDelete->restore('reservations', 'reservation_id', $_POST['restore_id']);
}
```

### 2. View Deleted Records
Create admin views to see deleted records:
```php
$deletedRecords = $softDelete->getDeletedRecords('reservations');
```

### 3. Permanent Deletion
For compliance, add permanent deletion after a certain period:
```php
// Clean up records deleted more than 1 year ago
$softDelete->cleanupOldDeleted('reservations', 365);
```

### 4. Deletion History
Track who deleted what and when:
```sql
ALTER TABLE reservations ADD COLUMN deleted_by INT NULL;
ALTER TABLE reservations ADD COLUMN deletion_reason VARCHAR(255) NULL;
```

## Testing Checklist

- [ ] Records are marked as deleted instead of removed
- [ ] Deleted records don't appear in main listings
- [ ] Deleted records can be restored
- [ ] Related records are also soft deleted
- [ ] Performance is maintained with proper indexes
- [ ] All queries exclude deleted records

## Performance Considerations

### Indexes
Ensure `deleted_at` columns are properly indexed:
```sql
CREATE INDEX idx_table_deleted_at ON table_name(deleted_at);
```

### Query Optimization
Use composite indexes for common query patterns:
```sql
CREATE INDEX idx_reservations_status_deleted ON reservations(status, deleted_at);
```

### Cleanup Strategy
Implement periodic cleanup of old deleted records:
```php
// Run monthly cleanup
$softDelete->cleanupOldDeleted('reservations', 365); // 1 year
```

## Migration Strategy

### Phase 1: Database Schema
1. Add `deleted_at` columns to all tables
2. Create necessary indexes
3. Test schema changes

### Phase 2: Core Functionality
1. Update delete services
2. Update main listing queries
3. Test basic soft delete functionality

### Phase 3: Advanced Features
1. Add restore functionality
2. Implement deletion history
3. Add admin views for deleted records

### Phase 4: Cleanup and Optimization
1. Implement periodic cleanup
2. Optimize queries
3. Performance testing

## Troubleshooting

### Common Issues
1. **Deleted records still showing**: Check if `WHERE deleted_at IS NULL` is added to queries
2. **Performance degradation**: Ensure proper indexes on `deleted_at` columns
3. **Foreign key constraints**: Update related tables to also use soft deletes

### Debug Queries
```sql
-- Check for deleted records
SELECT COUNT(*) FROM reservations WHERE deleted_at IS NOT NULL;

-- Check for records without deleted_at filter
SELECT * FROM reservations WHERE deleted_at IS NULL LIMIT 10;
```

## Conclusion
Soft deletes provide a robust foundation for data management while maintaining business continuity and compliance requirements. Implement this systematically across all tables to ensure consistent behavior throughout your restaurant management system.
