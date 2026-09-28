<?php
require_once 'dp_connection.php';
$res = $conn->query("SELECT DATABASE() AS db");
$row = $res->fetch_assoc();
echo "HTTP DB: " . $row['db'] . "\n";
