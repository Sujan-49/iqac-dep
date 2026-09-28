<?php
require_once __DIR__ . '/../dp_connection.php';

$tables = ['batch_subjects', 'student_subjects', 'batches', 'subjects'];
foreach ($tables as $table) {
    echo "=== DESCRIBE $table ===\n";
    $res = $conn->query("DESCRIBE `$table`");
    while ($row = $res->fetch_assoc()) {
        echo "  Field: " . $row['Field'] . " | Type: " . $row['Type'] . "\n";
    }
    echo "\n";
}
