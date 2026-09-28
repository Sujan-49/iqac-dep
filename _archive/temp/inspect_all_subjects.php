<?php
require_once __DIR__ . '/../dp_connection.php';
$res = $conn->query("SELECT * FROM subjects WHERE department = 'Diploma in Information Technology' AND semester BETWEEN 1 AND 4 ORDER BY semester, subject_code");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
