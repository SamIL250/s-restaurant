-- SAFE SQL Script to add deleted_at columns for soft delete functionality
-- Run this script in parts to avoid errors
-- This script handles the problematic timestamp columns first

-- =====================================================
-- STEP 1: Fix problematic timestamp columns first
-- =====================================================

-- Fix orders table timestamp columns (these have invalid default values)
ALTER TABLE orders MODIFY COLUMN estimated_ready_time TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE orders MODIFY COLUMN completed_at TIMESTAMP NULL DEFAULT NULL;

-- =====================================================
-- STEP 2: Add deleted_at columns to core tables
-- =====================================================

-- Add deleted_at column to reservations table
ALTER TABLE reservations ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Add deleted_at column to reservation_items table
ALTER TABLE reservation_items ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Add deleted_at column to inventory_items table
ALTER TABLE inventory_items ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Add deleted_at column to menu_items table
ALTER TABLE menu_items ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Add deleted_at column to categories table
ALTER TABLE categories ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Add deleted_at column to suppliers table
ALTER TABLE suppliers ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- =====================================================
-- STEP 3: Add deleted_at columns to management tables
-- =====================================================

-- Add deleted_at column to customers table
ALTER TABLE customers ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Add deleted_at column to users table (staff)
ALTER TABLE users ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Add deleted_at column to restaurant_tables table
ALTER TABLE restaurant_tables ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Add deleted_at column to promotions table
ALTER TABLE promotions ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Add deleted_at column to purchase_orders table
ALTER TABLE purchase_orders ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Add deleted_at column to purchase_order_items table
ALTER TABLE purchase_order_items ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- =====================================================
-- STEP 4: Add deleted_at columns to transaction tables
-- =====================================================

-- Add deleted_at column to stock_movements table
ALTER TABLE stock_movements ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Add deleted_at column to subscription_types table
ALTER TABLE subscription_types ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Add deleted_at column to subscription_payments table
ALTER TABLE subscription_payments ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Add deleted_at column to subscription_usage table
ALTER TABLE subscription_usage ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Add deleted_at column to subscription_alerts table
ALTER TABLE subscription_alerts ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Add deleted_at column to recipe_ingredients table
ALTER TABLE recipe_ingredients ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Add deleted_at column to orders table
ALTER TABLE orders ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Add deleted_at column to order_items table
ALTER TABLE order_items ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;

-- =====================================================
-- STEP 5: Create indexes for performance
-- =====================================================

-- Create indexes for better performance on soft delete queries
CREATE INDEX idx_reservations_deleted_at ON reservations(deleted_at);
CREATE INDEX idx_reservation_items_deleted_at ON reservation_items(deleted_at);
CREATE INDEX idx_inventory_items_deleted_at ON inventory_items(deleted_at);
CREATE INDEX idx_menu_items_deleted_at ON menu_items(deleted_at);
CREATE INDEX idx_categories_deleted_at ON categories(deleted_at);
CREATE INDEX idx_suppliers_deleted_at ON suppliers(deleted_at);
CREATE INDEX idx_customers_deleted_at ON customers(deleted_at);
CREATE INDEX idx_users_deleted_at ON users(deleted_at);
CREATE INDEX idx_restaurant_tables_deleted_at ON restaurant_tables(deleted_at);
CREATE INDEX idx_promotions_deleted_at ON promotions(deleted_at);
CREATE INDEX idx_purchase_orders_deleted_at ON purchase_orders(deleted_at);
CREATE INDEX idx_purchase_order_items_deleted_at ON purchase_order_items(deleted_at);
CREATE INDEX idx_stock_movements_deleted_at ON stock_movements(deleted_at);
CREATE INDEX idx_subscription_types_deleted_at ON subscription_types(deleted_at);
CREATE INDEX idx_subscription_payments_deleted_at ON subscription_payments(deleted_at);
CREATE INDEX idx_subscription_usage_deleted_at ON subscription_usage(deleted_at);
CREATE INDEX idx_subscription_alerts_deleted_at ON subscription_alerts(deleted_at);
CREATE INDEX idx_recipe_ingredients_deleted_at ON recipe_ingredients(deleted_at);
CREATE INDEX idx_orders_deleted_at ON orders(deleted_at);
CREATE INDEX idx_order_items_deleted_at ON order_items(deleted_at);

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Check if all tables now have deleted_at columns
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE COLUMN_NAME = 'deleted_at' 
AND TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

-- Check for any remaining timestamp columns with invalid defaults
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    DATA_TYPE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE DATA_TYPE = 'timestamp' 
AND COLUMN_DEFAULT = '0000-00-00 00:00:00'
AND TABLE_SCHEMA = DATABASE();
