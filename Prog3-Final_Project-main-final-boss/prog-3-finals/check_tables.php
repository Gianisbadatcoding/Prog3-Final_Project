<?php
require_once 'dbconnection.php';

$conn = getDBConnection();

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Connected to database: " . DB_NAME . "\n";
echo "Host: " . DB_HOST . "\n";

$result = $conn->query("SHOW TABLES");

echo "Tables found:\n";
if ($result->num_rows > 0) {
    while($row = $result->fetch_array()) {
        echo "- " . $row[0] . "\n";
    }
} else {
    echo "No tables found.\n";
}

$conn->close();
?>
