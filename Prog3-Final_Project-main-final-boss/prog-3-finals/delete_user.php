<?php
require_once 'auth.php';
requireLogin();
requireManager();

require_once 'dbconnection.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: users.php?error=Invalid ID");
    exit();
}

// Prevent self-deletion
$currentUser = getCurrentUser();
if ($id == $currentUser['user_id']) {
    header("Location: users.php?error=You cannot delete your own account");
    exit();
}

$conn = getDBConnection();

// Check if user exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    closeDBConnection($conn);
    header("Location: users.php?error=User not found");
    exit();
}
$stmt->close();

// Delete User
$del_stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
$del_stmt->bind_param("i", $id);

if ($del_stmt->execute()) {
    $del_stmt->close();
    closeDBConnection($conn);
    header("Location: users.php?success=deleted");
    exit();
} else {
    $error = $conn->error;
    $del_stmt->close();
    closeDBConnection($conn);
    header("Location: users.php?error=Deletion failed: " . urlencode($error));
    exit();
}
?>
