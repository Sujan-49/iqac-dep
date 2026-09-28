<?php
require_once __DIR__ . '/../dp_connection.php';

$username = '40DI07';
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if ($user) {
    echo "User found in 'users' table:\n";
    print_r($user);
} else {
    echo "User NOT found in 'users' table.\n";
}

$stmt2 = $conn->prepare("SELECT * FROM student_details WHERE roll_number = ?");
$stmt2->bind_param('s', $username);
$stmt2->execute();
$res2 = $stmt2->get_result();
$sd = $res2->fetch_assoc();
$stmt2->close();

if ($sd) {
    echo "\nStudent details found in 'student_details' table:\n";
    print_r($sd);
} else {
    echo "\nStudent details NOT found in 'student_details' table.\n";
}
