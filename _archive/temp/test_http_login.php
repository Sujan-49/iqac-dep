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
        // Find Location header
        if (preg_match('/Location:\s*([^\r\n]+)/i', $response2, $locMatches)) {
            $redirect_url = trim($locMatches[1]);
            return ['status' => 'PASS', 'redirect' => $redirect_url];
        }
        return ['status' => 'PASS', 'redirect' => 'unknown'];
    }
    
    // If we didn't get a redirect, let's extract the alert error if present
    if (preg_match('/<div class="alert error">([^<]+)<\/div>/', $response2, $errMatches)) {
        return ['status' => 'FAIL', 'error' => trim($errMatches[1])];
    }
    
    return ['status' => 'FAIL', 'error' => 'Invalid credentials or validation failed without explicit alert'];
}

$students = [
    '40DI01', '40DI02', '40DI03', '40DI04', '40DI05', '40DI06', '40DI07', '40DI08', '40DI09', '40DI10',
    '40DI11', '40DI12', '40DI13', '40DI14', '40DI15', '40DI16', '40DI17', '40DI18', '40DI19', '40DI20',
    '40DI21', '40DI22', '40DI23', '40DI24', '40DI25', '40DI26', '40DI27'
];

echo "Testing student 40DI07 login...\n";
$res = test_login('40DI07', '40DI07');
print_r($res);

echo "\nTesting other roles...\n";
echo "staff40: "; print_r(test_login('staff40', 'staff123'));
echo "dsa40: "; print_r(test_login('dsa40', 'staff123'));
echo "dbms40: "; print_r(test_login('dbms40', 'staff123'));
echo "cn40: "; print_r(test_login('cn40', 'staff123'));
echo "mainadmin40: "; print_r(test_login('mainadmin40', 'admin123'));
