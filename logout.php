<?php
require_once __DIR__ . '/include/auth.php';

iqac_logout($conn);
iqac_no_cache_headers();
header('Location: login.php');
exit;
?>
