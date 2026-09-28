<?php
require_once __DIR__ . '/../dp_connection.php';
$res = $conn->query("SELECT DISTINCT department FROM staff_details");
while ($row = $res->fetch_row()) {
    echo "Staff department: " . $row[0] . "\n";
}
if ($conn->query("SHOW TABLES LIKE 'departments'")->num_rows > 0) {
    $res = $conn->query("SELECT * FROM departments");
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
}
