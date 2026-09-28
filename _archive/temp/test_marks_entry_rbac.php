<?php
declare(strict_types=1);

function http_post_login(string $username, string $password, string $cookie_file): ?string {
    // 1. GET to get CSRF token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/Iqac/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if (!$response || !preg_match('/name="csrf_token"\s+value="([^"]+)"/', $response, $matches)) {
        return null;
    }
    $csrf = $matches[1];
    
    // 2. POST credentials
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/Iqac/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'csrf_token' => $csrf,
        'username' => $username,
        'password' => $password
    ]));
    $response2 = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($info['http_code'] === 302) {
        return $csrf; // Login successful!
    }
    return null;
}

function http_save_marks(string $cookie_file, string $subject, array $marksData): array {
    // 1. GET to get CSRF token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/Iqac/marks_entry.php?mode=ca&subject_code=' . urlencode($subject) . '&semester=5&class_year=3');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if (!$response) {
        return ['status' => 'FAIL', 'error' => 'GET marks_entry page failed'];
    }
    
    if (!preg_match('/name="csrf_token"\s+value="([^"]+)"/', $response, $matches)) {
        return ['status' => 'FAIL', 'error' => 'CSRF token not found'];
    }
    $csrf = $matches[1];
    
    // Build POST fields
    $fields = [
        'csrf_token' => $csrf,
        'action' => 'save_bulk_marks',
        'subject_code' => $subject,
        'semester' => '5',
        'class_year' => '3'
    ];
    
    foreach ($marksData as $sId => $m) {
        $fields['student_ids'][] = $sId;
        $fields['ca1'][$sId] = $m['ca1'];
        $fields['ca2'][$sId] = $m['ca2'];
        $fields['ca3'][$sId] = $m['ca3'];
        $fields['assignment_mark'][$sId] = $m['assignment'];
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/Iqac/marks_entry.php?mode=ca&subject_code=' . urlencode($subject) . '&semester=5&class_year=3');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    $response2 = curl_exec($ch);
    $info2 = curl_getinfo($ch);
    curl_close($ch);
    
    if (preg_match('/<div class="alert error">([^<]+)<\/div>/', $response2, $errMatches)) {
        return ['status' => 'FAIL', 'error' => trim($errMatches[1])];
    }
    if (preg_match('/<div class="alert success">([^<]+)<\/div>/', $response2, $sucMatches)) {
        return ['status' => 'PASS', 'message' => trim($sucMatches[1])];
    }
    
    return ['status' => 'FAIL', 'error' => 'No success or error message found'];
}

// Prepare student IDs
require_once __DIR__ . '/../dp_connection.php';
$studentIds = [];
$res = $conn->query("SELECT student_id, roll_number FROM student_details WHERE roll_number IN ('40DI01', '40DI07', '40DI12', '40DI20', '40DI25')");
while ($row = $res->fetch_assoc()) {
    $studentIds[$row['roll_number']] = (int)$row['student_id'];
}

$marksData = [
    $studentIds['40DI01'] => ['ca1' => 45, 'ca2' => 48, 'ca3' => 47, 'assignment' => 10],
    $studentIds['40DI07'] => ['ca1' => 38, 'ca2' => 40, 'ca3' => 42, 'assignment' => 9],
    $studentIds['40DI12'] => ['ca1' => 32, 'ca2' => 35, 'ca3' => 33, 'assignment' => 8],
    $studentIds['40DI20'] => ['ca1' => 24, 'ca2' => 26, 'ca3' => 28, 'assignment' => 7],
    $studentIds['40DI25'] => ['ca1' => 28, 'ca2' => 30, 'ca3' => 25, 'assignment' => 6]
];

echo "==================================================\n";
echo "PSG PTC ERP – WRITE TEST & RBAC VERIFICATION\n";
echo "==================================================\n";

$cookie_file = tempnam(sys_get_temp_dir(), 'cookie_');

// 1. Login as dsa40 and write marks
echo "Logging in as dsa40...\n";
$csrf = http_post_login('dsa40', 'staff123', $cookie_file);
if ($csrf) {
    echo "Login SUCCESS. Submitting DSA (40DI501) marks...\n";
    $res = http_save_marks($cookie_file, '40DI501', $marksData);
    print_r($res);
} else {
    echo "Login FAILED for dsa40.\n";
}

// Verify database values
echo "\nVerifying DB values for 40DI07 DSA:\n";
$dbRes = $conn->query("SELECT sm.*, sd.roll_number FROM student_marks sm JOIN student_details sd ON sd.student_id = sm.student_id WHERE sd.roll_number = '40DI07' AND sm.subject_code = '40DI501'");
if ($dbRes && $row = $dbRes->fetch_assoc()) {
    print_r($row);
} else {
    echo "No DB record found for 40DI07 DSA!\n";
}

// 2. Test RBAC: Login as dbms40 and try to edit DSA
echo "\nTesting RBAC: Logging in as dbms40...\n";
$cookie_file2 = tempnam(sys_get_temp_dir(), 'cookie_');
$csrf2 = http_post_login('dbms40', 'staff123', $cookie_file2);
if ($csrf2) {
    echo "Login SUCCESS. Attempting to submit DSA (40DI501) marks (should fail/be rejected)...\n";
    $res2 = http_save_marks($cookie_file2, '40DI501', $marksData);
    print_r($res2);
} else {
    echo "Login FAILED for dbms40.\n";
}

unlink($cookie_file);
unlink($cookie_file2);
echo "==================================================\n";
