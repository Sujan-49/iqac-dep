<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/report_layout.php';
$user = iqac_require_login(['staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);

// Fetch distinct dropdown options for filters
$departments = array_column($conn->query("SELECT DISTINCT department FROM student_details WHERE department IS NOT NULL AND department != '' ORDER BY department")->fetch_all(MYSQLI_ASSOC), 'department');
$batches = array_column($conn->query("SELECT DISTINCT batch FROM student_details WHERE batch IS NOT NULL AND batch != '' ORDER BY batch DESC")->fetch_all(MYSQLI_ASSOC), 'batch');
$semesters = array_column($conn->query("SELECT DISTINCT semester FROM student_marks WHERE semester IS NOT NULL ORDER BY semester")->fetch_all(MYSQLI_ASSOC), 'semester');

$semester = (int)($_GET['semester'] ?? 0);
$where = $semester ? 'WHERE sm.semester = ' . $semester : '';

$rows = $conn->query("SELECT sm.subject_code, COALESCE(sub.subject_name, sm.subject_code) AS subject_name, COALESCE(st.full_name, 'Unassigned') AS faculty_name, COUNT(*) AS appeared, SUM(CASE WHEN COALESCE(sm.is_arrear,0)=0 AND sm.status IN ('verified','locked') THEN 1 ELSE 0 END) AS passed, SUM(CASE WHEN COALESCE(sm.is_arrear,0)=1 THEN 1 ELSE 0 END) AS failed, MAX(sm.theory_total + sm.practical_total) AS highest_mark, MIN(NULLIF(sm.theory_total + sm.practical_total,0)) AS lowest_mark, ROUND(AVG(NULLIF(sm.theory_total + sm.practical_total,0)),2) AS average_mark, SUM(CASE WHEN COALESCE(sm.is_arrear,0)=1 THEN 1 ELSE 0 END) AS arrear_count FROM student_marks sm LEFT JOIN subjects sub ON sub.subject_code COLLATE utf8mb4_unicode_ci = sm.subject_code COLLATE utf8mb4_unicode_ci LEFT JOIN staff_subject_allocation ssa ON ssa.subject_code COLLATE utf8mb4_unicode_ci = sm.subject_code COLLATE utf8mb4_unicode_ci LEFT JOIN staff_details st ON st.staff_id = ssa.staff_id $where GROUP BY sm.subject_code, subject_name, faculty_name ORDER BY sm.subject_code")->fetch_all(MYSQLI_ASSOC);

if (!function_exists('pctv')) {
    function pctv($a, $b) { return $b > 0 ? number_format(($a / $b) * 100, 2) . '%' : '0.00%'; }
}

// Start ERP Layout
erp_report_layout_start(
    'Subject Performance Report',
    $user,
    'view_sem.php',
    [
        ['label' => 'Dashboard', 'url' => iqac_role_home($user['role'])],
        ['label' => 'Subject Performance NBA Report']
    ]
);
?>

<div class="report-shell">
  <?php
  // Render Toolbar
  erp_report_toolbar([
      'page_name' => 'subject_performance_nba_report',
      'back_url' => iqac_role_home($user['role']),
      'exports' => ['excel', 'csv', 'word', 'print'],
      'target_table' => 'subject-performance-table'
  ]);

  // Build Filters
  $deptOptions = array_merge(['All' => 'All Departments'], array_combine($departments, $departments));
  $batchOptions = array_merge(['All' => 'All Batches'], array_combine($batches, $batches));
  $semOptions = ['All' => 'All Semesters'];
  foreach ($semesters as $s) { $semOptions[(string)$s] = 'Semester ' . $s; }

  erp_report_filters([
      ['name' => 'academic_year', 'label' => 'Academic Year', 'type' => 'text', 'placeholder' => 'e.g. 2024-2025', 'value' => $_GET['academic_year'] ?? ''],
      ['name' => 'department', 'label' => 'Department', 'type' => 'select', 'options' => $deptOptions, 'value' => $_GET['department'] ?? 'All'],
      ['name' => 'batch', 'label' => 'Batch', 'type' => 'select', 'options' => $batchOptions, 'value' => $_GET['batch'] ?? 'All'],
      ['name' => 'semester', 'label' => 'Semester', 'type' => 'select', 'options' => $semOptions, 'value' => (string)($semester ?: 'All')],
  ], 'view_sem.php', 'view_sem.php');

  // Summary Stat Cards
  $totalAppeared = array_sum(array_column($rows, 'appeared'));
  $totalPassed = array_sum(array_column($rows, 'passed'));
  $passPct = $totalAppeared > 0 ? number_format(($totalPassed / $totalAppeared) * 100, 2) . '%' : '0.00%';

  erp_report_cards([
      ['label' => 'Total Subjects', 'value' => count($rows), 'icon' => 'fa-book-open', 'tone' => 'gold'],
      ['label' => 'Overall Pass %', 'value' => $passPct, 'icon' => 'fa-chart-line', 'tone' => 'success'],
      ['label' => 'Semester', 'value' => $semester ? 'Semester ' . $semester : 'All', 'icon' => 'fa-graduation-cap', 'tone' => ''],
      ['label' => 'Department', 'value' => $_GET['department'] ?? 'All', 'icon' => 'fa-building', 'tone' => ''],
      ['label' => 'Generated Date', 'value' => date('Y-m-d'), 'icon' => 'fa-calendar-check', 'tone' => ''],
  ]);

  // Official Report Header
  erp_report_header(
      'NBA ACCREDITATION REPORT - SUBJECT PERFORMANCE',
      $_GET['department'] ?? 'ALL',
      [
          'Date' => date('Y-m-d'),
          'By' => $user['username'] ?? 'ERP User',
          'Page No' => '1'
      ]
  );

  // Metadata Bar
  erp_report_meta_bar([
      'Academic Year' => $_GET['academic_year'] ?? 'Current',
      'Semester' => $semester ? 'Semester ' . $semester : 'All',
      'Batch' => $_GET['batch'] ?? 'All',
      'Scope' => 'Subject Performance Analysis'
  ]);

  // Search Box
  erp_report_search('Type subject code, name, faculty...', 'subject-performance-table');
  ?>

  <!-- Data Table -->
  <div class="report-table-container">
    <table class="report-table" id="subject-performance-table">
      <thead>
        <tr>
          <th>Subject Code</th>
          <th>Subject Name</th>
          <th>Faculty Assigned</th>
          <th>Appeared</th>
          <th>Passed</th>
          <th>Failed</th>
          <th>Highest</th>
          <th>Lowest</th>
          <th>Average</th>
          <th>Pass %</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr class="empty-row">
            <td colspan="10" class="text-center empty-cell-text"><i>No records available.</i></td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><strong><?= htmlspecialchars($r['subject_code']) ?></strong></td>
              <td><?= htmlspecialchars($r['subject_name']) ?></td>
              <td><?= htmlspecialchars($r['faculty_name']) ?></td>
              <td><?= (int)$r['appeared'] ?></td>
              <td><?= (int)$r['passed'] ?></td>
              <td><?= (int)$r['failed'] ?></td>
              <td><?= htmlspecialchars((string)($r['highest_mark'] ?? 0)) ?></td>
              <td><?= htmlspecialchars((string)($r['lowest_mark'] ?? 0)) ?></td>
              <td><?= htmlspecialchars((string)($r['average_mark'] ?? 0)) ?></td>
              <td><span class="badge badge-success"><?= pctv((int)$r['passed'], (int)$r['appeared']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php
  // Pagination
  erp_report_pagination('subject-performance-table', 15);

  // Signature Block
  erp_report_signatures(['Prepared By', 'Tutor', 'Faculty In-Charge', 'HOD', 'Principal']);

  // Print Footer
  erp_report_footer();
  ?>
</div>

<?php erp_report_layout_end(); ?>
