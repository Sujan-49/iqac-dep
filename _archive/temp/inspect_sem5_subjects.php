<?php
require_once __DIR__ . '/../dp_connection.php';
$res = $conn->query("SELECT * FROM subjects WHERE semester = 5");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No Semester 5 subjects found.\n";
}
