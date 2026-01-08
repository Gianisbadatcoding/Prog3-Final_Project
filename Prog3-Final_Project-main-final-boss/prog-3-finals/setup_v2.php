<?php
/**
 * Database Migration Script - Phase 2
 * Creates tables for Transaction History
 */

require_once 'dbconnection.php';

echo "Starting database migration for Transaction History...\n";

$conn = getDBConnection();

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// 1. Create TRANSACTIONS table
$sql_transactions = "CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (transaction_id),
    KEY user_id (user_id),
    CONSTRAINT fk_transaction_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($sql_transactions) === TRUE) {
    echo "Table 'transactions' created or already exists.\n";
} else {
    echo "Error creating table 'transactions': " . $conn->error . "\n";
}

// 2. Create TRANSACTION_ITEMS table
$sql_items = "CREATE TABLE IF NOT EXISTS transaction_items (
    id INT(11) NOT NULL AUTO_INCREMENT,
    transaction_id INT(11) NOT NULL,
    item_id INT(11) NOT NULL,
    quantity INT(11) NOT NULL,
    price_at_sale DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    KEY transaction_id (transaction_id),
    KEY item_id (item_id),
    CONSTRAINT fk_items_transaction FOREIGN KEY (transaction_id) REFERENCES transactions (transaction_id) ON DELETE CASCADE,
    CONSTRAINT fk_items_item FOREIGN KEY (item_id) REFERENCES items (item_id) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($sql_items) === TRUE) {
    echo "Table 'transaction_items' created or already exists.\n";
} else {
    echo "Error creating table 'transaction_items': " . $conn->error . "\n";
}

closeDBConnection($conn);
echo "Migration completed.\n";
?>
