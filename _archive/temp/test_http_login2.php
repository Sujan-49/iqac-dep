<?php
declare(strict_types=1);

function test_login(string $username, string $password): array {
    $cookie_file = tempnam(sys_get_temp_dir(), 'cookie_');
    
    // 1. GET login page to obtain CSRF token and cookie
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/Iqac/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if (!$response) {
        return ['status' => 'FAIL', 'error' => 'GET request failed'];
    }
    
    // Extract CSRF token
    if (!preg_match('/name="csrf_token"\s+value="([^"]+)"/', $response, $matches)) {
        return ['status' => 'FAIL', 'error' => 'CSRF token not found in GET response'];
    }
    $csrf_token = $matches[1];
    
    // 2. POST credentials
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/Iqac/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'csrf_token' => $csrf_token,
        'username' => $username,
        'password' => $password
    ]));
    curl_setopt($ch, CURLOPT_HEADER, true);
    $response2 = curl_exec($ch);
    $info2 = curl_getinfo($ch);
    curl_close($ch);
    
    unlink($cookie_file);
    
    if (!$response2) {
        return ['status' => 'FAIL', 'error' => 'POST request failed'];
    }
    
    // Check if redirect occurs (302 Found)
    if ($info2['http_code'] === 302) {
        if (preg_match('/Location:\s*([^\r\n]+)/i', $response2, $locMatches)) {
            $redirect_url = trim($locMatches[1]);
            return ['status' => 'PASS', 'redirect' => $redirect_url];
        }
        return ['status' => 'PASS', 'redirect' => 'unknown'];
    }
    
    if (preg_match('/<div class="alert error">([^<]+)<\/div>/', $response2, $errMatches)) {
        return ['status' => 'FAIL', 'error' => trim($errMatches[1])];
    }
    
    return ['status' => 'FAIL', 'error' => 'Invalid credentials or validation failed without explicit alert'];
}

$accounts = [
    '40DI01' => '40DI01',
    '40DI07' => '40DI07',
    '40DI27' => '40DI27',
    'tutor40' => 'staff123',
    'dsa40' => 'staff123',
    'dbms40' => 'staff123',
    'cn40' => 'staff123',
    'admin40' => 'admin123'
];

echo "==================================================\n";
echo "PSG PTC ERP – HTTP LOGIN VERIFICATION RESULTS\n";
echo "==================================================\n";

$failed = 0;
foreach ($accounts as $user => $pass) {
    $res = test_login($user, $pass);
    if ($res['status'] === 'PASS') {
        echo "$user / $pass -> PASS (Redirect: {$res['redirect']})\n";
    } else {
        echo "$user / $pass -> FAIL: {$res['error']}\n";
        $failed++;
    }
}
echo "==================================================\n";
if ($failed === 0) {
    echo "ALL LOGINS SUCCESSFUL!\n";
} else {
    echo "$failed LOGINS FAILED!\n";
}
echo "==================================================\n";
