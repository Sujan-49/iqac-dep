<?php
require_once __DIR__ . '/../dp_connection.php';

echo "=== student_marks samples ===\n";
$res = $conn->query("SELECT * FROM student_marks LIMIT 3");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No rows found in student_marks.\n";
}

echo "=== staff_subject_allocation samples ===\n";
$res = $conn->query("SELECT * FROM staff_subject_allocation LIMIT 3");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No rows found in staff_subject_allocation.\n";
}

echo "=== subjects samples ===\n";
$res = $conn->query("SELECT * FROM subjects LIMIT 3");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No rows found in subjects.\n";
}
