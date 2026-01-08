<?php
header('Content-Type: application/json');

require_once '../auth.php';
requireLogin(); 

require_once '../dbconnection.php';

// Get JSON Input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['items']) || empty($input['items'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No items in cart']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user = getCurrentUser();
if (!$user || !isset($user['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $user['user_id'];

// Verify user exists in DB (fix for stale sessions)
$u_stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
if ($u_stmt->get_result()->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid session. Please logout and login again.']);
    exit();
}
$u_stmt->close();

$items = $input['items'];

// Start Transaction
$conn->begin_transaction();

try {
    $total_amount = 0;
    
    // First pass: Calculate total and check stock
    foreach ($items as $item) {
        $id = $item['id'];
        $qty = $item['qty'];
        
        // Lock row for update
        $stmt = $conn->prepare("SELECT price, quantity, item_name FROM items WHERE item_id = ? FOR UPDATE");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
        
        if (!$product) {
            throw new Exception("Product ID $id not found");
        }
        
        if ($product['quantity'] < $qty) {
            throw new Exception("Insufficient stock for {$product['item_name']}. Available: {$product['quantity']}");
        }
        
        $total_amount += ($product['price'] * $qty);
    }
    
    // Insert Transaction Record
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, total_amount) VALUES (?, ?)");
    $stmt->bind_param("id", $user_id, $total_amount);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create transaction record");
    }
    
    $transaction_id = $conn->insert_id;
    $stmt->close();
    
    // Process Items: Deduct Stock and Insert Transaction Items
    $insert_stmt = $conn->prepare("INSERT INTO transaction_items (transaction_id, item_id, quantity, price_at_sale) VALUES (?, ?, ?, ?)");
    $update_stmt = $conn->prepare("UPDATE items SET quantity = quantity - ? WHERE item_id = ?");
    
    foreach ($items as $item) {
        $id = $item['id'];
        $qty = $item['qty'];
        
        // Get price again (or cache it from before, but safer to re-fetch if we didn't store it)
        // Optimization: We could have stored it in an array in the first loop.
        // Let's assume price hasn't changed in milliseconds. 
        // We really should have stored it. Let's do a quick select or just trust the first loop?
        // Actually, let's just do a SELECT price again to be safe/simple, or fetch from DB in loop.
        // Better: We already fetched it. Let's assume valid. 
        // Wait, I didn't save the price in the first loop. Let's just re-fetch or assume safe to reuse logic if I change structure.
        // Let's keep it simple: Select again. In high concurency, it's locked anyway.
        
        $price_stmt = $conn->prepare("SELECT price FROM items WHERE item_id = ?");
        $price_stmt->bind_param("i", $id);
        $price_stmt->execute();
        $p_res = $price_stmt->get_result();
        $p_row = $p_res->fetch_assoc();
        $price = $p_row['price'];
        $price_stmt->close();
        
        // Insert Item
        $insert_stmt->bind_param("iiid", $transaction_id, $id, $qty, $price);
        if (!$insert_stmt->execute()) {
            throw new Exception("Failed to record item $id");
        }
        
        // Update Stock
        $update_stmt->bind_param("ii", $qty, $id);
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update stock for item $id");
        }
    }
    
    $insert_stmt->close();
    $update_stmt->close();
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Transaction successful', 'transaction_id' => $transaction_id]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

closeDBConnection($conn);
?>
