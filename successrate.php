<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';
require_once __DIR__ . '/include/nba_document_export.php';

$user = iqac_require_login(['staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
$role = iqac_effective_role($user);
$format = $_GET['format'] ?? 'html';
$academicYear = trim((string)($_GET['academic_year'] ?? nba_default_academic_year($conn)));
$department = trim((string)($_GET['department'] ?? ''));
$batch = trim((string)($_GET['batch'] ?? ''));
$studyYear = trim((string)($_GET['study_year'] ?? ''));
$semester = trim((string)($_GET['semester'] ?? ''));
$staffId = (int)($_SESSION['staff_id'] ?? 0);

$where = [];
$types = '';
$params = [];
if ($department !== '') {
    $where[] = 'sd.department = ?';
    $types .= 's';
    $params[] = $department;
}
if ($batch !== '') {
    $where[] = 'sd.batch = ?';
    $types .= 's';
    $params[] = $batch;
}
$studyYearSemesters = nba_study_year_semesters($studyYear);
if ($studyYear !== '' && $studyYearSemesters) {
    $where[] = 'COALESCE(sm.semester, sd.current_semester) IN (' . implode(',', array_fill(0, count($studyYearSemesters), '?')) . ')';
    $types .= str_repeat('i', count($studyYearSemesters));
    array_push($params, ...$studyYearSemesters);
}
if ($semester !== '') {
    $where[] = 'sm.semester = ?';
    $types .= 'i';
    $params[] = (int)$semester;
}
if ($academicYear !== '') {
    $where[] = '(sm.academic_year = ? OR sm.academic_year IS NULL)';
    $types .= 's';
    $params[] = $academicYear;
}
if ($role === 'staff') {
    $where[] = 'ssa.staff_id = ?';
    $types .= 'i';
    $params[] = $staffId;
}
if ($role === 'tutor') {
    $where[] = 'sd.tutor_staff_id = ?';
    $types .= 'i';
    $params[] = $staffId;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT COALESCE(NULLIF(sd.department, ''), 'Unassigned') AS department,
               COALESCE(NULLIF(sd.batch, ''), sd.class_year, 'Unassigned') AS batch,
               COALESCE(sm.semester, sd.current_semester, 0) AS semester,
               COUNT(DISTINCT sd.student_id) AS admission_count,
               COUNT(DISTINCT CASE WHEN sm.status IN ('verified','locked') THEN sd.student_id END) AS appeared,
               COUNT(DISTINCT CASE WHEN sm.status IN ('verified','locked') AND COALESCE(sm.is_arrear, 0) = 0 THEN sd.student_id END) AS passed,
               COUNT(DISTINCT CASE WHEN sm.status IN ('verified','locked') AND COALESCE(sm.is_arrear, 0) = 1 THEN sd.student_id END) AS failed,
               SUM(CASE WHEN COALESCE(sm.is_arrear, 0) = 1 THEN 1 ELSE 0 END) AS arrear,
               ROUND(AVG(NULLIF(sm.theory_total + sm.practical_total, 0)), 2) AS department_average
        FROM student_details sd
        LEFT JOIN student_marks sm ON sm.student_id = sd.student_id
        LEFT JOIN staff_subject_allocation ssa ON ssa.subject_code COLLATE utf8mb4_unicode_ci = sm.subject_code COLLATE utf8mb4_unicode_ci
        $whereSql
        GROUP BY COALESCE(NULLIF(sd.department, ''), 'Unassigned'), COALESCE(NULLIF(sd.batch, ''), sd.class_year, 'Unassigned'), COALESCE(sm.semester, sd.current_semester, 0)
        ORDER BY department, batch, semester";

if ($types !== '') {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result = $conn->query($sql);
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function sr_percent(int|float $part, int|float $total): string
{
    return $total > 0 ? number_format(($part / $total) * 100, 2) . '%' : '0.00%';
}

function sr_success_index_value(int|float $passed, int|float $total): string
{
    if ($total <= 0) {
        return 'X / Z = 0 / 0 = 0.00';
    }
    $si = round($passed / $total, 2);
    return 'X / Z = ' . $passed . ' / ' . $total . ' = ' . number_format($si, 2);
}

function sr_nba_meta(array $user, string $academicYear, string $department, string $batch, string $semester): array
{
    global $conn;
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

    $tutorName = 'Assigned Tutor';
    if ($batch !== '') {
        $tRes = $conn->query("
            SELECT DISTINCT st.full_name 
            FROM student_details sd
            JOIN staff_details st ON st.staff_id = sd.tutor_staff_id
            WHERE sd.batch = '" . $conn->real_escape_string($batch) . "' AND sd.tutor_staff_id IS NOT NULL AND sd.tutor_staff_id <> 0
            LIMIT 1
        ");
        if ($tRes && ($tRow = $tRes->fetch_assoc())) {
            $tutorName = $tRow['full_name'];
        }
    }

    return [
        'Department Name' => $department !== '' ? $department : 'All Departments',
        'Academic Year' => $academicYear,
        'Semester' => $semester !== '' ? 'Semester ' . $semester : 'Study year filtered',
        'Batch' => $batch !== '' ? $batch : 'All',
        'Section' => 'All',
        'Faculty Name' => 'All / Assigned Faculty',
        'Tutor Name' => $tutorName,
        'HOD Name' => $hodName,
        'Prepared By' => (string)($user['username'] ?? 'ERP User'),
        'Generated Date' => date('Y-m-d H:i:s'),
        'Report ID' => nba_doc_report_id('SUCCESS'),
    ];
}

function sr_nba_headers(): array
{
    return ['Department', 'Batch', 'Semester', 'Admission', 'Appeared', 'Passed', 'Failed / RA', 'Arrear', 'Pass %', 'Fail %', 'Success Index', 'Backlog %', 'Average'];
}

function sr_nba_rows(array $rows): array
{
    $exportRows = [];
    foreach ($rows as $row) {
        $exportRows[] = [
            (string)$row['department'],
            (string)$row['batch'],
            (string)$row['semester'],
            (string)$row['admission_count'],
            (string)$row['appeared'],
            (string)$row['passed'],
            (string)$row['failed'],
            (string)$row['arrear'],
            sr_percent((int)$row['passed'], (int)$row['appeared']),
            sr_percent((int)$row['failed'], (int)$row['appeared']),
            sr_success_index_value((int)$row['passed'], (int)$row['admission_count']),
            sr_percent((int)$row['arrear'], max(1, (int)$row['appeared'])),
            (string)($row['department_average'] ?? 0),
        ];
    }
    return $exportRows;
}

if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="success_rate_nba_report.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['PSG POLYTECHNIC COLLEGE', 'SUCCESS RATE REPORT', $academicYear]);
    fputcsv($out, ['Department','Batch','Semester','Admission Count','Appeared','Passed','Failed','Arrear','Pass %','Fail %','Success Index','Backlog %','Department Average']);
    foreach ($rows as $row) {
        fputcsv($out, [$row['department'], $row['batch'], $row['semester'], $row['admission_count'], $row['appeared'], $row['passed'], $row['failed'], $row['arrear'], sr_percent((int)$row['passed'], (int)$row['appeared']), sr_percent((int)$row['failed'], (int)$row['appeared']), sr_percent((int)$row['passed'], (int)$row['admission_count']), sr_percent((int)$row['arrear'], max(1, (int)$row['appeared'])), $row['department_average']]);
    }
    fclose($out);
    exit;
}
if ($format === 'excel') {
    nba_doc_export_excel('success_rate_nba_report.xls', 'SUCCESS RATE REPORT', sr_nba_meta($user, $academicYear, $department, $batch, $semester), sr_nba_headers(), sr_nba_rows($rows));
}
if ($format === 'pdf') {
    nba_doc_export_pdf('success_rate_nba_report.pdf', 'SUCCESS RATE REPORT', sr_nba_meta($user, $academicYear, $department, $batch, $semester), sr_nba_headers(), sr_nba_rows($rows));
}
if ($format === 'docx') {
    nba_doc_export_docx('success_rate_nba_report.docx', 'SUCCESS RATE REPORT', sr_nba_meta($user, $academicYear, $department, $batch, $semester), sr_nba_headers(), sr_nba_rows($rows));
}

// Compute Summary Metrics
$totAdmitted = array_sum(array_column($rows, 'admission_count'));
$totAppeared = array_sum(array_column($rows, 'appeared'));
$totPassed = array_sum(array_column($rows, 'passed'));
$totFailed = array_sum(array_column($rows, 'failed'));
$avgPass = $totAppeared > 0 ? number_format(($totPassed / $totAppeared) * 100, 2) . '%' : '0.00%';

$averages = array_filter(array_column($rows, 'department_average'), fn($v) => (float)$v > 0);
$overallAvg = !empty($averages) ? number_format(array_sum($averages) / count($averages), 2) : '0.00';
$overallHighest = !empty($averages) ? number_format(max($averages), 2) : '0.00';
$overallLowest = !empty($averages) ? number_format(min($averages), 2) : '0.00';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Success Rate NBA Report</title>
<link rel="stylesheet" href="assets/erp.css">
<link rel="stylesheet" href="assets/report.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="report-body erp-report-screen">
<div class="erp-layout">
    <?php iqac_render_sidebar($user, 'successrate.php'); ?>
    <main class="erp-main">
        <header class="erp-topbar no-print">
            <div class="topbar-left">
                <h1>Success Rate NBA Report</h1>
                <span>NBA/IQAC print format with PSG PTC ERP navigation</span>
            </div>
            <div class="toolbar-actions">
                <a class="secondary-btn" href="<?= htmlspecialchars(iqac_role_home($role)) ?>">Dashboard</a>
            </div>
        </header>

        <section class="erp-content">
            <!-- Breadcrumb -->
            <div class="report-breadcrumb no-print" style="margin-bottom: 16px;">
                <a href="<?= htmlspecialchars(iqac_role_home($role)) ?>">Dashboard</a>
                <span class="sep">/</span>
                <span class="current">Success Rate Report</span>
            </div>

            <div class="report-shell">
                <!-- Toolbar -->
                <div class="report-toolbar no-print">
                    <div class="toolbar-left" style="display:flex; gap:8px; align-items:center;">
                        <a href="<?= htmlspecialchars(iqac_role_home($role)) ?>" class="btn-toolbar btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
                        <button type="button" class="btn-toolbar toggle-filter-btn" onclick="window.ReportUI.toggleFilter()"><i class="fa-solid fa-filter"></i> Filter</button>
                        <button type="button" class="btn-toolbar btn-refresh" onclick="window.location.reload()"><i class="fa-solid fa-rotate-right"></i> Refresh</button>
                    </div>
                    <div class="toolbar-right" style="display:flex; gap:8px; align-items:center;">
                        <a class="btn-toolbar btn-export" href="<?= htmlspecialchars('successrate.php?' . http_build_query(array_merge($_GET, ['format' => 'excel']))) ?>"><i class="fa-solid fa-file-excel"></i> Excel</a>
                        <a class="btn-toolbar btn-export" href="<?= htmlspecialchars('successrate.php?' . http_build_query(array_merge($_GET, ['format' => 'csv']))) ?>"><i class="fa-solid fa-file-csv"></i> CSV</a>
                        <a class="btn-toolbar btn-export" href="<?= htmlspecialchars('successrate.php?' . http_build_query(array_merge($_GET, ['format' => 'docx']))) ?>"><i class="fa-solid fa-file-word"></i> Word</a>
                        <a class="btn-toolbar btn-export" href="<?= htmlspecialchars('successrate.php?' . http_build_query(array_merge($_GET, ['format' => 'pdf']))) ?>"><i class="fa-solid fa-file-pdf"></i> PDF</a>
                        <button type="button" class="btn-toolbar btn-print" onclick="window.ReportUI.reportPrint()"><i class="fa-solid fa-print"></i> Print</button>
                        <button type="button" class="btn-toolbar btn-fullscreen" onclick="window.ReportUI.toggleFullscreen()"><i class="fa-solid fa-expand"></i> Fullscreen</button>
                    </div>
                </div>

                <!-- Filter Panel -->
                <div class="report-filter-panel no-print">
                    <form action="successrate.php" method="GET" class="filter-form">
                        <div class="filter-grid">
                            <div class="filter-group">
                                <label for="filter_academic_year">Academic Year</label>
                                <input type="text" name="academic_year" id="filter_academic_year" value="<?= htmlspecialchars($academicYear) ?>" placeholder="e.g. 2023-2024" class="filter-input">
                            </div>
                            <div class="filter-group">
                                <label for="filter_department">Department</label>
                                <input type="text" name="department" id="filter_department" value="<?= htmlspecialchars($department) ?>" placeholder="e.g. Information Technology" class="filter-input">
                            </div>
                            <div class="filter-group">
                                <label for="filter_batch">Batch</label>
                                <input type="text" name="batch" id="filter_batch" value="<?= htmlspecialchars($batch) ?>" placeholder="e.g. 40DI" class="filter-input">
                            </div>
                            <div class="filter-group">
                                <label for="filter_study_year">Study Year</label>
                                <select name="study_year" id="filter_study_year" class="filter-input">
                                    <option value="">All Study Years</option>
                                    <option value="1" <?= $studyYear === '1' ? 'selected' : '' ?>>1st Year</option>
                                    <option value="2" <?= $studyYear === '2' ? 'selected' : '' ?>>2nd Year</option>
                                    <option value="3" <?= $studyYear === '3' ? 'selected' : '' ?>>3rd Year</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="filter_semester">Semester</label>
                                <select name="semester" id="filter_semester" class="filter-input">
                                    <option value="">All Semesters</option>
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                        <option value="<?= $i ?>" <?= $semester === (string)$i ? 'selected' : '' ?>>Semester <?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn-primary"><i class="fa-solid fa-check"></i> Apply Filters</button>
                            <a href="successrate.php" class="btn-secondary"><i class="fa-solid fa-xmark"></i> Clear</a>
                        </div>
                    </form>
                </div>

                <!-- Summary Stat Cards -->
                <div class="report-cards-grid no-print" style="margin-bottom: 20px;">
                    <div class="report-card tone-gold">
                        <div class="card-icon"><i class="fa-solid fa-layer-group"></i></div>
                        <div class="card-content">
                            <div class="card-value"><?= count($rows) ?></div>
                            <div class="card-label">Total Groups</div>
                        </div>
                    </div>
                    <div class="report-card tone-success">
                        <div class="card-icon"><i class="fa-solid fa-chart-pie"></i></div>
                        <div class="card-content">
                            <div class="card-value"><?= $avgPass ?></div>
                            <div class="card-label">Overall Pass %</div>
                        </div>
                    </div>
                    <div class="report-card">
                        <div class="card-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="card-content">
                            <div class="card-value"><?= $totAdmitted ?></div>
                            <div class="card-label">Total Students</div>
                        </div>
                    </div>
                    <div class="report-card tone-success">
                        <div class="card-icon"><i class="fa-solid fa-user-check"></i></div>
                        <div class="card-content">
                            <div class="card-value"><?= $totPassed ?></div>
                            <div class="card-label">Passed</div>
                        </div>
                    </div>
                    <div class="report-card tone-danger">
                        <div class="card-icon"><i class="fa-solid fa-user-xmark"></i></div>
                        <div class="card-content">
                            <div class="card-value"><?= $totFailed ?></div>
                            <div class="card-label">Failed</div>
                        </div>
                    </div>
                    <div class="report-card">
                        <div class="card-icon"><i class="fa-solid fa-calculator"></i></div>
                        <div class="card-content">
                            <div class="card-value"><?= $overallAvg ?></div>
                            <div class="card-label">Average</div>
                        </div>
                    </div>
                    <div class="report-card tone-success">
                        <div class="card-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
                        <div class="card-content">
                            <div class="card-value"><?= $overallHighest ?></div>
                            <div class="card-label">Highest</div>
                        </div>
                    </div>
                    <div class="report-card tone-gold">
                        <div class="card-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
                        <div class="card-content">
                            <div class="card-value"><?= $overallLowest ?></div>
                            <div class="card-label">Lowest</div>
                        </div>
                    </div>
                </div>

                <!-- Official Report Header -->
                <div class="report-header">
                    <div class="report-header-inner">
                        <h1 class="college-name">PSG POLYTECHNIC COLLEGE</h1>
                        <h2 class="department-name">DEPARTMENT OF <?= htmlspecialchars(strtoupper($department ?: 'ALL')) ?></h2>
                        <h3 class="report-title">NBA ACCREDITATION REPORT - SUCCESS RATE</h3>
                        <div class="header-meta">
                            <div><strong>Date:</strong> <?= date('Y-m-d') ?></div>
                            <div><strong>By:</strong> <?= htmlspecialchars($user['username'] ?? 'ERP User') ?></div>
                        </div>
                    </div>
                </div>

                <!-- Metadata Bar -->
                <div class="report-meta-bar">
                    <div class="meta-badge">
                        <span class="badge-label">Academic Year:</span>
                        <span class="badge-value"><?= htmlspecialchars($academicYear ?: 'Current') ?></span>
                    </div>
                    <div class="meta-badge">
                        <span class="badge-label">Semester:</span>
                        <span class="badge-value"><?= htmlspecialchars($semester ? 'Semester ' . $semester : 'All') ?></span>
                    </div>
                    <div class="meta-badge">
                        <span class="badge-label">Batch:</span>
                        <span class="badge-value"><?= htmlspecialchars($batch ?: 'All') ?></span>
                    </div>
                    <div class="meta-badge">
                        <span class="badge-label">Mode:</span>
                        <span class="badge-value">Historical Batch Success Rate</span>
                    </div>
                </div>

                <!-- Search Box -->
                <div class="report-search-container no-print">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="report-search" class="report-search-input" placeholder="Search department, batch, semester..." data-table-target="success-rate-table">
                </div>

                <!-- Data Table -->
                <div class="report-table-container">
                    <table class="report-table border-heavy" id="success-rate-table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Batch</th>
                                <th>Semester</th>
                                <th>Admission Count</th>
                                <th>Appeared</th>
                                <th>Passed</th>
                                <th>Failed</th>
                                <th>Arrear</th>
                                <th>Pass %</th>
                                <th>Fail %</th>
                                <th>Success Index</th>
                                <th>Backlog %</th>
                                <th>Dept Average</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr class="empty-row">
                                    <td colspan="13" class="text-center empty-cell-text"><i>No records available.</i></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($row['department']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['batch']) ?></td>
                                        <td><?= htmlspecialchars((string)$row['semester']) ?></td>
                                        <td><?= (int)$row['admission_count'] ?></td>
                                        <td><?= (int)$row['appeared'] ?></td>
                                        <td><?= (int)$row['passed'] ?></td>
                                        <td><?= (int)$row['failed'] ?></td>
                                        <td><?= (int)$row['arrear'] ?></td>
                                        <td><span class="badge badge-success"><?= sr_percent((int)$row['passed'], (int)$row['appeared']) ?></span></td>
                                        <td><?= sr_percent((int)$row['failed'], (int)$row['appeared']) ?></td>
                                        <td><?= sr_success_index_value((int)$row['passed'], (int)$row['admission_count']) ?></td>
                                        <td><?= sr_percent((int)$row['arrear'], max(1, (int)$row['appeared'])) ?></td>
                                        <td><?= htmlspecialchars((string)($row['department_average'] ?? 0)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="report-pagination no-print" data-table-target="success-rate-table" data-rows="15"></div>

                <!-- Signatures -->
                <div class="report-signatures">
                    <div class="sig-slot">Prepared By</div>
                    <div class="sig-slot">Tutor</div>
                    <div class="sig-slot">Faculty In-Charge</div>
                    <div class="sig-slot">HOD</div>
                    <div class="sig-slot">Principal</div>
                </div>

                <!-- Footer -->
                <div class="report-print-footer">
                    <span>College Seal Area</span>
                    <span>Generated Timestamp: <?= date('Y-m-d H:i:s') ?></span>
                </div>
            </div> <!-- .report-shell -->
        </section> <!-- .erp-content -->
    </main> <!-- .erp-main -->
</div> <!-- .erp-layout -->

<script src="assets/report.js"></script>
</body>
</html>
