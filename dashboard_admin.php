<?php
require_once __DIR__ . '/include/auth.php';
iqac_require_login(['hod', 'iqac', 'admin']);
$_GET['dashboard'] = 'admin';
require_once __DIR__ . '/academic_erp.php';
?>
