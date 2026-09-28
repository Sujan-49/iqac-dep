<?php
require_once __DIR__ . '/../dp_connection.php';

$res = $conn->query("SELECT * FROM users WHERE username = '40DI07' OR username IN ('tutor40', 'staff40', 'dsa40', 'dbms40', 'cn40', 'admin40', 'mainadmin40')");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
