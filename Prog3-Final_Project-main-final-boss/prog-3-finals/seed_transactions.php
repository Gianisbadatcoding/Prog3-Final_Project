<?php
require_once 'dbconnection.php';

try {
    $conn = getDBConnection();
    echo "Connected to database.\n";

    // 1. Fetch Users
    $result = $conn->query("SELECT user_id FROM users");
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row['user_id'];
    }

    if (empty($users)) {
        die("Error: No users found. Please run setup.php first.\n");
    }

    // 2. Fetch Items
    $result = $conn->query("SELECT item_id, price FROM items");
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    if (empty($items)) {
        die("Error: No items found. Please run setup.php first or add items.\n");
    }

    echo "Found " . count($users) . " users and " . count($items) . " items.\n";
    echo "Generating 100 transactions...\n";

    $conn->begin_transaction();

    $stmt_txn = $conn->prepare("INSERT INTO transactions (user_id, total_amount, created_at) VALUES (?, ?, ?)");
    $stmt_item = $conn->prepare("INSERT INTO transaction_items (transaction_id, item_id, quantity, price_at_sale) VALUES (?, ?, ?, ?)");

    for ($i = 0; $i < 100; $i++) {
        // Random User
        $user_id = $users[array_rand($users)];

        // Random Date (last 30 days)
        $timestamp = time() - mt_rand(0, 30 * 24 * 60 * 60);
        $created_at = date('Y-m-d H:i:s', $timestamp);

        // Random Items (1 to 5 different items)
        $num_items = mt_rand(1, 5);
        $txn_items = [];
        $total_amount = 0;

        // Shuffle items to pick random ones
        shuffle($items);
        $selected_items = array_slice($items, 0, $num_items);

        // Calculate total first
        foreach ($selected_items as $item) {
            $qty = mt_rand(1, 10);
            $price = $item['price'];
            $total_amount += $qty * $price;
            
            $txn_items[] = [
                'item_id' => $item['item_id'],
                'quantity' => $qty,
                'price' => $price
            ];
        }

        // Insert Transaction
        $stmt_txn->bind_param("ids", $user_id, $total_amount, $created_at);
        $stmt_txn->execute();
        $txn_id = $conn->insert_id;

        // Insert Items
        foreach ($txn_items as $t_item) {
            $stmt_item->bind_param("iiid", $txn_id, $t_item['item_id'], $t_item['quantity'], $t_item['price']);
            $stmt_item->execute();
        }

        if (($i + 1) % 10 == 0) {
            echo ".";
        }
    }

    $conn->commit();
    echo "\nSuccessfully inserted 100 transactions!\n";

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    echo "\nError: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) closeDBConnection($conn);
}
?>
