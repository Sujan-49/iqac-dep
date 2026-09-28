<?php
require_once __DIR__ . '/include/auth.php';
iqac_require_login(['tutor']);
$_GET['dashboard'] = 'tutor';
require_once __DIR__ . '/academic_erp.php';
?>
