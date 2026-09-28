<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';
require_once __DIR__ . '/include/nba_document_export.php';

$user = iqac_require_login(['student', 'staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
$role = iqac_effective_role($user);
$message = '';
$error = '';

function marks_grade_point(?string $grade): float
{
    return match (strtoupper(trim((string)$grade))) {
        'O' => 10.0,
        'A+' => 9.0,
        'A' => 8.0,
        'B+' => 7.0,
        'B' => 6.0,
        'C' => 5.0,
        default => 0.0,
    };
}

function marks_best_two(float $ca1, float $ca2, float $ca3): float
{
    $marks = [$ca1, $ca2, $ca3];
    rsort($marks, SORT_NUMERIC);
    return round(($marks[0] + $marks[1]) / 2, 2);
}

function marks_current_staff_id(mysqli $conn, int $userId): ?int
{
    $stmt = $conn->prepare('SELECT staff_id FROM staff_details WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int)$row['staff_id'] : null;
}

function marks_current_student(mysqli $conn, int $userId): ?array
{
    $stmt = $conn->prepare('SELECT * FROM student_details WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function marks_academic_year(): string
{
    global $conn;
    return nba_default_academic_year($conn);
}

function marks_subject_compat_id(string $subjectCode): int
{
    return (int)(sprintf('%u', crc32($subjectCode)) % 2147483647);
}

function marks_entry_window_open(mysqli $conn, array $student, string $subjectCode): bool
{
    $semester = (int)$student['current_semester'];
    $classYear = (string)($student['class_year'] ?? '');
    $batch = (string)($student['batch'] ?? '');
    $section = (string)($student['section'] ?? '');
    $stmt = $conn->prepare("SELECT id FROM mark_entry_windows
        WHERE is_active = 1
          AND NOW() BETWEEN start_at AND end_at
          AND (subject_code = ? OR subject_code IS NULL OR subject_code = '')
          AND (semester = ? OR semester IS NULL)
          AND (class_year = ? OR class_year IS NULL OR class_year = '')
          AND (batch = ? OR batch IS NULL OR batch = '')
          AND (section = ? OR section IS NULL OR section = '')
        LIMIT 1");
    $stmt->bind_param('sisss', $subjectCode, $semester, $classYear, $batch, $section);
    $stmt->execute();
    $open = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $open;
}

function marks_staff_can_access(mysqli $conn, int $staffId, string $subjectCode, ?string $classYear): bool
{
    $stmt = $conn->prepare('SELECT allocation_id FROM staff_subject_allocation WHERE staff_id = ? AND subject_code = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci AND (class_year = ? OR class_year = "" OR class_year IS NULL) LIMIT 1');
    $stmt->bind_param('iss', $staffId, $subjectCode, $classYear);
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $ok;
}

function marks_notify(mysqli $conn, ?int $userId, ?string $role, string $title, string $message, string $link = 'marks_entry.php'): void
{
    if (!iqac_table_exists($conn, 'notifications')) {
        return;
    }
    $stmt = $conn->prepare('INSERT INTO notifications (user_id, role, title, message, link_url) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('issss', $userId, $role, $title, $message, $link);
    $stmt->execute();
    $stmt->close();
}

function marks_report_label(string $entryMode): string
{
    return match ($entryMode) {
        'practical' => 'PRACTICAL MARKS REPORT',
        'semester' => 'SEMESTER GRADE REPORT',
        default => 'CA MARKS REPORT',
    };
}

function marks_export_csv(array $rows, string $entryMode): void
{
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $entryMode . '_marks_report.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, marks_nba_headers($entryMode));
    foreach ($rows as $row) {
        fputcsv($out, array_slice(marks_nba_row($row, 0, $entryMode), 1));
    }
    fclose($out);
    exit;
}

function marks_nba_meta(array $rows, array $user, string $academicYear): array
{
    global $conn;
    $first = $rows[0] ?? [];
    $department = (string)($first['department'] ?? '');
    
    $hodName = '';
    if ($department !== '') {
        $hRes = $conn->query("
            SELECT sd.full_name 
            FROM staff_details sd
            JOIN users u ON u.id = sd.user_id
            WHERE u.role = 'hod' AND sd.department = '" . $conn->real_escape_string($department) . "'
            LIMIT 1
        ");
        if ($hRes && ($hRow = $hRes->fetch_assoc())) {
            $hodName = $hRow['full_name'];
        }
    }
    if ($hodName === '') {
        $hodName = 'Department HOD';
    }

    $tutorName = (string)($first['tutor_name'] ?? '');
    if ($tutorName === 'Unassigned' || $tutorName === '') {
        $tutorName = 'Assigned Tutor';
    }

    return [
        'Department Name' => (string)($first['department'] ?? 'All Departments'),
        'Academic Year' => $academicYear,
        'Semester' => (string)($first['semester'] ?? 'All'),
        'Batch' => (string)($first['batch'] ?? 'All'),
        'Section' => (string)($first['section'] ?? 'All'),
        'Faculty Name' => (string)($first['faculty_name'] ?? 'All / Assigned Faculty'),
        'Tutor Name' => $tutorName,
        'HOD Name' => $hodName,
        'Prepared By' => (string)($user['username'] ?? 'ERP User'),
        'Generated Date' => date('Y-m-d H:i:s'),
        'Report ID' => nba_doc_report_id('MARKS'),
    ];
}

function marks_nba_headers(string $entryMode): array
{
    return match ($entryMode) {
        'practical' => ['S.No', 'Register No', 'Student Name', 'Subject Code', 'Subject / Faculty', 'Cycle 1 Execution', 'Cycle 1 Test', 'Cycle 2 Record', 'Cycle 2 Test', 'Practical Total', 'Status'],
        'semester' => ['S.No', 'Register No', 'Student Name', 'Subject Code', 'Subject Name', 'Grade', 'Grade Point', 'Arrear', 'Status'],
        default => ['S.No', 'Register No', 'Student Name', 'Subject Code', 'Subject / Faculty', 'CA1', 'CA2', 'CA3', 'Best Two', 'Converted / 30', 'Assignment', 'Internal Total', 'Status'],
    };
}

function marks_nba_row(array $row, int $index, string $entryMode): array
{
    if ($entryMode === 'practical') {
        return [
            $index,
            (string)($row['roll_number'] ?? ''),
            (string)($row['student_name'] ?? ''),
            (string)($row['subject_code'] ?? ''),
            trim((string)($row['subject_name'] ?? '') . ' / ' . (string)($row['faculty_name'] ?? 'Unassigned')),
            (string)($row['cycle1_execution'] ?? ''),
            (string)($row['cycle1_test'] ?? ''),
            (string)($row['cycle2_record'] ?? ''),
            (string)($row['cycle2_test'] ?? ''),
            (string)($row['practical_total'] ?? ''),
            (string)($row['status'] ?? ''),
        ];
    }
    if ($entryMode === 'semester') {
        return [
            $index,
            (string)($row['roll_number'] ?? ''),
            (string)($row['student_name'] ?? ''),
            (string)($row['subject_code'] ?? ''),
            (string)($row['subject_name'] ?? ''),
            (string)($row['semester_grade'] ?? ''),
            (string)($row['grade_point'] ?? ''),
            ((int)($row['is_arrear'] ?? 0) ? 'Yes' : 'No'),
            (string)($row['status'] ?? ''),
        ];
    }
    return [
        $index,
        (string)($row['roll_number'] ?? ''),
        (string)($row['student_name'] ?? ''),
        (string)($row['subject_code'] ?? ''),
        trim((string)($row['subject_name'] ?? '') . ' / ' . (string)($row['faculty_name'] ?? 'Unassigned')),
        (string)($row['ca1'] ?? ''),
        (string)($row['ca2'] ?? ''),
        (string)($row['ca3'] ?? ''),
        (string)($row['best_two'] ?? ''),
        (string)($row['ca_converted_30'] ?? ''),
        (string)($row['assignment_mark'] ?? ''),
        (string)($row['theory_total'] ?? ''),
        (string)($row['status'] ?? ''),
    ];
}

function marks_nba_rows(array $rows, string $entryMode): array
{
    $exportRows = [];
    $index = 1;
    foreach ($rows as $row) {
        $exportRows[] = marks_nba_row($row, $index++, $entryMode);
    }
    return $exportRows;
}

function marks_export_excel(array $rows, array $user, string $academicYear, string $entryMode): void
{
    nba_doc_export_excel($entryMode . '_nba_marks_report.xls', marks_report_label($entryMode), marks_nba_meta($rows, $user, $academicYear), marks_nba_headers($entryMode), marks_nba_rows($rows, $entryMode));
}

function marks_export_pdf(array $rows, array $user, string $academicYear, string $entryMode): void
{
    nba_doc_export_pdf($entryMode . '_nba_marks_report.pdf', marks_report_label($entryMode), marks_nba_meta($rows, $user, $academicYear), marks_nba_headers($entryMode), marks_nba_rows($rows, $entryMode));
}

function marks_export_docx(array $rows, array $user, string $academicYear, string $entryMode): void
{
    nba_doc_export_docx($entryMode . '_nba_marks_report.docx', marks_report_label($entryMode), marks_nba_meta($rows, $user, $academicYear), marks_nba_headers($entryMode), marks_nba_rows($rows, $entryMode));
}

$isAdmin = in_array($role, ['admin', 'super_admin', 'hod', 'iqac'], true);
$staffId = marks_current_staff_id($conn, $user['id']);
$student = $role === 'student' ? marks_current_student($conn, $user['id']) : null;
$academicYear = marks_academic_year();
$token = iqac_csrf_token();
$modeVal = $_GET['mode'] ?? 'ca';
$entryMode = in_array($modeVal, ['ca', 'practical', 'semester'], true) ? (string)$modeVal : 'ca';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!iqac_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'save_bulk_marks' && $role !== 'student') {
        $subjectCode = trim((string)($_POST['subject_code'] ?? ''));
        $semester = (int)($_POST['semester'] ?? 0);
        $academicYear = marks_academic_year();
        $classYear = trim((string)($_POST['class_year'] ?? ''));
        
        $isAuthorized = true;
        if (!$isAdmin && $role === 'staff') {
            $isAuthorized = marks_staff_can_access($conn, $staffId, $subjectCode, $classYear ?: null);
        }
        
        if (!$isAuthorized) {
            $error = 'You are not allocated to this subject.';
        } elseif (empty($subjectCode)) {
            $error = 'Subject code is required.';
        } else {
            $typeStmt = $conn->prepare("SELECT subject_type FROM subjects WHERE subject_code = ? LIMIT 1");
            $typeStmt->bind_param('s', $subjectCode);
            $typeStmt->execute();
            $tRow = $typeStmt->get_result()->fetch_assoc();
            $typeStmt->close();
            $subType = $tRow ? $tRow['subject_type'] : 'theory';
            
            $studentIdsPost = $_POST['student_ids'] ?? [];
            foreach ($studentIdsPost as $sId) {
                $sId = (int)$sId;
                
                $checkLock = $conn->prepare("SELECT status FROM student_marks WHERE student_id = ? AND subject_code = ? LIMIT 1");
                $checkLock->bind_param('is', $sId, $subjectCode);
                $checkLock->execute();
                $lockRow = $checkLock->get_result()->fetch_assoc();
                $checkLock->close();
                if ($lockRow && in_array($lockRow['status'], ['verified', 'locked'], true)) {
                    continue;
                }
                
                $sStmt = $conn->prepare("SELECT current_semester, class_year, batch, section FROM student_details WHERE student_id = ? LIMIT 1");
                $sStmt->bind_param('i', $sId);
                $sStmt->execute();
                $sDetails = $sStmt->get_result()->fetch_assoc();
                $sStmt->close();
                
                if ($sDetails) {
                    $ca1 = max(0, min(50, (float)($_POST['ca1'][$sId] ?? 0)));
                    $ca2 = max(0, min(50, (float)($_POST['ca2'][$sId] ?? 0)));
                    $ca3 = max(0, min(50, (float)($_POST['ca3'][$sId] ?? 0)));
                    $assignment = max(0, min(10, (float)($_POST['assignment_mark'][$sId] ?? 0)));
                    
                    $cycle1Execution = max(0, min(10, (float)($_POST['cycle1_execution'][$sId] ?? 0)));
                    $cycle1Test = max(0, min(10, (float)($_POST['cycle1_test'][$sId] ?? 0)));
                    $cycle2Record = max(0, min(10, (float)($_POST['cycle2_record'][$sId] ?? 0)));
                    $cycle2Test = max(0, min(10, (float)($_POST['cycle2_test'][$sId] ?? 0)));
                    
                    $grade = strtoupper(trim((string)($_POST['semester_grade'][$sId] ?? '')));
                    
                    $bestTwo = marks_best_two($ca1, $ca2, $ca3);
                    $converted = round(($bestTwo / 50) * 30, 2);
                    $theoryTotal = min(40.0, $converted + $assignment);
                    $practicalTotal = min(40.0, $cycle1Execution + $cycle1Test + $cycle2Record + $cycle2Test);
                    $gradePoint = marks_grade_point($grade);
                    $isArrear = ($grade === 'RA') ? 1 : 0;
                    
                    $status = 'submitted';
                    $subjectIdCompat = marks_subject_compat_id($subjectCode);
                    
                    $sql = "INSERT INTO student_marks
                        (student_id, subject_id, subject_code, semester, academic_year, class_year, batch, section, ca1, ca2, ca3,
                         best_two, ca_converted_30, assignment_mark, theory_total, cycle1_execution, cycle1_test,
                         cycle2_record, cycle2_test, practical_total, semester_grade, grade_point, is_arrear, status,
                         entered_by, submitted_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE
                         ca1=VALUES(ca1), ca2=VALUES(ca2), ca3=VALUES(ca3), best_two=VALUES(best_two),
                         ca_converted_30=VALUES(ca_converted_30), assignment_mark=VALUES(assignment_mark),
                         theory_total=VALUES(theory_total), cycle1_execution=VALUES(cycle1_execution),
                         cycle1_test=VALUES(cycle1_test), cycle2_record=VALUES(cycle2_record),
                         cycle2_test=VALUES(cycle2_test), practical_total=VALUES(practical_total),
                         semester_grade=VALUES(semester_grade), grade_point=VALUES(grade_point),
                         is_arrear=VALUES(is_arrear), status=VALUES(status), entered_by=VALUES(entered_by),
                         submitted_at=NOW(), staff_comment=NULL";
                         
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param(
                        'iisissssddddddddddddsdisi',
                        $sId,
                        $subjectIdCompat,
                        $subjectCode,
                        $sDetails['current_semester'],
                        $academicYear,
                        $sDetails['class_year'],
                        $sDetails['batch'],
                        $sDetails['section'],
                        $ca1,
                        $ca2,
                        $ca3,
                        $bestTwo,
                        $converted,
                        $assignment,
                        $theoryTotal,
                        $cycle1Execution,
                        $cycle1Test,
                        $cycle2Record,
                        $cycle2Test,
                        $practicalTotal,
                        $grade,
                        $gradePoint,
                        $isArrear,
                        $status,
                        $user['id']
                    );
                    $stmt->execute();
                    $stmt->close();
                }
            }
            $message = 'Marks updated successfully.';
        }
    } elseif (($_POST['action'] ?? '') === 'save_student_marks' && $role === 'student') {
        if (!$student) {
            $error = 'Student profile not found.';
        } else {
            $subjectCode = trim((string)($_POST['subject_code'] ?? ''));
            $semester = (int)$student['current_semester'];
            $classYear = (string)$student['class_year'];
            $entryMode = in_array(($_POST['entry_mode'] ?? 'ca'), ['ca', 'practical', 'semester'], true) ? (string)$_POST['entry_mode'] : 'ca';
            $status = ($_POST['submit_mode'] ?? '') === 'draft' ? 'draft' : 'submitted';

            $subjectStmt = $conn->prepare('SELECT subject_code FROM subjects WHERE subject_code = ? AND semester = ? LIMIT 1');
            $subjectStmt->bind_param('si', $subjectCode, $semester);
            $subjectStmt->execute();
            $subjectOk = $subjectStmt->get_result()->num_rows > 0;
            $subjectStmt->close();

            if (!$subjectOk) {
                $error = 'Invalid subject for your current semester.';
            } elseif (!marks_entry_window_open($conn, $student, $subjectCode)) {
                $error = 'Mark Entry Window Closed';
            } else {
                $ca1 = max(0, min(50, (float)($_POST['ca1'] ?? 0)));
                $ca2 = max(0, min(50, (float)($_POST['ca2'] ?? 0)));
                $ca3 = max(0, min(50, (float)($_POST['ca3'] ?? 0)));
                $assignment = max(0, min(10, (float)($_POST['assignment_mark'] ?? 0)));
                $cycle1Execution = max(0, min(10, (float)($_POST['cycle1_execution'] ?? 0)));
                $cycle1Test = max(0, min(10, (float)($_POST['cycle1_test'] ?? 0)));
                $cycle2Record = max(0, min(10, (float)($_POST['cycle2_record'] ?? 0)));
                $cycle2Test = max(0, min(10, (float)($_POST['cycle2_test'] ?? 0)));
                $grade = strtoupper(trim((string)($_POST['semester_grade'] ?? '')));
                $bestTwo = marks_best_two($ca1, $ca2, $ca3);
                $converted = round(($bestTwo / 50) * 30, 2);
                $theoryTotal = min(40, $converted + $assignment);
                $practicalTotal = min(40, $cycle1Execution + $cycle1Test + $cycle2Record + $cycle2Test);
                $gradePoint = marks_grade_point($grade);
                $isArrear = $grade === 'RA' ? 1 : 0;
                $submittedAt = $status === 'submitted' ? date('Y-m-d H:i:s') : null;
                $existing = null;
                $check = $conn->prepare('SELECT * FROM student_marks WHERE student_id = ? AND subject_code = ? AND semester = ? AND academic_year = ? LIMIT 1');
                $check->bind_param('isis', $student['student_id'], $subjectCode, $semester, $academicYear);
                $check->execute();
                $existing = $check->get_result()->fetch_assoc();
                $check->close();

                if ($existing && in_array($existing['status'], ['verified', 'locked'], true)) {
                    $error = 'Verified or locked marks are read-only.';
                } else {
                $existing = $existing ?: [];
                if ($entryMode !== 'ca') {
                    $ca1 = (float)($existing['ca1'] ?? 0);
                    $ca2 = (float)($existing['ca2'] ?? 0);
                    $ca3 = (float)($existing['ca3'] ?? 0);
                    $assignment = (float)($existing['assignment_mark'] ?? 0);
                }
                if ($entryMode !== 'practical') {
                    $cycle1Execution = (float)($existing['cycle1_execution'] ?? 0);
                    $cycle1Test = (float)($existing['cycle1_test'] ?? 0);
                    $cycle2Record = (float)($existing['cycle2_record'] ?? 0);
                    $cycle2Test = (float)($existing['cycle2_test'] ?? 0);
                }
                if ($entryMode !== 'semester') {
                    $grade = strtoupper(trim((string)($existing['semester_grade'] ?? '')));
                }
                $subjectIdCompat = marks_subject_compat_id($subjectCode);

                $sql = "INSERT INTO student_marks
                    (student_id, subject_id, subject_code, semester, academic_year, class_year, ca1, ca2, ca3,
                     best_two, ca_converted_30, assignment_mark, theory_total, cycle1_execution, cycle1_test,
                     cycle2_record, cycle2_test, practical_total, semester_grade, grade_point, is_arrear, status,
                     entered_by, submitted_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                     ca1=VALUES(ca1), ca2=VALUES(ca2), ca3=VALUES(ca3), best_two=VALUES(best_two),
                     ca_converted_30=VALUES(ca_converted_30), assignment_mark=VALUES(assignment_mark),
                     theory_total=VALUES(theory_total), cycle1_execution=VALUES(cycle1_execution),
                     cycle1_test=VALUES(cycle1_test), cycle2_record=VALUES(cycle2_record),
                     cycle2_test=VALUES(cycle2_test), practical_total=VALUES(practical_total),
                     semester_grade=VALUES(semester_grade), grade_point=VALUES(grade_point),
                     is_arrear=VALUES(is_arrear), status=VALUES(status), entered_by=VALUES(entered_by),
                     submitted_at=VALUES(submitted_at), staff_comment=NULL";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    'iisissddddddddddddsdisis',
                    $student['student_id'],
                    $subjectIdCompat,
                    $subjectCode,
                    $semester,
                    $academicYear,
                    $classYear,
                    $ca1,
                    $ca2,
                    $ca3,
                    $bestTwo,
                    $converted,
                    $assignment,
                    $theoryTotal,
                    $cycle1Execution,
                    $cycle1Test,
                    $cycle2Record,
                    $cycle2Test,
                    $practicalTotal,
                    $grade,
                    $gradePoint,
                    $isArrear,
                    $status,
                    $user['id'],
                    $submittedAt
                );
                $stmt->execute();
                $stmt->close();

                if ($status === 'submitted') {
                    marks_notify($conn, null, 'staff', 'Marks submitted', $student['full_name'] . ' submitted marks for ' . $subjectCode);
                    iqac_audit($conn, 'marks_submitted', $user['id'], $role, $subjectCode);
                }
                $message = $status === 'draft' ? 'Draft saved.' : 'Marks submitted for verification.';
                }
            }
        }
    } elseif (($_POST['action'] ?? '') === 'review_marks' && $role !== 'student') {
        $markId = (int)($_POST['mark_id'] ?? 0);
        $decision = $_POST['decision'] ?? 'verified';
        $allowedDecision = in_array($decision, ['verified', 'returned', 'locked', 'unlocked'], true);
        $comment = trim((string)($_POST['staff_comment'] ?? ''));

        $stmt = $conn->prepare('SELECT sm.*, sd.user_id AS student_user_id, sd.class_year FROM student_marks sm JOIN student_details sd ON sd.student_id = sm.student_id WHERE sm.id = ? LIMIT 1');
        $stmt->bind_param('i', $markId);
        $stmt->execute();
        $mark = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$mark || !$allowedDecision) {
            $error = 'Invalid mark record or action.';
        } elseif (!$isAdmin && (!$staffId || !marks_staff_can_access($conn, $staffId, (string)$mark['subject_code'], $mark['class_year']))) {
            $error = 'You are not assigned to this subject.';
        } elseif ($decision === 'locked' && !$isAdmin) {
            $error = 'Only admin roles can lock marks.';
        } else {
            if ($decision === 'unlocked') {
                $stmt = $conn->prepare("UPDATE student_marks SET status='verified', staff_comment=?, locked_by_user_id=NULL, locked_at=NULL WHERE id=?");
                $stmt->bind_param('si', $comment, $markId);
                $noticeTitle = 'Marks unlocked';
            } elseif ($decision === 'locked') {
                $stmt = $conn->prepare("UPDATE student_marks SET status='locked', staff_comment=?, locked_by_user_id=?, locked_at=NOW() WHERE id=?");
                $stmt->bind_param('sii', $comment, $user['id'], $markId);
                $noticeTitle = 'Marks locked';
            } else {
                $stmt = $conn->prepare('UPDATE student_marks SET status=?, staff_comment=?, verified_by=?, verified_at=NOW() WHERE id=?');
                $verifyStaff = $staffId ?: 0;
                $stmt->bind_param('ssii', $decision, $comment, $verifyStaff, $markId);
                $noticeTitle = $decision === 'verified' ? 'Marks verified' : 'Marks returned';
            }
            $stmt->execute();
            $stmt->close();
            marks_notify($conn, (int)$mark['student_user_id'], null, $noticeTitle, 'Your ' . $mark['subject_code'] . ' marks were ' . $decision . '.', 'marks_entry.php');
            iqac_audit($conn, 'marks_' . $decision, $user['id'], $role, 'Mark ID ' . $markId);
            $message = 'Mark record updated.';
        }
    } elseif (($_POST['action'] ?? '') === 'create_window' && ($isAdmin || $role === 'staff')) {
        $subjectCode = trim((string)($_POST['window_subject_code'] ?? ''));
        $semester = (int)($_POST['window_semester'] ?? 0);
        $classYear = trim((string)($_POST['window_class_year'] ?? ''));
        $batch = trim((string)($_POST['window_batch'] ?? ''));
        $section = trim((string)($_POST['window_section'] ?? ''));
        $startAt = trim((string)($_POST['start_at'] ?? ''));
        $endAt = trim((string)($_POST['end_at'] ?? ''));
        if (!$isAdmin && (!$staffId || !marks_staff_can_access($conn, $staffId, $subjectCode, $classYear))) {
            $error = 'You can create windows only for assigned subjects.';
        } elseif ($subjectCode === '' || !$semester || $startAt === '' || $endAt === '') {
            $error = 'Subject, semester, start date, and end date are required.';
        } else {
            $title = $subjectCode . ' mark entry window';
            $stmt = $conn->prepare('INSERT INTO mark_entry_windows (title, subject_code, semester, class_year, batch, section, start_at, end_at, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)');
            $stmt->bind_param('ssisssssi', $title, $subjectCode, $semester, $classYear, $batch, $section, $startAt, $endAt, $user['id']);
            $stmt->execute();
            $stmt->close();
            $message = 'Mark entry window created.';
        }
    }
}

$studentSubjects = [];
if ($student) {
    $stmt = $conn->prepare('SELECT subject_code, subject_name, subject_type FROM subjects WHERE semester = ? ORDER BY subject_code');
    $stmt->bind_param('i', $student['current_semester']);
    $stmt->execute();
    $studentSubjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$filterSubject = trim((string)($_GET['subject_code'] ?? ''));
$filterClassYear = trim((string)($_GET['class_year'] ?? ''));
$filterSemester = (int)($_GET['semester'] ?? 0);
$filterStatus = trim((string)($_GET['status'] ?? ''));

$marks = [];
if ($role !== 'student' && $filterSubject !== '') {
    $isAuthorized = true;
    if (!$isAdmin && $role === 'staff') {
        $isAuthorized = marks_staff_can_access($conn, (int)$staffId, $filterSubject, $filterClassYear ?: null);
    }
    
    if (!$isAuthorized) {
        $error = 'You are not allocated to this subject.';
    } else {
        $subName = '';
        $subType = 'theory';
        $sStmt = $conn->prepare("SELECT subject_name, subject_type FROM subjects WHERE subject_code = ? LIMIT 1");
        $sStmt->bind_param('s', $filterSubject);
        $sStmt->execute();
        $sRow = $sStmt->get_result()->fetch_assoc();
        $sStmt->close();
        if ($sRow) {
            $subName = $sRow['subject_name'];
            $subType = $sRow['subject_type'];
        }
        
        $qWhere = [];
        $qTypes = '';
        $qParams = [];
        
        if ($filterClassYear !== '') {
            $qWhere[] = 'sd.class_year = ?';
            $qTypes .= 's';
            $qParams[] = $filterClassYear;
        }
        if ($filterSemester > 0) {
            $qWhere[] = 'sd.current_semester = ?';
            $qTypes .= 'i';
            $qParams[] = $filterSemester;
        }
        if (!$isAdmin && $role === 'tutor' && $staffId) {
            $qWhere[] = 'sd.tutor_staff_id = ?';
            $qTypes .= 'i';
            $qParams[] = $staffId;
        }
        
        if (!empty($user['department']) && $role !== 'admin' && $role !== 'super_admin') {
            $qWhere[] = 'sd.department = ?';
            $qTypes .= 's';
            $qParams[] = $user['department'];
        }
        
        $qWhereSql = $qWhere ? 'WHERE ' . implode(' AND ', $qWhere) : '';
        
        $sql = "SELECT 
                    COALESCE(sm.id, 0) AS id,
                    sd.student_id,
                    sd.full_name AS student_name,
                    sd.roll_number,
                    sd.class_year,
                    sd.department,
                    sd.batch,
                    sd.section,
                    sd.tutor_staff_id,
                    COALESCE(sm.ca1, 0.0) AS ca1,
                    COALESCE(sm.ca2, 0.0) AS ca2,
                    COALESCE(sm.ca3, 0.0) AS ca3,
                    COALESCE(sm.best_two, 0.0) AS best_two,
                    COALESCE(sm.ca_converted_30, 0.0) AS ca_converted_30,
                    COALESCE(sm.assignment_mark, 0.0) AS assignment_mark,
                    COALESCE(sm.theory_total, 0.0) AS theory_total,
                    COALESCE(sm.cycle1_execution, 0.0) AS cycle1_execution,
                    COALESCE(sm.cycle1_test, 0.0) AS cycle1_test,
                    COALESCE(sm.cycle2_record, 0.0) AS cycle2_record,
                    COALESCE(sm.cycle2_test, 0.0) AS cycle2_test,
                    COALESCE(sm.practical_total, 0.0) AS practical_total,
                    COALESCE(sm.semester_grade, '') AS semester_grade,
                    COALESCE(sm.grade_point, 0.0) AS grade_point,
                    COALESCE(sm.is_arrear, 0) AS is_arrear,
                    COALESCE(sm.status, 'draft') AS status,
                    COALESCE(sm.staff_comment, '') AS staff_comment,
                    ? AS subject_code,
                    ? AS subject_name,
                    ? AS subject_type,
                    COALESCE((SELECT st.full_name FROM staff_subject_allocation ssa2 JOIN staff_details st ON st.staff_id = ssa2.staff_id WHERE ssa2.subject_code COLLATE utf8mb4_unicode_ci = ? LIMIT 1), 'Unassigned') AS faculty_name,
                    COALESCE((SELECT tutor.full_name FROM staff_details tutor WHERE tutor.staff_id = sd.tutor_staff_id LIMIT 1), 'Unassigned') AS tutor_name
                FROM student_details sd
                LEFT JOIN student_marks sm ON sm.student_id = sd.student_id AND sm.subject_code = ?
                $qWhereSql
                ORDER BY sd.roll_number ASC";
                
        $stmt = $conn->prepare($sql);
        $allParams = array_merge([$filterSubject, $subName, $subType, $filterSubject, $filterSubject], $qParams);
        $allTypes = 'sssss' . $qTypes;
        $stmt->bind_param($allTypes, ...$allParams);
        $stmt->execute();
        $marks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} else {
    $where = [];
    $types = '';
    $params = [];
    $joins = 'JOIN student_details sd ON sd.student_id = sm.student_id JOIN subjects sub ON sub.subject_code = sm.subject_code COLLATE utf8mb4_unicode_ci';

    if ($role === 'student' && $student) {
        $where[] = 'sm.student_id = ?';
        $types .= 'i';
        $params[] = (int)$student['student_id'];
    } elseif (!$isAdmin && $role === 'tutor' && $staffId) {
        $where[] = 'sd.tutor_staff_id = ?';
        $types .= 'i';
        $params[] = $staffId;
    } elseif (!$isAdmin && $staffId) {
        $joins .= ' JOIN staff_subject_allocation ssa ON ssa.subject_code = sm.subject_code COLLATE utf8mb4_unicode_ci AND ssa.staff_id = ? AND (ssa.class_year = sd.class_year OR ssa.class_year = "" OR ssa.class_year IS NULL)';
        $types .= 'i';
        $params[] = $staffId;
    } elseif (!$isAdmin) {
        $where[] = '1=0';
    }

    if ($filterSubject !== '') {
        $where[] = 'sm.subject_code = ?';
        $types .= 's';
        $params[] = $filterSubject;
    }
    if ($filterClassYear !== '') {
        $where[] = 'sd.class_year = ?';
        $types .= 's';
        $params[] = $filterClassYear;
    }
    if ($filterSemester > 0) {
        $where[] = 'sm.semester = ?';
        $types .= 'i';
        $params[] = $filterSemester;
    }
    if ($filterStatus !== '') {
        $where[] = 'sm.status = ?';
        $types .= 's';
        $params[] = $filterStatus;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "SELECT sm.*, sd.full_name AS student_name, sd.roll_number, sd.class_year, sd.department, sd.batch, sd.section, sd.tutor_staff_id,
                   sub.subject_name, sub.subject_type,
                   COALESCE((SELECT st.full_name FROM staff_subject_allocation ssa2 JOIN staff_details st ON st.staff_id = ssa2.staff_id WHERE ssa2.subject_code COLLATE utf8mb4_unicode_ci = sm.subject_code COLLATE utf8mb4_unicode_ci LIMIT 1), 'Unassigned') AS faculty_name,
                   COALESCE((SELECT tutor.full_name FROM staff_details tutor WHERE tutor.staff_id = sd.tutor_staff_id LIMIT 1), 'Unassigned') AS tutor_name
            FROM student_marks sm $joins $whereSql
            ORDER BY sm.updated_at DESC";
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $marks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

if (isset($_GET['export'])) {
    if ($_GET['export'] === 'csv') {
        marks_export_csv($marks, $entryMode);
    }
    if ($_GET['export'] === 'excel') {
        marks_export_excel($marks, $user, $academicYear, $entryMode);
    }
    if ($_GET['export'] === 'pdf') {
        marks_export_pdf($marks, $user, $academicYear, $entryMode);
    }
    if ($_GET['export'] === 'docx') {
        marks_export_docx($marks, $user, $academicYear, $entryMode);
    }
}

$subjects = [];
if ($isAdmin) {
    $result = $conn->query('SELECT subject_code, subject_name, semester, year FROM subjects ORDER BY semester, subject_code');
    $subjects = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
} elseif ($staffId) {
    $stmt = $conn->prepare('SELECT DISTINCT sub.subject_code, sub.subject_name, sub.semester, ssa.class_year FROM staff_subject_allocation ssa JOIN subjects sub ON sub.subject_code = ssa.subject_code WHERE ssa.staff_id = ? ORDER BY sub.semester, sub.subject_code');
    $stmt->bind_param('i', $staffId);
    $stmt->execute();
    $subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$windows = [];
if ($role !== 'student') {
    $result = $conn->query('SELECT * FROM mark_entry_windows ORDER BY created_at DESC LIMIT 10');
    $windows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Marks Workflow</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/erp.css">
</head>
<body>
<div class="erp-layout">
    <?php iqac_render_sidebar($user, 'marks_entry.php'); ?>
    <main class="erp-main">
        <header class="erp-topbar">
            <div><h1>Marks Workflow</h1><span>Student entry, staff verification, tutor monitoring, admin lock.</span></div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a class="secondary-btn" href="marks_entry.php?mode=<?= htmlspecialchars($entryMode) ?>&export=csv">CSV</a>
                <a class="secondary-btn" href="marks_entry.php?mode=<?= htmlspecialchars($entryMode) ?>&export=excel">Excel</a>
                <a class="secondary-btn" href="marks_entry.php?mode=<?= htmlspecialchars($entryMode) ?>&export=pdf">PDF</a>
                <a class="secondary-btn" href="marks_entry.php?mode=<?= htmlspecialchars($entryMode) ?>&export=docx">DOCX</a>
                <button class="secondary-btn" type="button" onclick="window.print()">Print</button>
            </div>
        </header>
        <section class="erp-content">
            <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="tab-row">
                <a class="secondary-btn <?= $entryMode === 'ca' ? 'active' : '' ?>" href="marks_entry.php?mode=ca">CA Entry</a>
                <a class="secondary-btn <?= $entryMode === 'practical' ? 'active' : '' ?>" href="marks_entry.php?mode=practical">Practical Entry</a>
                <a class="secondary-btn <?= $entryMode === 'semester' ? 'active' : '' ?>" href="marks_entry.php?mode=semester">Semester Grade Entry</a>
            </div>

            <?php if ($role === 'student' && $student): ?>
            <section class="panel">
                <div class="panel-header"><h2><?= $entryMode === 'ca' ? 'CA Entry' : ($entryMode === 'practical' ? 'Practical Entry' : 'Semester Grade Entry') ?></h2><span class="badge gold"><?= htmlspecialchars($student['class_year']) ?> - Semester <?= (int)$student['current_semester'] ?></span></div>
                <form method="post" class="erp-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="action" value="save_student_marks">
                    <input type="hidden" name="entry_mode" value="<?= htmlspecialchars($entryMode) ?>">
                    <div class="form-grid">
                        <label><span>Subject</span><select name="subject_code" required><option value="">Select subject</option><?php foreach ($studentSubjects as $subject): ?><option value="<?= htmlspecialchars($subject['subject_code']) ?>"><?= htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']) ?></option><?php endforeach; ?></select></label>
                        <?php if ($entryMode === 'ca'): ?>
                        <label><span>CA1 / 50</span><input name="ca1" type="number" min="0" max="50" step="0.01" required></label>
                        <label><span>CA2 / 50</span><input name="ca2" type="number" min="0" max="50" step="0.01" required></label>
                        <label><span>CA3 / 50</span><input name="ca3" type="number" min="0" max="50" step="0.01" required></label>
                        <label><span>Assignment / 10</span><input name="assignment_mark" type="number" min="0" max="10" step="0.01" required></label>
                        <?php elseif ($entryMode === 'practical'): ?>
                        <label><span>Cycle 1 Execution / 10</span><input name="cycle1_execution" type="number" min="0" max="10" step="0.01"></label>
                        <label><span>Cycle 1 Test / 10</span><input name="cycle1_test" type="number" min="0" max="10" step="0.01"></label>
                        <label><span>Cycle 2 Record / 10</span><input name="cycle2_record" type="number" min="0" max="10" step="0.01"></label>
                        <label><span>Cycle 2 Test / 10</span><input name="cycle2_test" type="number" min="0" max="10" step="0.01"></label>
                        <?php else: ?>
                        <label><span>Grade</span><select name="semester_grade" required><option value="">Select Grade</option><option>O</option><option>A+</option><option>A</option><option>B+</option><option>B</option><option>C</option><option>RA</option></select></label>
                        <?php endif; ?>
                    </div>
                    <button class="secondary-btn" name="submit_mode" value="draft" type="submit">Save Draft</button>
                    <button class="primary-btn" name="submit_mode" value="submitted" type="submit">Submit <?= $entryMode === 'ca' ? 'CA Marks' : ($entryMode === 'practical' ? 'Practical Marks' : 'Semester Grade') ?></button>
                </form>
            </section>
            <?php endif; ?>

            <?php if ($role !== 'student'): ?>
            <section class="panel">
                <div class="panel-header"><h2>Mark Entry Window</h2><span class="badge gold">Staff/Admin</span></div>
                <form method="post" class="erp-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="action" value="create_window">
                    <div class="form-grid">
                        <label><span>Subject</span><select name="window_subject_code" required><option value="">Select subject</option><?php foreach ($subjects as $subject): ?><option value="<?= htmlspecialchars($subject['subject_code']) ?>"><?= htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']) ?></option><?php endforeach; ?></select></label>
                        <label><span>Semester</span><input type="number" name="window_semester" min="1" max="8" required></label>
                        <label><span>Year</span><input name="window_class_year" placeholder="2nd Year"></label>
                        <label><span>Batch</span><input name="window_batch" placeholder="2nd Year"></label>
                        <label><span>Section</span><input name="window_section" placeholder="A"></label>
                        <label><span>Start Date</span><input type="datetime-local" name="start_at" required></label>
                        <label><span>End Date</span><input type="datetime-local" name="end_at" required></label>
                    </div>
                    <button class="primary-btn" type="submit">Create Window</button>
                </form>
                <div class="table-wrap" style="margin-top:16px"><table class="erp-table"><thead><tr><th>Subject</th><th>Semester</th><th>Year</th><th>Batch</th><th>Start</th><th>End</th></tr></thead><tbody><?php foreach ($windows as $window): ?><tr><td><?= htmlspecialchars((string)$window['subject_code']) ?></td><td><?= htmlspecialchars((string)$window['semester']) ?></td><td><?= htmlspecialchars((string)$window['class_year']) ?></td><td><?= htmlspecialchars((string)$window['batch']) ?></td><td><?= htmlspecialchars((string)$window['start_at']) ?></td><td><?= htmlspecialchars((string)$window['end_at']) ?></td></tr><?php endforeach; ?></tbody></table></div>
            </section>
            <section class="panel">
                <div class="panel-header"><h2>Filters</h2><span class="badge">Assignment scoped</span></div>
                <form method="get" class="erp-form">
                    <div class="form-grid">
                        <label><span>Subject</span><select name="subject_code"><option value="">All assigned</option><?php foreach ($subjects as $subject): ?><option value="<?= htmlspecialchars($subject['subject_code']) ?>" <?= $filterSubject === $subject['subject_code'] ? 'selected' : '' ?>><?= htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']) ?></option><?php endforeach; ?></select></label>
                        <label><span>Year</span><input name="class_year" value="<?= htmlspecialchars($filterClassYear) ?>" placeholder="1st Year"></label>
                        <label><span>Semester</span><input type="number" name="semester" value="<?= $filterSemester ?: '' ?>"></label>
                        <label><span>Status</span><select name="status"><option value="">All</option><?php foreach (['draft','submitted','verified','returned','locked'] as $status): ?><option value="<?= $status ?>" <?= $filterStatus === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select></label>
                    </div>
                    <button class="primary-btn" type="submit">Apply</button>
                    <a class="secondary-btn" href="marks_entry.php?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['mode' => $entryMode, 'export' => 'csv']))) ?>">Export CSV</a>
                    <a class="secondary-btn" href="marks_entry.php?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['mode' => $entryMode, 'export' => 'excel']))) ?>">Export Excel</a>
                    <a class="secondary-btn" href="marks_entry.php?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['mode' => $entryMode, 'export' => 'pdf']))) ?>">Export PDF</a>
                    <a class="secondary-btn" href="marks_entry.php?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['mode' => $entryMode, 'export' => 'docx']))) ?>">Export DOCX</a>
                    <button class="secondary-btn" type="button" onclick="window.print()">Print</button>
                </form>
            </section>
            <?php endif; ?>

            <section class="panel">
                <div class="panel-header"><h2><?= $entryMode === 'ca' ? 'CA Report' : ($entryMode === 'practical' ? 'Practical Report' : 'Semester Grade Report') ?></h2><span class="badge gold"><?= count($marks) ?> records</span><?php if ($role !== 'student'): ?><span class="badge" data-pending-count>Checking...</span><?php endif; ?></div>
                <div class="table-wrap">
                    <?php if ($role !== 'student'): ?>
                    <form method="post" class="erp-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                        <input type="hidden" name="action" value="save_bulk_marks">
                        <input type="hidden" name="subject_code" value="<?= htmlspecialchars($filterSubject) ?>">
                        <input type="hidden" name="semester" value="<?= (int)$filterSemester ?>">
                        <input type="hidden" name="class_year" value="<?= htmlspecialchars($filterClassYear) ?>">
                    <?php endif; ?>
                    <table class="erp-table">
                        <?php if ($entryMode === 'ca'): ?>
                        <thead><tr><th>Student</th><th>Subject</th><th>CA1</th><th>CA2</th><th>CA3</th><th>Best Two</th><th>Converted / 30</th><th>Assignment</th><th>Internal Total</th><th>Status</th><th>Comment</th><?php if ($role !== 'student' && $role !== 'tutor'): ?><th>Action</th><?php endif; ?></tr></thead>
                        <?php elseif ($entryMode === 'practical'): ?>
                        <thead><tr><th>Student</th><th>Subject</th><th>Cycle 1 Execution</th><th>Cycle 1 Test</th><th>Cycle 2 Record</th><th>Cycle 2 Test</th><th>Practical Total</th><th>Status</th><th>Comment</th><?php if ($role !== 'student' && $role !== 'tutor'): ?><th>Action</th><?php endif; ?></tr></thead>
                        <?php else: ?>
                        <thead><tr><th>Student</th><th>Subject Code</th><th>Subject Name</th><th>Grade</th><th>Grade Point</th><th>Arrear</th><th>Status</th><th>Comment</th><?php if ($role !== 'student' && $role !== 'tutor'): ?><th>Action</th><?php endif; ?></tr></thead>
                        <?php endif; ?>
                        <tbody>
                        <?php if (!$marks): ?><tr><td colspan="12">No records found.</td></tr><?php endif; ?>
                        <?php foreach ($marks as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['student_name']) ?><br><small><?= htmlspecialchars($row['roll_number']) ?></small></td>
                                <?php if ($entryMode === 'ca'): ?>
                                <td><?= htmlspecialchars($row['subject_code'] . ' - ' . $row['subject_name']) ?></td>
                                <td>
                                    <?php if ($role !== 'student' && $filterSubject !== '' && !in_array($row['status'], ['verified', 'locked'], true)): ?>
                                        <input type="hidden" name="student_ids[]" value="<?= (int)$row['student_id'] ?>">
                                        <input type="number" name="ca1[<?= $row['student_id'] ?>]" value="<?= htmlspecialchars((string)$row['ca1']) ?>" min="0" max="50" step="0.01" style="width: 70px;">
                                    <?php else: ?>
                                        <?= htmlspecialchars((string)$row['ca1']) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($role !== 'student' && $filterSubject !== '' && !in_array($row['status'], ['verified', 'locked'], true)): ?>
                                        <input type="number" name="ca2[<?= $row['student_id'] ?>]" value="<?= htmlspecialchars((string)$row['ca2']) ?>" min="0" max="50" step="0.01" style="width: 70px;">
                                    <?php else: ?>
                                        <?= htmlspecialchars((string)$row['ca2']) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($role !== 'student' && $filterSubject !== '' && !in_array($row['status'], ['verified', 'locked'], true)): ?>
                                        <input type="number" name="ca3[<?= $row['student_id'] ?>]" value="<?= htmlspecialchars((string)$row['ca3']) ?>" min="0" max="50" step="0.01" style="width: 70px;">
                                    <?php else: ?>
                                        <?= htmlspecialchars((string)$row['ca3']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string)$row['best_two']) ?></td>
                                <td><?= htmlspecialchars((string)$row['ca_converted_30']) ?></td>
                                <td>
                                    <?php if ($role !== 'student' && $filterSubject !== '' && !in_array($row['status'], ['verified', 'locked'], true)): ?>
                                        <input type="number" name="assignment_mark[<?= $row['student_id'] ?>]" value="<?= htmlspecialchars((string)$row['assignment_mark']) ?>" min="0" max="10" step="0.01" style="width: 70px;">
                                    <?php else: ?>
                                        <?= htmlspecialchars((string)$row['assignment_mark']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string)$row['theory_total']) ?></td>
                                <?php elseif ($entryMode === 'practical'): ?>
                                <td><?= htmlspecialchars($row['subject_code'] . ' - ' . $row['subject_name']) ?></td>
                                <td>
                                    <?php if ($role !== 'student' && $filterSubject !== '' && !in_array($row['status'], ['verified', 'locked'], true)): ?>
                                        <input type="hidden" name="student_ids[]" value="<?= (int)$row['student_id'] ?>">
                                        <input type="number" name="cycle1_execution[<?= $row['student_id'] ?>]" value="<?= htmlspecialchars((string)$row['cycle1_execution']) ?>" min="0" max="10" step="0.01" style="width: 70px;">
                                    <?php else: ?>
                                        <?= htmlspecialchars((string)$row['cycle1_execution']) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($role !== 'student' && $filterSubject !== '' && !in_array($row['status'], ['verified', 'locked'], true)): ?>
                                        <input type="number" name="cycle1_test[<?= $row['student_id'] ?>]" value="<?= htmlspecialchars((string)$row['cycle1_test']) ?>" min="0" max="10" step="0.01" style="width: 70px;">
                                    <?php else: ?>
                                        <?= htmlspecialchars((string)$row['cycle1_test']) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($role !== 'student' && $filterSubject !== '' && !in_array($row['status'], ['verified', 'locked'], true)): ?>
                                        <input type="number" name="cycle2_record[<?= $row['student_id'] ?>]" value="<?= htmlspecialchars((string)$row['cycle2_record']) ?>" min="0" max="10" step="0.01" style="width: 70px;">
                                    <?php else: ?>
                                        <?= htmlspecialchars((string)$row['cycle2_record']) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($role !== 'student' && $filterSubject !== '' && !in_array($row['status'], ['verified', 'locked'], true)): ?>
                                        <input type="number" name="cycle2_test[<?= $row['student_id'] ?>]" value="<?= htmlspecialchars((string)$row['cycle2_test']) ?>" min="0" max="10" step="0.01" style="width: 70px;">
                                    <?php else: ?>
                                        <?= htmlspecialchars((string)$row['cycle2_test']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string)$row['practical_total']) ?></td>
                                <?php else: ?>
                                <td><?= htmlspecialchars((string)$row['subject_code']) ?></td>
                                <td><?= htmlspecialchars((string)$row['subject_name']) ?></td>
                                <td>
                                    <?php if ($role !== 'student' && $filterSubject !== '' && !in_array($row['status'], ['verified', 'locked'], true)): ?>
                                        <input type="hidden" name="student_ids[]" value="<?= (int)$row['student_id'] ?>">
                                        <select name="semester_grade[<?= $row['student_id'] ?>]">
                                            <option value="">Select</option>
                                            <?php foreach (['O','A+','A','B+','B','C','RA'] as $g): ?>
                                                <option value="<?= $g ?>" <?= $row['semester_grade'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <?= htmlspecialchars((string)$row['semester_grade']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string)$row['grade_point']) ?></td>
                                <td><?= (int)$row['is_arrear'] ? '<span class="badge gold">Arrear</span>' : 'No' ?></td>
                                <?php endif; ?>
                                <td><span class="badge"><?= htmlspecialchars($row['status']) ?></span></td>
                                <td><?= htmlspecialchars((string)$row['staff_comment']) ?></td>
                                <?php if ($role !== 'student' && $role !== 'tutor'): ?>
                                <td>
                                    <?php if ($row['status'] !== 'locked' || $isAdmin || $role === 'staff'): ?>
                                    <!-- Use a nested form if we're not inside the main bulk form, or handle review actions separately -->
                                    <div style="display:inline-block">
                                        <select name="decision_mark[<?= (int)$row['student_id'] ?>]" onchange="if(this.value){ document.getElementById('review_decision_<?= $row['student_id'] ?>').value = this.value; document.getElementById('review_form_<?= $row['student_id'] ?>').submit(); }">
                                            <option value="">Review...</option>
                                            <?php if ($row['status'] !== 'locked'): ?>
                                            <option value="verified">Verify</option>
                                            <option value="returned">Return</option>
                                            <?php if ($isAdmin): ?><option value="locked">Lock</option><?php endif; ?>
                                            <?php endif; ?>
                                            <?php if ($row['status'] === 'locked' && ($isAdmin || $role === 'staff')): ?><option value="unlocked">Unlock</option><?php endif; ?>
                                        </select>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($role !== 'student'): ?>
                        <?php if ($filterSubject !== ''): ?>
                        <div style="margin-top: 16px; display: flex; justify-content: flex-end;">
                            <button class="primary-btn" type="submit">Save All Marks</button>
                        </div>
                        <?php endif; ?>
                    </form>
                    
                    <!-- Hidden mini-forms for each row's individual status review -->
                    <?php foreach ($marks as $row): ?>
                        <?php if ($row['id'] > 0): ?>
                        <form id="review_form_<?= $row['student_id'] ?>" method="post" style="display:none;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                            <input type="hidden" name="action" value="review_marks">
                            <input type="hidden" name="mark_id" value="<?= (int)$row['id'] ?>">
                            <input type="hidden" name="decision" id="review_decision_<?= $row['student_id'] ?>">
                            <input type="hidden" name="staff_comment" value="">
                        </form>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </section>
    </main>
</div>
<script>
async function refreshMarksCount() {
  const badge = document.querySelector('[data-pending-count]');
  if (!badge) return;
  try {
    const response = await fetch('api/marks_updates.php', { credentials: 'same-origin' });
    const data = await response.json();
    if (data.ok) badge.textContent = data.pending + ' pending';
  } catch (error) {}
}
refreshMarksCount();
setInterval(refreshMarksCount, 15000);
</script>
</body>
</html>
