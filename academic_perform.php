<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';
require_once __DIR__ . '/include/nba_document_export.php';
require_once __DIR__ . '/include/report_layout.php';

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
$studyYearSemesters = nba_study_year_semesters($studyYear);
if ($batch !== '') {
    $where[] = 'COALESCE(NULLIF(sd.batch, ""), sd.class_year, "") = ?';
    $types .= 's';
    $params[] = $batch;
}
if ($studyYear !== '' && $studyYearSemesters) {
    $where[] = 'sm.semester IN (' . implode(',', array_fill(0, count($studyYearSemesters), '?')) . ')';
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

$sql = "SELECT CASE
        WHEN sm.semester IN (1, 2) THEN 1
        WHEN sm.semester IN (3, 4) THEN 2
        WHEN sm.semester IN (5, 6) THEN 3
        ELSE 0
    END AS study_year,
           COUNT(DISTINCT CASE WHEN sm.status IN ('verified','locked') THEN sd.student_id END) AS appeared,
           COUNT(DISTINCT CASE WHEN sm.status IN ('verified','locked') AND COALESCE(sm.is_arrear, 0) = 0 THEN sd.student_id END) AS passed,
           COUNT(DISTINCT CASE WHEN sm.status IN ('verified','locked') AND COALESCE(sm.is_arrear, 0) = 1 THEN sd.student_id END) AS failed,
           ROUND(AVG(NULLIF(sm.theory_total + sm.practical_total, 0)), 2) AS average_percent,
           MAX(sm.theory_total + sm.practical_total) AS highest_percent,
           MIN(NULLIF(sm.theory_total + sm.practical_total, 0)) AS lowest_percent
    FROM student_details sd
    LEFT JOIN student_marks sm ON sm.student_id = sd.student_id
    LEFT JOIN staff_subject_allocation ssa ON ssa.subject_code COLLATE utf8mb4_unicode_ci = sm.subject_code COLLATE utf8mb4_unicode_ci
    $whereSql
GROUP BY study_year
HAVING study_year IN (1, 2, 3)
ORDER BY study_year";

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

function ap_percent(int|float $part, int|float $total): string
{
    return $total > 0 ? number_format(($part / $total) * 100, 2) . '%' : '0.00%';
}

function ap_nba_meta(array $user, string $academicYear, string $department): array
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
    $batch = trim((string)($_GET['batch'] ?? ''));
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
        'Semester' => 'Semester 1 to Semester 6',
        'Batch' => $batch !== '' ? $batch : 'Historical batches',
        'Section' => 'All',
        'Faculty Name' => 'All / Assigned Faculty',
        'Tutor Name' => $tutorName,
        'HOD Name' => $hodName,
        'Prepared By' => (string)($user['username'] ?? 'ERP User'),
        'Generated Date' => date('Y-m-d H:i:s'),
        'Report ID' => nba_doc_report_id('ACADEMIC'),
    ];
}

function ap_nba_headers(): array
{
    return ['Study Year', 'Appeared', 'Passed', 'Failed / RA', 'Pass %', 'Fail %', 'Average %', 'Highest %', 'Lowest %'];
}

function ap_nba_rows(array $rows): array
{
    $exportRows = [];
    foreach ($rows as $row) {
        $exportRows[] = [
            nba_study_year_label($row['study_year'] ?? 0),
            (string)$row['appeared'],
            (string)$row['passed'],
            (string)$row['failed'],
            ap_percent((int)$row['passed'], (int)$row['appeared']),
            ap_percent((int)$row['failed'], (int)$row['appeared']),
            (string)($row['average_percent'] ?? 0),
            (string)($row['highest_percent'] ?? 0),
            (string)($row['lowest_percent'] ?? 0),
        ];
    }
    return $exportRows;
}

if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="academic_performance_nba_report.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['PSG POLYTECHNIC COLLEGE', 'ACADEMIC PERFORMANCE REPORT', $academicYear]);
    fputcsv($out, ['Study Year','Students Appeared','Students Passed','Students Failed','Pass %','Fail %','Average %','Highest %','Lowest %']);
    foreach ($rows as $row) {
        fputcsv($out, [nba_study_year_label($row['study_year'] ?? 0), $row['appeared'], $row['passed'], $row['failed'], ap_percent((int)$row['passed'], (int)$row['appeared']), ap_percent((int)$row['failed'], (int)$row['appeared']), $row['average_percent'], $row['highest_percent'], $row['lowest_percent']]);
    }
    fclose($out);
    exit;
}
if ($format === 'excel') {
    nba_doc_export_excel('academic_performance_nba_report.xls', 'ACADEMIC PERFORMANCE REPORT', ap_nba_meta($user, $academicYear, $department), ap_nba_headers(), ap_nba_rows($rows));
}
if ($format === 'pdf') {
    nba_doc_export_pdf('academic_performance_nba_report.pdf', 'ACADEMIC PERFORMANCE REPORT', ap_nba_meta($user, $academicYear, $department), ap_nba_headers(), ap_nba_rows($rows));
}
if ($format === 'docx') {
    nba_doc_export_docx('academic_performance_nba_report.docx', 'ACADEMIC PERFORMANCE REPORT', ap_nba_meta($user, $academicYear, $department), ap_nba_headers(), ap_nba_rows($rows));
}

