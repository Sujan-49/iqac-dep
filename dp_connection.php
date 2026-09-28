<?php
// Database connection parameters
$host = 'localhost'; // or your host
$db_user = 'root'; // replace with your database username
$db_password = ''; // replace with your database password
$db_name = 'iqac'; // replace with your database name

// Create connection
$conn = new mysqli($host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>