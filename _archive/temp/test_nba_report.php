<?php
declare(strict_types=1);

function http_post_login(string $username, string $password, string $cookie_file): bool {
    // 1. GET to get CSRF token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/Iqac/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if (!$response || !preg_match('/name="csrf_token"\s+value="([^"]+)"/', $response, $matches)) {
        return false;
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
    
    return $info['http_code'] === 302;
}

function fetch_report(string $cookie_file, string $url): string {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response ?: '';
}

$cookie = tempnam(sys_get_temp_dir(), 'cookie_');
if (http_post_login('admin40', 'admin123', $cookie)) {
    echo "Admin logged in successfully.\n";
    
    // Test Year 1 (Semesters 1-2)
    echo "\nFetching NBA Batch Report for 40DI (Year 1)...\n";
    $html1 = fetch_report($cookie, 'http://localhost/Iqac/nba_report.php?report_type=batch&batch=40DI&study_year=1&department=' . urlencode('Diploma in Information Technology'));
    if (strpos($html1, '27') !== false) {
        echo "Year 1 Report: PASS (Contains student count 27)\n";
    } else {
        echo "Year 1 Report: FAIL (Could not find student count 27)\n";
    }
    
    // Test Year 2 (Semesters 3-4)
    echo "\nFetching NBA Batch Report for 40DI (Year 2)...\n";
    $html2 = fetch_report($cookie, 'http://localhost/Iqac/nba_report.php?report_type=batch&batch=40DI&study_year=2&department=' . urlencode('Diploma in Information Technology'));
    if (strpos($html2, '27') !== false) {
        echo "Year 2 Report: PASS (Contains student count 27)\n";
    } else {
        echo "Year 2 Report: FAIL\n";
    }
    
    // Test Year 3 (Semester 5)
    echo "\nFetching NBA Batch Report for 40DI (Year 3)...\n";
    $html3 = fetch_report($cookie, 'http://localhost/Iqac/nba_report.php?report_type=batch&batch=40DI&study_year=3&department=' . urlencode('Diploma in Information Technology'));
    file_put_contents(__DIR__ . '/report_response.html', $html3);
    if (strpos($html3, '27') !== false) {
        echo "Year 3 Report: PASS (Contains student count 27)\n";
    } else {
        echo "Year 3 Report: FAIL\n";
    }
    
    // Let's check tutor mapping display
    if (strpos($html3, 'Batch Tutor') !== false) {
        echo "Tutor display in report: PASS (Contains 'Batch Tutor')\n";
    } else {
        echo "Tutor display in report: FAIL (Could not find 'Batch Tutor')\n";
    }
} else {
    echo "Admin login failed.\n";
}
unlink($cookie);