// Start ERP Layout
erp_report_layout_start(
    'Academic Performance Report',
    $user,
    'academic_perform.php',
    [
        ['label' => 'Dashboard', 'url' => iqac_role_home($user['role'])],
        ['label' => 'Academic Performance NBA Report']
    ]
);
?>

<div class="report-shell">
  <?php
  // Render Toolbar
  erp_report_toolbar([
      'page_name' => 'academic_performance_nba_report',
      'back_url' => iqac_role_home($user['role']),
      'exports' => ['excel', 'csv', 'word', 'pdf', 'print'],
      'target_table' => 'academic-performance-table'
  ]);

  // Filters
  erp_report_filters([
      ['name' => 'academic_year', 'label' => 'Academic Year', 'type' => 'text', 'placeholder' => 'e.g. 2023-2024', 'value' => $academicYear],
      ['name' => 'department', 'label' => 'Department', 'type' => 'text', 'placeholder' => 'e.g. Information Technology', 'value' => $department],
      ['name' => 'batch', 'label' => 'Batch', 'type' => 'text', 'placeholder' => 'e.g. 40DI', 'value' => $batch],
      ['name' => 'study_year', 'label' => 'Study Year', 'type' => 'select', 'options' => ['' => 'All Study Years', '1' => '1st Year', '2' => '2nd Year', '3' => '3rd Year'], 'value' => $studyYear],
      ['name' => 'semester', 'label' => 'Semester', 'type' => 'select', 'options' => ['' => 'All Semesters', '1' => 'Semester 1', '2' => 'Semester 2', '3' => 'Semester 3', '4' => 'Semester 4', '5' => 'Semester 5', '6' => 'Semester 6'], 'value' => $semester],
  ], 'academic_perform.php', 'academic_perform.php');

  // Summary Stat Cards
  $totAppeared = array_sum(array_column($rows, 'appeared'));
  $totPassed = array_sum(array_column($rows, 'passed'));
  $avgPassRate = $totAppeared > 0 ? number_format(($totPassed / $totAppeared) * 100, 2) . '%' : '0.00%';

  erp_report_cards([
      ['label' => 'Study Years Analyzed', 'value' => count($rows), 'icon' => 'fa-graduation-cap', 'tone' => 'gold'],
      ['label' => 'Overall Pass %', 'value' => $avgPassRate, 'icon' => 'fa-chart-line', 'tone' => 'success'],
      ['label' => 'Department', 'value' => $department ?: 'All', 'icon' => 'fa-building', 'tone' => ''],
      ['label' => 'Academic Year', 'value' => $academicYear ?: 'Current', 'icon' => 'fa-calendar', 'tone' => ''],
      ['label' => 'Generated Date', 'value' => date('Y-m-d'), 'icon' => 'fa-calendar-check', 'tone' => ''],
  ]);

  // Official Report Header
  erp_report_header(
      'NBA ACCREDITATION REPORT - ACADEMIC PERFORMANCE',
      $department ?: 'ALL',
      [
          'Date' => date('Y-m-d'),
          'By' => $user['username'] ?? 'ERP User',
          'Page No' => '1'
      ]
  );

  // Metadata Bar
  erp_report_meta_bar([
      'Academic Year' => $academicYear ?: 'Current',
      'Semester' => $semester ? 'Semester ' . $semester : 'All',
      'Batch' => $batch ?: 'All',
      'Study Year' => $studyYear !== '' ? $studyYear . ' Year' : 'All'
  ]);

  // Search Box
  erp_report_search('Search study year, metrics...', 'academic-performance-table');
  ?>

  <!-- Data Table -->
  <div class="report-table-container">
    <table class="report-table border-heavy" id="academic-performance-table">
      <thead>
        <tr>
          <th>Year Wise</th>
          <th>Students Appeared</th>
          <th>Students Passed</th>
          <th>Students Failed</th>
          <th>Pass %</th>
          <th>Fail %</th>
          <th>Average %</th>
          <th>Highest %</th>
          <th>Lowest %</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr class="empty-row">
            <td colspan="9" class="text-center empty-cell-text"><i>No records available.</i></td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><strong><?= htmlspecialchars(nba_study_year_label($row['study_year'] ?? 0)) ?></strong></td>
              <td><?= (int)$row['appeared'] ?></td>
              <td><?= (int)$row['passed'] ?></td>
              <td><?= (int)$row['failed'] ?></td>
              <td><span class="badge badge-success"><?= ap_percent((int)$row['passed'], (int)$row['appeared']) ?></span></td>
              <td><?= ap_percent((int)$row['failed'], (int)$row['appeared']) ?></td>
              <td><?= htmlspecialchars((string)($row['average_percent'] ?? 0)) ?></td>
              <td><?= htmlspecialchars((string)($row['highest_percent'] ?? 0)) ?></td>
              <td><?= htmlspecialchars((string)($row['lowest_percent'] ?? 0)) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php
  // Pagination
  erp_report_pagination('academic-performance-table', 15);

  // Signature Block
  erp_report_signatures(['Prepared By', 'Tutor', 'Faculty In-Charge', 'HOD', 'Principal']);

  // Print Footer
  erp_report_footer();
  ?>
</div>

<?php erp_report_layout_end(); ?>
