<?php
require_once __DIR__ . '/include/auth.php';
iqac_require_login(['super_admin']);
$_GET['dashboard'] = 'super';
require_once __DIR__ . '/academic_erp.php';
?>
