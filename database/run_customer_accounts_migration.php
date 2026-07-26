<?php
require dirname(__DIR__) . '/config/config.php';

if (!$conn) {
    fwrite(STDERR, "No DB connection\n");
    exit(1);
}

function columnExists(mysqli $conn, string $table, string $column): bool
{
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return $result && mysqli_num_rows($result) > 0;
}

function tableExists(mysqli $conn, string $table): bool
{
    $result = mysqli_query($conn, "SHOW TABLES LIKE '" . mysqli_real_escape_string($conn, $table) . "'");
    return $result && mysqli_num_rows($result) > 0;
}

if (!columnExists($conn, 'customers', 'password_hash')) {
    mysqli_query($conn, 'ALTER TABLE customers ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL AFTER phone')
        or die(mysqli_error($conn));
    echo "Added password_hash\n";
} else {
    echo "password_hash exists\n";
}

if (!columnExists($conn, 'customers', 'account_status')) {
    mysqli_query($conn, "ALTER TABLE customers ADD COLUMN account_status ENUM('guest','active') NOT NULL DEFAULT 'guest' AFTER password_hash")
        or die(mysqli_error($conn));
    echo "Added account_status\n";
} else {
    echo "account_status exists\n";
}

if (!tableExists($conn, 'customer_favorites')) {
    $sql = "CREATE TABLE customer_favorites (
      favorite_id int(11) NOT NULL AUTO_INCREMENT,
      customer_id int(11) NOT NULL,
      menu_item_id int(11) NOT NULL,
      created_at timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (favorite_id),
      UNIQUE KEY uniq_customer_menu_item (customer_id, menu_item_id),
      KEY idx_customer_favorites_customer (customer_id),
      CONSTRAINT fk_customer_favorites_customer FOREIGN KEY (customer_id) REFERENCES customers (customer_id) ON DELETE CASCADE,
      CONSTRAINT fk_customer_favorites_menu_item FOREIGN KEY (menu_item_id) REFERENCES menu_items (menu_item_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($conn, $sql) or die(mysqli_error($conn));
    echo "Created customer_favorites\n";
} else {
    echo "customer_favorites exists\n";
}

echo "Migration complete\n";
