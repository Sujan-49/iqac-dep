<?php
$hash = '$2y$10$vKB6vD5kfRWe.r3jK.zUFO5io03mD4EpEnh23TpNxvA9xBgBcxHhi';
$password = '40DI07';
if (password_verify($password, $hash)) {
    echo "Password verify: SUCCESS\n";
} else {
    echo "Password verify: FAILURE\n";
}
