<?php
header('Content-Type: application/json');

require_once '../auth.php';
// requireLogin(); // Uncomment if you want to protect this endpoint

require_once '../dbconnection.php';

$conn = getDBConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$sql = "SELECT item_id, item_name, price, quantity, category, image_path, isles, shelf_position FROM items WHERE status = 'active'";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (item_name LIKE ? OR item_id = ?)";
    $params[] = "%$search%";
    $params[] = $search; // Exact match for ID (barcode style)
    $types .= "ss";
}

if (!empty($category)) {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

$sql .= " ORDER BY item_name ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode($items);

$stmt->close();
closeDBConnection($conn);
?>
