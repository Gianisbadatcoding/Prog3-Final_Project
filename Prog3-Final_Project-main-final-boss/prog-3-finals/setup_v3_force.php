<?php
// Enable full error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once 'dbconnection.php';

echo "Starting debug migration...\n";

try {
    $conn = getDBConnection();
    echo "Connected to: " . DB_NAME . "\n";

    // 1. Transactions Table
    $sql_transactions = "CREATE TABLE IF NOT EXISTS transactions (
        transaction_id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (transaction_id),
        KEY user_id (user_id),
        CONSTRAINT fk_transaction_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    echo "Creating transactions table...\n";
    $conn->query($sql_transactions);
    echo "Transactions table created successfully.\n";

    // 2. Transaction Items Table
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

    echo "Creating transaction_items table...\n";
    $conn->query($sql_items);
    echo "Transaction_items table created successfully.\n";

    $conn->close();

} catch (mysqli_sql_exception $e) {
    echo "SQL ERROR: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "GENERAL ERROR: " . $e->getMessage() . "\n";
}
?>
