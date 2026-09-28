<?php
require_once __DIR__ . '/../dp_connection.php';

echo "=== USERS ===\n";
$res = $conn->query("SELECT id, username, role, is_active, associated_id, department, semester, batch FROM users WHERE username LIKE '40DI%' OR username IN ('staff40', 'tutor40', 'dsa40', 'dbms40', 'cn40', 'admin40', 'mainadmin40')");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Query failed: " . $conn->error . "\n";
}

echo "=== STUDENT DETAILS ===\n";
if ($conn->query("SHOW TABLES LIKE 'student_details'")->num_rows > 0) {
    $res = $conn->query("SELECT student_id, user_id, roll_number, full_name, current_semester, class_year, batch, stage1_status, tutor_staff_id FROM student_details WHERE roll_number LIKE '40DI%'");
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "student_details table not found!\n";
}

echo "=== STAFF DETAILS ===\n";
if ($conn->query("SHOW TABLES LIKE 'staff_details'")->num_rows > 0) {
    $res = $conn->query("SELECT staff_id, user_id, full_name, department, is_tutor FROM staff_details");
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "staff_details table not found!\n";
}
