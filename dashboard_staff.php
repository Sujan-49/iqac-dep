<?php
require_once __DIR__ . '/include/auth.php';
iqac_require_login(['staff']);
$_GET['dashboard'] = 'staff';
require_once __DIR__ . '/academic_erp.php';
?>
