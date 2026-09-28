<?php
$conn = new mysqli('localhost', 'root', '');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}
$res = $conn->query("SHOW DATABASES");
while ($row = $res->fetch_row()) {
    echo $row[0] . "\n";
}
