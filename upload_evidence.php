<?php
/**
 * PSG PTC ERP — Upload & Evidence Management API Endpoint
 */

declare(strict_types=1);

require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/evidence_helper.php';

$user = iqac_require_login(['staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
$role = iqac_effective_role($user);
$userId = (int)($user['id'] ?? 0);

$action = trim((string)($_POST['action'] ?? $_GET['action'] ?? 'upload'));

if ($action === 'delete') {
    if (!in_array($role, ['admin', 'super_admin', 'hod', 'iqac'], true)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized deletion permission.']);
        exit;
    }

    $table = trim((string)($_POST['table'] ?? ''));
    $recordId = (int)($_POST['id'] ?? 0);
    $category = trim((string)($_POST['category'] ?? 'certificates'));

    $validTables = [
        'mou_master' => 'mou_file',
        'student_sports' => 'certificate_file',
        'student_achievements' => 'certificate_file',
        'student_publications' => 'publication_file',
        'student_internships' => 'certificate_file',
        'student_projects' => 'project_file',
        'student_patents' => 'patent_file',
        'faculty_development_programs' => 'certificate_file',
        'research_publications' => 'publication_file',
        'industry_interactions' => 'evidence_file',
        'guest_lectures' => 'evidence_file'
    ];

    if (!isset($validTables[$table]) || $recordId <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid table or record ID.']);
        exit;
    }

    $colName = $validTables[$table];
    $primaryKey = ($table === 'mou_master' || $table === 'faculty_development_programs' || $table === 'research_publications' || $table === 'industry_interactions' || $table === 'guest_lectures') ? 'id' : (($table === 'student_sports' || $table === 'student_achievements' || $table === 'student_publications' || $table === 'student_internships' || $table === 'student_projects' || $table === 'student_patents') ? 'id' : 'id');

    // Get current file
    $res = $conn->query("SELECT `$colName` FROM `$table` WHERE `$primaryKey` = $recordId LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $oldFile = $row[$colName];
        if (!empty($oldFile)) {
            $filePath = IQAC_UPLOAD_BASE_DIR . '/' . $category . '/' . basename($oldFile);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
    }

    $conn->query("UPDATE `$table` SET `$colName` = NULL, `uploaded_at` = NOW(), `uploaded_by` = $userId WHERE `$primaryKey` = $recordId");

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Evidence file deleted successfully.']);
    exit;
}

if ($action === 'upload' || $action === 'replace') {
    if (!iqac_can_manage_evidence($user)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Permission denied for file upload.']);
        exit;
    }

    if (empty($_FILES['evidence_file']['name'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
        exit;
    }

    $file = $_FILES['evidence_file'];
    $table = trim((string)($_POST['table'] ?? 'student_achievements'));
    $recordId = (int)($_POST['id'] ?? 0);
    $category = trim((string)($_POST['category'] ?? 'certificates'));

    // Category folder validation
    $cleanCategory = preg_replace('/[^a-z0-9_-]/i', '', $category);
    $targetDir = IQAC_UPLOAD_BASE_DIR . '/' . $cleanCategory;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Size check (max 20MB)
    $maxSize = 20 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'File size exceeds 20MB limit.']);
        exit;
    }

    // File extension check
    $fileName = $file['name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];

    if (!in_array($ext, $allowedExts, true)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid file format. Allowed formats: JPG, PNG, JPEG, PDF.']);
        exit;
    }

    // Generate safe clean filename
    $safeBasename = preg_replace('/[^a-z0-9_.-]/i', '_', pathinfo($fileName, PATHINFO_FILENAME));
    $newFileName = $safeBasename . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
    $targetFilePath = $targetDir . '/' . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
        exit;
    }

    // Verify MIME type security check
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $targetFilePath);
        finfo_close($finfo);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        if (!in_array($mime, $allowedMimes, true)) {
            @unlink($targetFilePath);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Security Error: Invalid MIME type detected.']);
            exit;
        }
    }

    $validTables = [
        'mou_master' => 'mou_file',
        'student_sports' => 'certificate_file',
        'student_achievements' => 'certificate_file',
        'student_publications' => 'publication_file',
        'student_internships' => 'certificate_file',
        'student_projects' => 'project_file',
        'student_patents' => 'patent_file',
        'faculty_development_programs' => 'certificate_file',
        'research_publications' => 'publication_file',
        'industry_interactions' => 'evidence_file',
        'guest_lectures' => 'evidence_file'
    ];

    if (isset($validTables[$table]) && $recordId > 0) {
        $colName = $validTables[$table];
        $storedRelPath = 'uploads/' . $cleanCategory . '/' . $newFileName;
        $conn->query("UPDATE `$table` SET `$colName` = '$storedRelPath', `uploaded_at` = NOW(), `uploaded_by` = $userId WHERE `id` = $recordId");
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Evidence file uploaded successfully.',
        'file_url' => 'uploads/' . $cleanCategory . '/' . $newFileName,
        'filename' => $newFileName
    ]);
    exit;
}
