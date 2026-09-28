<?php
require_once __DIR__ . '/../dp_connection.php';

echo "=== mark_entry_windows schema ===\n";
if ($conn->query("SHOW TABLES LIKE 'mark_entry_windows'")->num_rows > 0) {
    $res = $conn->query("DESCRIBE mark_entry_windows");
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
    echo "=== mark_entry_windows rows ===\n";
    $res = $conn->query("SELECT * FROM mark_entry_windows");
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "mark_entry_windows table does not exist.\n";
}
