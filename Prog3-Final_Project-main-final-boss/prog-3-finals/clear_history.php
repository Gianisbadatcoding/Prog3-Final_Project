<?php
require_once 'dbconnection.php';

try {
    $conn = getDBConnection();
    echo "Connected to database.\n";

    $conn->begin_transaction();

    // Delete transaction items first due to Foreign Key constraint
    if ($conn->query("DELETE FROM transaction_items") === TRUE) {
        echo "Deleted all transaction items.\n";
    } else {
        throw new Exception("Error deleting transaction items: " . $conn->error);
    }

    // Delete transactions
    if ($conn->query("DELETE FROM transactions") === TRUE) {
        echo "Deleted all transactions.\n";
    } else {
        throw new Exception("Error deleting transactions: " . $conn->error);
    }

    // Reset Auto Increment (optional, but good for "clean" state)
    $conn->query("ALTER TABLE transactions AUTO_INCREMENT = 1");
    $conn->query("ALTER TABLE transaction_items AUTO_INCREMENT = 1");

    $conn->commit();
    echo "Transaction history cleared successfully.\n";

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) closeDBConnection($conn);
}
?>
