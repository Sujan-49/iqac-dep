<?php
declare(strict_types=1);

require_once __DIR__ . '/../dp_connection.php';

function http_post_login(string $username, string $password): ?string {
    // 1. GET to obtain initial session cookie and CSRF token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1/Iqac/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    
    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    
    if (!preg_match('/Set-Cookie:\s*PHPSESSID=([^;]+)/i', $headers, $cookieMatches)) {
        return null;
    }
    $sessId = $cookieMatches[1];
    
    if (!preg_match('/name="csrf_token"\s+value="([^"]+)"/', $body, $csrfMatches)) {
        return null;
    }
    $csrf = $csrfMatches[1];
    
    // 2. Auth POST to authenticate
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, 'http://127.0.0.1/Iqac/login.php');
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_HEADER, true); // Keep headers to extract regenerated session cookie
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_COOKIE, "PHPSESSID=" . $sessId);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query([
        'csrf_token' => $csrf,
        'username' => $username,
        'password' => $password
    ]));
    $res2 = curl_exec($ch2);
    $header_size2 = curl_getinfo($ch2, CURLINFO_HEADER_SIZE);
    $status = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    
    if ($status === 302) {
        $headers2 = substr($res2, 0, $header_size2);
        if (preg_match('/Set-Cookie:\s*PHPSESSID=([^;]+)/i', $headers2, $newCookieMatches)) {
            return $newCookieMatches[1];
        }
        return $sessId;
    }
    return null;
}

function http_save_bulk_marks(string $sessId, string $subject, array $studentIds, array $marksPreset): bool {
    // 1. GET to get CSRF
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1/Iqac/marks_entry.php?mode=ca&subject_code=' . urlencode($subject) . '&semester=5&class_year=3');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=" . $sessId);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if (!$response || !preg_match('/name="csrf_token"\s+value="([^"]+)"/', $response, $matches)) {
        return false;
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
    
    foreach ($studentIds as $sId) {
        $fields['student_ids'][] = $sId;
        $fields['ca1'][$sId] = $marksPreset['ca1'];
        $fields['ca2'][$sId] = $marksPreset['ca2'];
        $fields['ca3'][$sId] = $marksPreset['ca3'];
        $fields['assignment_mark'][$sId] = $marksPreset['assignment'];
    }
    
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, 'http://127.0.0.1/Iqac/marks_entry.php?mode=ca&subject_code=' . urlencode($subject) . '&semester=5&class_year=3');
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_COOKIE, "PHPSESSID=" . $sessId);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query($fields));
    $response2 = curl_exec($ch2);
    $status = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    
    return strpos($response2, 'Marks updated successfully') !== false;
}

echo "==================================================\n";
echo "PSG PTC ERP – END-TO-END DEMO SETUP & VERIFICATION\n";
echo "==================================================\n";

// Get all 27 student IDs
$res = $conn->query("SELECT student_id, roll_number FROM student_details WHERE batch = '40DI' ORDER BY roll_number");
$studentIds = [];
while ($row = $res->fetch_assoc()) {
    $studentIds[] = (int)$row['student_id'];
}
$totalStudents = count($studentIds);
echo "Loaded $totalStudents students in batch 40DI.\n";

if ($totalStudents !== 27) {
    die("Error: Expected 27 students, found $totalStudents\n");
}

// 1. Submit DSA (40DI501) marks by dsa40
echo "\n[DSA] Logging in as dsa40...\n";
$sessDsa = http_post_login('dsa40', 'staff123');
if ($sessDsa) {
    echo "Login SUCCESS (New session: $sessDsa). Submitting DSA marks for all 27 students...\n";
    $success = http_save_bulk_marks($sessDsa, '40DI501', $studentIds, ['ca1' => 42, 'ca2' => 45, 'ca3' => 40, 'assignment' => 9]);
    echo "DSA Submission: " . ($success ? "PASS (Successfully updated)" : "FAIL") . "\n";
} else {
    echo "Login FAILED for dsa40.\n";
}

// 2. Submit DBMS (40DI502) marks by dbms40
echo "\n[DBMS] Logging in as dbms40...\n";
$sessDbms = http_post_login('dbms40', 'staff123');
if ($sessDbms) {
    echo "Login SUCCESS (New session: $sessDbms). Submitting DBMS marks for all 27 students...\n";
    $success = http_save_bulk_marks($sessDbms, '40DI502', $studentIds, ['ca1' => 38, 'ca2' => 40, 'ca3' => 41, 'assignment' => 8]);
    echo "DBMS Submission: " . ($success ? "PASS (Successfully updated)" : "FAIL") . "\n";
} else {
    echo "Login FAILED for dbms40.\n";
}

// 3. Submit CN (40DI503) marks by cn40
echo "\n[CN] Logging in as cn40...\n";
$sessCn = http_post_login('cn40', 'staff123');
if ($sessCn) {
    echo "Login SUCCESS (New session: $sessCn). Submitting CN marks for all 27 students...\n";
    $success = http_save_bulk_marks($sessCn, '40DI503', $studentIds, ['ca1' => 45, 'ca2' => 46, 'ca3' => 44, 'assignment' => 10]);
    echo "CN Submission: " . ($success ? "PASS (Successfully updated)" : "FAIL") . "\n";
} else {
    echo "Login FAILED for cn40.\n";
}

// Verify counts in DB
echo "\n--- Database Final Check ---\n";
$qRes = $conn->query("SELECT subject_code, COUNT(*) AS cnt, AVG(theory_total) AS avg_score FROM student_marks WHERE batch = '40DI' AND semester = 5 GROUP BY subject_code");
while ($qRow = $qRes->fetch_assoc()) {
    echo "Subject: " . $qRow['subject_code'] . " | Rows: " . $qRow['cnt'] . " | Avg Theory Total: " . round((float)$qRow['avg_score'], 2) . "\n";
}
echo "==================================================\n";
