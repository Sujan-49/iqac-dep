<?php
require_once __DIR__ . '/../dp_connection.php';
$res = $conn->query("DESCRIBE users");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
