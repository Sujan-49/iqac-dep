<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/report_layout.php';
$currentUser = iqac_require_login(['student', 'staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);

$files = [];
if (file_exists(__DIR__ . "/data.json")) {
    $files = json_decode(file_get_contents(__DIR__ . "/data.json"), true) ?: [];
}

if (empty($files) && iqac_table_exists($conn, 'mou_files')) {
    $res = $conn->query("SELECT department, year, company, status, filename, filepath, upload_date FROM mou_files ORDER BY upload_date DESC LIMIT 100");
    if ($res) {
        $files = $res->fetch_all(MYSQLI_ASSOC);
    }
}

// Start ERP Layout
erp_report_layout_start(
    'Uploaded MOU Documents',
    $currentUser,
    'view.php',
    [
        ['label' => 'Dashboard', 'url' => iqac_role_home($currentUser['role'])],
        ['label' => 'MOU Document Viewer']
    ]
);
?>

<div class="report-shell">
  <?php
  // Render Toolbar
  erp_report_toolbar([
      'page_name' => 'mou_documents_report',
      'back_url' => iqac_role_home($currentUser['role']),
      'exports' => ['excel', 'csv', 'word', 'print'],
      'target_table' => 'mou-documents-table'
  ]);

  // Summary Stat Cards
  erp_report_cards([
      ['label' => 'Total MOU Files', 'value' => count($files), 'icon' => 'fa-file-contract', 'tone' => 'gold'],
      ['label' => 'Portal Mode', 'value' => 'MOU Document Repository', 'icon' => 'fa-building', 'tone' => ''],
      ['label' => 'Generated Date', 'value' => date('Y-m-d'), 'icon' => 'fa-calendar-check', 'tone' => 'success'],
  ]);

  // Official Report Header
  erp_report_header(
      'MOU DOCUMENT REPOSITORY REPORT',
      $currentUser['department'] ?? 'ALL',
      [
          'Date' => date('Y-m-d'),
          'By' => $currentUser['username'] ?? 'ERP User',
          'Page No' => '1'
      ]
  );

  // Search Box
  erp_report_search('Search company, department, filename...', 'mou-documents-table');
  ?>

  <!-- Data Table -->
  <div class="report-table-container">
    <table class="report-table" id="mou-documents-table">
      <thead>
        <tr>
          <th>Department</th>
          <th>Year</th>
          <th>Company / Partner</th>
          <th>Status</th>
          <th>Filename</th>
          <th>Upload Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($files)): ?>
          <tr class="empty-row">
            <td colspan="7" class="text-center empty-cell-text"><i>No records available.</i></td>
          </tr>
        <?php else: ?>
          <?php foreach ($files as $file): ?>
            <tr>
              <td><strong><?= htmlspecialchars($file['department'] ?? '') ?></strong></td>
              <td><?= htmlspecialchars($file['year'] ?? '') ?></td>
              <td><?= htmlspecialchars($file['company'] ?? '') ?></td>
              <td><span class="badge badge-active"><?= htmlspecialchars($file['status'] ?? 'Active') ?></span></td>
              <td><?= htmlspecialchars($file['filename'] ?? '') ?></td>
              <td><?= htmlspecialchars($file['upload_date'] ?? '') ?></td>
              <td>
                <?php if (!empty($file['filepath'])): ?>
                  <a href="<?= htmlspecialchars($file['filepath']) ?>" target="_blank" class="btn-toolbar" style="padding: 3px 8px; font-size: 11px;"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open</a>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php
  // Pagination
  erp_report_pagination('mou-documents-table', 15);

  // Signature Block
  erp_report_signatures(['Prepared By', 'HOD', 'IQAC Coordinator', 'Principal']);

  // Print Footer
  erp_report_footer();
  ?>
</div>

<?php erp_report_layout_end(); ?>
