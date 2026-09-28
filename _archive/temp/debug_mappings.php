<?php
require_once __DIR__ . '/../dp_connection.php';

echo "=== SELECT * FROM batches ===\n";
$res1 = $conn->query("SELECT * FROM batches");
while ($row = $res1->fetch_assoc()) {
    print_r($row);
}

echo "\n=== SELECT * FROM batch_subjects ===\n";
$res2 = $conn->query("SELECT bs.*, b.batch_name FROM batch_subjects bs JOIN batches b ON b.batch_id = bs.batch_id");
while ($row = $res2->fetch_assoc()) {
    print_r($row);
}
