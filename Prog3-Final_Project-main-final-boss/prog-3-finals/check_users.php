<?php
require_once 'dbconnection.php';

$conn = getDBConnection();

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "--- Users in Database ---\n";
$result = $conn->query("SELECT user_id, username, full_name, role FROM users");

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row['user_id'] . " | User: " . $row['username'] . " | (" . $row['role'] . ")\n";
    }
} else {
    echo "NO USERS FOUND!\n";
}

$conn->close();
?>
