<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/report_layout.php';
$user = iqac_require_login(['staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);

// Fetch distinct dropdown options for filters
$departments = array_column($conn->query("SELECT DISTINCT department FROM student_details WHERE department IS NOT NULL AND department != '' ORDER BY department")->fetch_all(MYSQLI_ASSOC), 'department');
$batches = array_column($conn->query("SELECT DISTINCT batch FROM student_details WHERE batch IS NOT NULL AND batch != '' ORDER BY batch DESC")->fetch_all(MYSQLI_ASSOC), 'batch');
$semesters = array_column($conn->query("SELECT DISTINCT current_semester FROM student_details WHERE current_semester IS NOT NULL ORDER BY current_semester")->fetch_all(MYSQLI_ASSOC), 'current_semester');
$sections = array_column($conn->query("SELECT DISTINCT section FROM student_details WHERE section IS NOT NULL AND section != '' ORDER BY section")->fetch_all(MYSQLI_ASSOC), 'section');

// Process SQL Filters
$where = [];
if (!empty($_GET['department']) && $_GET['department'] !== 'All') {
    $where[] = "department = '" . $conn->real_escape_string($_GET['department']) . "'";
}
if (!empty($_GET['batch']) && $_GET['batch'] !== 'All') {
    $where[] = "batch = '" . $conn->real_escape_string($_GET['batch']) . "'";
}
if (!empty($_GET['semester']) && $_GET['semester'] !== 'All') {
    $where[] = "current_semester = " . (int)$_GET['semester'];
}
if (!empty($_GET['section']) && $_GET['section'] !== 'All') {
    $where[] = "section = '" . $conn->real_escape_string($_GET['section']) . "'";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$rows = $conn->query("SELECT full_name, roll_number, department, current_semester, batch, section FROM student_details $whereClause ORDER BY department, batch, roll_number")->fetch_all(MYSQLI_ASSOC);

// Start ERP Layout
erp_report_layout_start(
    'Student Details Report',
    $user,
    'view_std.php',
    [
        ['label' => 'Dashboard', 'url' => iqac_role_home($user['role'])],
        ['label' => 'Student NBA Report']
    ]
);
?>

<div class="report-shell">
  <?php
  // Render Toolbar
  erp_report_toolbar([
      'page_name' => 'student_nba_report',
      'back_url' => iqac_role_home($user['role']),
      'exports' => ['excel', 'csv', 'word', 'print'],
      'target_table' => 'student-nba-table'
  ]);

  // Build Filters
  $deptOptions = array_merge(['All' => 'All Departments'], array_combine($departments, $departments));
  $batchOptions = array_merge(['All' => 'All Batches'], array_combine($batches, $batches));
  $semOptions = ['All' => 'All Semesters'];
  foreach ($semesters as $s) { $semOptions[(string)$s] = 'Semester ' . $s; }
  $secOptions = array_merge(['All' => 'All Sections'], array_combine($sections, $sections));

  erp_report_filters([
      ['name' => 'academic_year', 'label' => 'Academic Year', 'type' => 'text', 'placeholder' => 'e.g. 2024-2025', 'value' => $_GET['academic_year'] ?? ''],
      ['name' => 'department', 'label' => 'Department', 'type' => 'select', 'options' => $deptOptions, 'value' => $_GET['department'] ?? 'All'],
      ['name' => 'batch', 'label' => 'Batch', 'type' => 'select', 'options' => $batchOptions, 'value' => $_GET['batch'] ?? 'All'],
      ['name' => 'semester', 'label' => 'Semester', 'type' => 'select', 'options' => $semOptions, 'value' => $_GET['semester'] ?? 'All'],
      ['name' => 'section', 'label' => 'Section', 'type' => 'select', 'options' => $secOptions, 'value' => $_GET['section'] ?? 'All'],
  ], 'view_std.php', 'view_std.php');

  // Summary Stat Cards
  erp_report_cards([
      ['label' => 'Total Students', 'value' => count($rows), 'icon' => 'fa-users', 'tone' => 'gold'],
      ['label' => 'Department', 'value' => $_GET['department'] ?? 'All', 'icon' => 'fa-building', 'tone' => ''],
      ['label' => 'Semester', 'value' => $_GET['semester'] ?? 'All', 'icon' => 'fa-graduation-cap', 'tone' => ''],
      ['label' => 'Batch', 'value' => $_GET['batch'] ?? 'All', 'icon' => 'fa-layer-group', 'tone' => ''],
      ['label' => 'Generated Date', 'value' => date('Y-m-d'), 'icon' => 'fa-calendar-check', 'tone' => 'success'],
  ]);

  // Official Report Header
  erp_report_header(
      'NBA ACCREDITATION REPORT - STUDENT DETAILS',
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
      'Semester' => $_GET['semester'] ?? 'All',
      'Batch' => $_GET['batch'] ?? 'All',
      'Section' => $_GET['section'] ?? 'All'
  ]);

  // Search Box
  erp_report_search('Type student name, roll number, department...', 'student-nba-table');
  ?>

  <!-- Data Table -->
  <div class="report-table-container">
    <table class="report-table" id="student-nba-table">
      <thead>
        <tr>
          <th>Student Name</th>
          <th>Roll Number</th>
          <th>Department</th>
          <th>Semester</th>
          <th>Batch</th>
          <th>Section</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr class="empty-row">
            <td colspan="7" class="text-center empty-cell-text"><i>No records available.</i></td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['full_name']) ?></td>
              <td><?= htmlspecialchars($r['roll_number']) ?></td>
              <td><?= htmlspecialchars($r['department'] ?? '') ?></td>
              <td><?= htmlspecialchars((string)$r['current_semester']) ?></td>
              <td><?= htmlspecialchars($r['batch'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['section'] ?? '') ?></td>
              <td><span class="badge badge-active">Active</span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php
  // Pagination
  erp_report_pagination('student-nba-table', 15);

  // Signature Block
  erp_report_signatures(['Prepared By', 'Tutor', 'Faculty In-Charge', 'HOD', 'Principal']);

  // Print Footer
  erp_report_footer();
  ?>
</div>

<?php erp_report_layout_end(); ?>
