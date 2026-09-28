<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';
require_once __DIR__ . '/include/erp_forms_config.php';

$user = iqac_require_login(['student', 'staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
$role = iqac_effective_role($user);
$catalog = erp_form_catalog();
$allowedScopes = erp_form_allowed_scopes($role);
$scope = strtolower((string)($_GET['scope'] ?? ($allowedScopes[0] ?? 'student')));
if (!in_array($scope, $allowedScopes, true) || !isset($catalog[$scope])) {
    $scope = $allowedScopes[0] ?? 'student';
}
$formKey = (string)($_GET['form'] ?? array_key_first($catalog[$scope]));
if (!isset($catalog[$scope][$formKey])) {
    $formKey = array_key_first($catalog[$scope]);
}

$formDef = $catalog[$scope][$formKey];
$formTitle = $formDef[0];
$fields = $formDef[1];
$hasAttachment = (bool)$formDef[2];
$message = '';
$error = '';
$csrf = iqac_csrf_token();
$isAdminLike = in_array($role, ['hod', 'iqac', 'admin', 'super_admin'], true);
$isReviewer = $isAdminLike || in_array($role, ['staff', 'tutor'], true);

function erp_forms_payload(array $fields): array
{
    $payload = [];
    foreach ($fields as $field) {
        $key = erp_forms_field_key($field);
        $payload[$field] = trim((string)($_POST[$key] ?? ''));
    }
    return $payload;
}

function erp_forms_field_key(string $field): string
{
    return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($field)));
}

function erp_forms_status_class(string $status): string
{
    return match ($status) {
        'approved' => 'verified',
        'rejected', 'returned' => 'danger',
        default => 'pending',
    };
}

function erp_forms_upload(?array $file): ?string
{
    if (!$file || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed. Please try again.');
    }
    $dir = __DIR__ . '/uploads/erp_forms';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        throw new RuntimeException('Unable to prepare upload folder.');
    }
    $original = basename((string)$file['name']);
    $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $original);
    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $safe;
    $target = $dir . '/' . $name;
    if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
        throw new RuntimeException('Unable to save uploaded file.');
    }
    return 'uploads/erp_forms/' . $name;
}

function erp_forms_where_for_user(array $user, string $alias = ''): array
{
    $prefix = $alias !== '' ? $alias . '.' : '';
    $role = iqac_effective_role($user);
    if ($role === 'student') {
        return [$prefix . 'submitted_by_user_id = ?', 'i', [$user['id']]];
    }
    if ($role === 'staff') {
        return [$prefix . 'submitted_by_user_id = ?', 'i', [$user['id']]];
    }
    if ($role === 'tutor' && (int)$user['staff_id'] > 0) {
        return ['(' . $prefix . 'submitted_by_user_id = ? OR ' . $prefix . 'student_id IN (SELECT student_id FROM student_details WHERE tutor_staff_id = ?))', 'ii', [$user['id'], $user['staff_id']]];
    }
    if ($role === 'hod' && $user['department'] !== '') {
        return ['(' . $prefix . 'department = ? OR ' . $prefix . 'submitted_by_user_id = ?)', 'si', [$user['department'], $user['id']]];
    }
    return ['1=1', '', []];
}

