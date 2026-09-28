<?php
require_once __DIR__ . '/../dp_connection.php';

$res = $conn->query("SELECT * FROM subjects");
while ($row = $res->fetch_assoc()) {
    echo "Code: " . $row['subject_code'] . " | Name: " . $row['subject_name'] . " | Sem: " . $row['semester'] . " | Dept: " . $row['department'] . "\n";
}