function erp_forms_fetch(mysqli $conn, int $id, array $user): ?array
{
    [$where, $types, $params] = erp_forms_where_for_user($user);
    $stmt = $conn->prepare("SELECT * FROM erp_form_submissions WHERE id = ? AND $where LIMIT 1");
    $allTypes = 'i' . $types;
    $allParams = array_merge([$id], $params);
    $stmt->bind_param($allTypes, ...$allParams);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

if (!iqac_table_exists($conn, 'erp_form_submissions')) {
    $error = 'ERP form table is not installed. Run database/erp_forms_migration.sql once.';
}

$editId = (int)($_GET['edit'] ?? 0);
$editRow = $editId > 0 && !$error ? erp_forms_fetch($conn, $editId, $user) : null;
if ($editRow && ($editRow['form_key'] !== $formKey || $editRow['form_scope'] !== $scope)) {
    $editRow = null;
}
$editPayload = $editRow ? json_decode((string)$editRow['payload'], true) : [];
if (!is_array($editPayload)) {
    $editPayload = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    if (!iqac_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $action = (string)($_POST['action'] ?? 'save');
        try {
            if ($action === 'review' && $isReviewer) {
                $reviewId = (int)($_POST['submission_id'] ?? 0);
                $reviewRow = erp_forms_fetch($conn, $reviewId, $user);
                $status = (string)($_POST['status'] ?? 'pending');
                if (!$reviewRow || !in_array($status, ['pending', 'approved', 'rejected', 'returned'], true)) {
                    throw new RuntimeException('Review request is not valid.');
                }
                $remark = trim((string)($_POST['reviewer_remark'] ?? ''));
                $stmt = $conn->prepare('UPDATE erp_form_submissions SET status = ?, reviewer_user_id = ?, reviewer_role = ?, reviewer_remark = ?, reviewed_at = NOW() WHERE id = ?');
                $stmt->bind_param('sissi', $status, $user['id'], $role, $remark, $reviewId);
                $stmt->execute();
                $stmt->close();
                iqac_audit($conn, 'erp_form_review', $user['id'], $role, $reviewRow['form_title'] . ' set to ' . $status);
                iqac_notify($conn, (int)$reviewRow['submitted_by_user_id'], null, 'Form status updated', $reviewRow['form_title'] . ' is now ' . $status, 'erp_forms.php?scope=' . urlencode((string)$reviewRow['form_scope']) . '&form=' . urlencode((string)$reviewRow['form_key']));
                $message = 'Form status updated.';
            } else {
                $payload = erp_forms_payload($fields);
                $attachment = erp_forms_upload($_FILES['attachment'] ?? null);
                $studentId = $scope === 'student' ? (int)$user['student_id'] : null;
                $staffId = in_array($scope, ['staff', 'admin'], true) ? (int)$user['staff_id'] : null;
                $department = $user['department'] ?: null;
                $batch = $user['batch'] ?: null;
                $semester = $user['semester'] ?: null;
                $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
                $postEditId = (int)($_POST['edit_id'] ?? 0);
                $existing = $postEditId > 0 ? erp_forms_fetch($conn, $postEditId, $user) : null;
                $ownerCanEdit = $existing && (int)$existing['submitted_by_user_id'] === (int)$user['id'] && in_array((string)$existing['status'], ['submitted', 'pending', 'returned'], true);
                if ($existing && ($ownerCanEdit || $isAdminLike)) {
                    $attachment = $attachment ?: ($existing['attachment_path'] ?? null);
                    $stmt = $conn->prepare('UPDATE erp_form_submissions SET payload = ?, attachment_path = ?, status = "submitted", reviewer_user_id = NULL, reviewer_role = NULL, reviewer_remark = NULL, reviewed_at = NULL WHERE id = ?');
                    $stmt->bind_param('ssi', $payloadJson, $attachment, $postEditId);
                    $stmt->execute();
                    $stmt->close();
                    iqac_audit($conn, 'erp_form_update', $user['id'], $role, $formTitle);
                    $message = 'Form updated and resubmitted.';
                } else {
                    $stmt = $conn->prepare('INSERT INTO erp_form_submissions (form_key, form_title, form_scope, submitted_by_user_id, submitted_by_role, student_id, staff_id, department, batch, semester, status, payload, attachment_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "submitted", ?, ?)');
                    $stmt->bind_param('sssisissssss', $formKey, $formTitle, $scope, $user['id'], $role, $studentId, $staffId, $department, $batch, $semester, $payloadJson, $attachment);
                    $stmt->execute();
                    $stmt->close();
                    iqac_audit($conn, 'erp_form_create', $user['id'], $role, $formTitle);
                    iqac_notify($conn, null, 'admin', 'New ERP form submitted', $formTitle . ' submitted by ' . $user['username'], 'erp_forms.php?scope=' . urlencode($scope) . '&form=' . urlencode($formKey));
                    if ($scope === 'student') {
                        iqac_notify($conn, null, 'tutor', 'Student form submitted', $formTitle . ' requires review.', 'erp_forms.php?scope=student&form=' . urlencode($formKey));
                    }
                    $message = 'Form submitted successfully.';
                }
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

if (isset($_GET['export']) && $_GET['export'] === 'csv' && !$error) {
    [$where, $types, $params] = erp_forms_where_for_user($user);
    $stmt = $conn->prepare("SELECT id, form_title, form_scope, submitted_by_role, status, department, batch, semester, created_at FROM erp_form_submissions WHERE form_scope = ? AND $where ORDER BY created_at DESC");
    $allTypes = 's' . $types;
    $allParams = array_merge([$scope], $params);
    $stmt->bind_param($allTypes, ...$allParams);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $scope . '_erp_forms.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Form', 'Scope', 'Submitted Role', 'Status', 'Department', 'Batch', 'Semester', 'Created']);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

$counts = ['submitted' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
$rows = [];
if (!$error) {
    [$where, $types, $params] = erp_forms_where_for_user($user);
    $stmt = $conn->prepare("SELECT status, COUNT(*) AS total FROM erp_form_submissions WHERE form_scope = ? AND $where GROUP BY status");
    $allTypes = 's' . $types;
    $allParams = array_merge([$scope], $params);
    $stmt->bind_param($allTypes, ...$allParams);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $counts[$row['status']] = (int)$row['total'];
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM erp_form_submissions WHERE form_scope = ? AND $where ORDER BY updated_at DESC LIMIT 100");
    $stmt->bind_param($allTypes, ...$allParams);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($formTitle) ?> - PSG PTC ERP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/erp.css">
</head>
<body>
<div class="erp-layout">
    <?php iqac_render_sidebar($user, 'erp_forms.php'); ?>
    <main class="erp-main">
        <header class="erp-topbar">
            <div>
                <h1>ERP Forms</h1>
                <span><?= htmlspecialchars(ucfirst($scope)) ?> workflow with status tracking</span>
            </div>
            <div class="toolbar-actions">
                <button class="secondary-btn" type="button" onclick="window.print()">Print</button>
                <a class="secondary-btn" href="erp_forms.php?scope=<?= urlencode($scope) ?>&form=<?= urlencode($formKey) ?>&export=csv">Export CSV</a>
            </div>
        </header>
        <section class="erp-content">
            <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <section class="panel">
                <div class="panel-header">
                    <h2><?= htmlspecialchars($formTitle) ?></h2>
                    <span class="badge gold"><?= htmlspecialchars(strtoupper($role)) ?></span>
                </div>
                <div class="form-tabs">
                    <?php foreach ($allowedScopes as $scopeKey): ?>
                        <a class="<?= $scopeKey === $scope ? 'active' : '' ?>" href="erp_forms.php?scope=<?= urlencode($scopeKey) ?>"><?= htmlspecialchars(ucfirst($scopeKey)) ?> Forms</a>
                    <?php endforeach; ?>
                </div>
                <div class="module-grid">
                    <?php foreach ($catalog[$scope] as $key => $def): ?>
                        <a class="<?= $key === $formKey ? 'active' : '' ?>" href="erp_forms.php?scope=<?= urlencode($scope) ?>&form=<?= urlencode($key) ?>"><?= htmlspecialchars($def[0]) ?></a>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="stat-grid">
                <article class="stat-card"><span>Submitted Forms</span><strong data-form-count="submitted"><?= $counts['submitted'] ?></strong><em>New entries</em></article>
                <article class="stat-card"><span>Pending Forms</span><strong data-form-count="pending"><?= $counts['pending'] ?></strong><em>Review queue</em></article>
                <article class="stat-card"><span>Approved Forms</span><strong data-form-count="approved"><?= $counts['approved'] ?></strong><em>Accepted</em></article>
                <article class="stat-card"><span>Rejected Forms</span><strong data-form-count="rejected"><?= $counts['rejected'] ?></strong><em>Needs correction</em></article>
            </div>

            <section class="panel">
                <div class="panel-header">
                    <h2><?= $editRow ? 'Edit Submission' : 'Create Submission' ?></h2>
                    <span class="badge">Create Edit View Print Export</span>
                </div>
                <form class="erp-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="edit_id" value="<?= htmlspecialchars((string)($editRow['id'] ?? 0)) ?>">
                    <div class="form-grid">
                        <?php foreach ($fields as $field): ?>
                            <?php $key = erp_forms_field_key($field); $value = (string)($editPayload[$field] ?? ''); ?>
                            <label>
                                <span><?= htmlspecialchars($field) ?></span>
                                <?php if (str_contains(strtolower($field), 'remarks') || str_contains(strtolower($field), 'comments') || str_contains(strtolower($field), 'feedback') || str_contains(strtolower($field), 'description')): ?>
                                    <textarea name="<?= htmlspecialchars($key) ?>" rows="4"><?= htmlspecialchars($value) ?></textarea>
                                <?php else: ?>
                                    <input type="text" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                        <?php if ($hasAttachment): ?>
                            <label>
                                <span>Document / Photo Upload</span>
                                <input type="file" name="attachment">
                            </label>
                        <?php endif; ?>
                    </div>
                    <div class="toolbar-actions">
                        <button class="primary-btn" type="submit"><?= $editRow ? 'Update Form' : 'Submit Form' ?></button>
                        <a class="secondary-btn" href="erp_forms.php?scope=<?= urlencode($scope) ?>&form=<?= urlencode($formKey) ?>">Clear</a>
                    </div>
                </form>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <h2>Submissions</h2>
                    <span class="badge">Latest 100</span>
                </div>
                <div class="table-wrap">
                    <table class="erp-table">
                        <thead><tr><th>ID</th><th>Form</th><th>Status</th><th>Submitted By</th><th>Updated</th><th>Attachment</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="7">No submissions available.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($rows as $row): ?>
                            <?php $canEdit = ((int)$row['submitted_by_user_id'] === (int)$user['id'] && in_array((string)$row['status'], ['submitted', 'pending', 'returned'], true)) || $isAdminLike; ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$row['id']) ?></td>
                                <td><strong><?= htmlspecialchars($row['form_title']) ?></strong><br><span class="muted-action"><?= htmlspecialchars($row['form_key']) ?></span></td>
                                <td><span class="status-badge <?= htmlspecialchars(erp_forms_status_class((string)$row['status'])) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                <td><?= htmlspecialchars($row['submitted_by_role']) ?></td>
                                <td><?= htmlspecialchars($row['updated_at']) ?></td>
                                <td><?= $row['attachment_path'] ? '<a href="' . htmlspecialchars($row['attachment_path']) . '" target="_blank">Open</a>' : 'None' ?></td>
                                <td>
                                    <?php if ($canEdit): ?><a href="erp_forms.php?scope=<?= urlencode((string)$row['form_scope']) ?>&form=<?= urlencode((string)$row['form_key']) ?>&edit=<?= (int)$row['id'] ?>">Edit</a><?php endif; ?>
                                    <?php if ($isReviewer): ?>
                                        <form class="inline-review" method="post">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                            <input type="hidden" name="action" value="review">
                                            <input type="hidden" name="submission_id" value="<?= (int)$row['id'] ?>">
                                            <select name="status">
                                                <option value="pending">Pending</option>
                                                <option value="approved">Approve</option>
                                                <option value="returned">Return</option>
                                                <option value="rejected">Reject</option>
                                            </select>
                                            <input type="text" name="reviewer_remark" placeholder="Remark">
                                            <button type="submit">Save</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
    </main>
</div>
<script>
(function () {
  function refreshFormCounts() {
    fetch('api/erp_forms_counts.php?scope=<?= urlencode($scope) ?>', {credentials: 'same-origin'})
      .then(function (response) { return response.ok ? response.json() : null; })
      .then(function (data) {
        if (!data || !data.ok || !data.counts) return;
        Object.keys(data.counts).forEach(function (key) {
          document.querySelectorAll('[data-form-count="' + key + '"]').forEach(function (node) {
            node.textContent = data.counts[key];
          });
        });
      })
      .catch(function () {});
  }
  setInterval(refreshFormCounts, 15000);
})();
</script>
<?php include __DIR__ . '/include/evidence_modal.php'; ?>
</body>
</html>
