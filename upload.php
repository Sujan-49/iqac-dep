<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';

$user = iqac_require_login(['staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
$role = iqac_effective_role($user);
$isFullAccess = in_array($role, ['admin', 'super_admin'], true);
$staffId = (int)($_SESSION['staff_id'] ?? 0);
$currentDepartment = trim((string)($user['department'] ?? ($_SESSION['department'] ?? '')));
$defaultAcademicYear = date('n') >= 6 ? date('Y') . '-' . ((int)date('Y') + 1) : ((int)date('Y') - 1) . '-' . date('Y');
$departmentOptions = [];

if (iqac_table_exists($conn, 'mou_files')) {
  $result = $conn->query("SELECT DISTINCT department FROM mou_files WHERE department IS NOT NULL AND department <> '' ORDER BY department");
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $departmentOptions[] = (string)$row['department'];
    }
  }
}

if ($departmentOptions === []) {
  $departmentOptions = [
    'Information Technology',
    'Computer Science and Engineering',
    'Computer Networking',
    'Electronics and Communication',
    'Electrical and Electronics Engineering',
    'Mechanical Engineering',
    'Civil Engineering',
  ];
}

if ($currentDepartment !== '' && !in_array($currentDepartment, $departmentOptions, true)) {
  array_unshift($departmentOptions, $currentDepartment);
}

$documentRoot = __DIR__ . '/uploads';
$documentDir = $documentRoot . '/documents';
$imageDir = $documentRoot . '/images';
$metaDir = $documentRoot . '/mou_meta';
foreach ([$documentDir, $imageDir, $metaDir] as $directory) {
  if (!is_dir($directory)) {
    mkdir($directory, 0777, true);
  }
}

$allowedDocumentExtensions = ['pdf', 'doc', 'docx'];
$allowedImageExtensions = ['jpg', 'jpeg', 'png', 'webp'];
$maxDocumentSize = 15 * 1024 * 1024;
$maxImageSize = 5 * 1024 * 1024;
$message = '';
$messageType = 'success';

function upload_sanitize_filename(string $name): string
{
  $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename($name));
  return trim($name, '._-') ?: 'upload';
}

function upload_parse_year(string $academicYear): int
{
  if (preg_match('/^(\d{4})/', $academicYear, $match)) {
    return (int)$match[1];
  }
  return (int)date('Y');
}

function upload_meta_path(string $metaDir, int $uploadId): string
{
  return rtrim($metaDir, '/\\') . DIRECTORY_SEPARATOR . $uploadId . '.json';
}

function upload_count(mysqli $conn, string $sql, string $types = '', array $params = []): int
{
  if ($types !== '') {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
      return 0;
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['count_value'] ?? 0);
  }

  $result = $conn->query($sql);
  if (!$result) {
    return 0;
  }
  $row = $result->fetch_assoc();
  return (int)($row['count_value'] ?? 0);
}

function upload_relative_path(string $absolutePath): string
{
  return str_replace(str_replace('/', DIRECTORY_SEPARATOR, __DIR__) . DIRECTORY_SEPARATOR, '', $absolutePath);
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && in_array($role, ['admin', 'super_admin'], true)) {
  $deleteId = (int)$_GET['id'];
  $stmt = $conn->prepare('SELECT * FROM mou_files WHERE id = ?');
  if ($stmt) {
    $stmt->bind_param('i', $deleteId);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($record) {
      $documentPath = __DIR__ . '/' . ltrim((string)($record['filepath'] ?? ''), '/\\');
      $imagePath = __DIR__ . '/' . ltrim((string)($record['image'] ?? ''), '/\\');
      $metaPath = upload_meta_path($metaDir, $deleteId);
      if (is_file($documentPath)) {
        @unlink($documentPath);
      }
      if (!empty($record['image']) && is_file($imagePath)) {
        @unlink($imagePath);
      }
      if (is_file($metaPath)) {
        @unlink($metaPath);
      }
      if (iqac_table_exists($conn, 'gallery_images') && !empty($record['image'])) {
        $deleteGallery = $conn->prepare('DELETE FROM gallery_images WHERE file_path = ? OR title LIKE ?');
        if ($deleteGallery) {
          $likeTitle = '%' . ($record['company'] ?? '') . '%';
          $imageRelative = (string)($record['image'] ?? '');
          $deleteGallery->bind_param('ss', $imageRelative, $likeTitle);
          $deleteGallery->execute();
          $deleteGallery->close();
        }
      }
      $stmt = $conn->prepare('DELETE FROM mou_files WHERE id = ?');
      if ($stmt) {
        $stmt->bind_param('i', $deleteId);
        $stmt->execute();
        $stmt->close();
      }
      header('Location: upload.php?msg=' . urlencode('Record deleted successfully.'));
      exit;
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!iqac_verify_csrf($_POST['csrf_token'] ?? null)) {
    $message = 'Invalid session token. Please refresh and try again.';
    $messageType = 'danger';
  } else {
    $selectedDepartment = trim((string)($_POST['department'] ?? ''));
    if (!$isFullAccess) {
      $selectedDepartment = $currentDepartment;
    }

    $academicYear = trim((string)($_POST['academic_year'] ?? $defaultAcademicYear));
    $company = trim((string)($_POST['company'] ?? ''));
    $status = trim((string)($_POST['status'] ?? 'Pending'));
    $staffInput = trim((string)($_POST['staff_id'] ?? ''));
    $signDate = trim((string)($_POST['sign_date'] ?? ''));
    $expiryDate = trim((string)($_POST['expiry_date'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $category = trim((string)($_POST['category'] ?? 'department'));
    $tags = trim((string)($_POST['tags'] ?? ''));
    $remarks = trim((string)($_POST['remarks'] ?? ''));

    $errors = [];
    if ($selectedDepartment === '') {
      $errors[] = 'Select a department.';
    }
    if ($company === '') {
      $errors[] = 'Enter a company or organization name.';
    }
    if ($signDate === '') {
      $errors[] = 'Select a signed date.';
    }
    if (!isset($_FILES['document']) || !is_array($_FILES['document'])) {
      $errors[] = 'Document upload is required.';
    }

    $staffToSave = $staffInput !== '' ? (int)preg_replace('/\D+/', '', $staffInput) : $staffId;
    if (!$isFullAccess && $staffId > 0) {
      $staffToSave = $staffId;
    }

    $documentRelativePath = '';
    $documentOriginalName = '';
    $imageRelativePath = '';
    $uploadedImageTitle = '';

    if (!$errors) {
      $document = $_FILES['document'];
      if ((int)$document['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Document upload failed with error code ' . (int)$document['error'] . '.';
      } elseif ((int)$document['size'] > $maxDocumentSize) {
        $errors[] = 'Document must be 15 MB or smaller.';
      } else {
        $documentOriginalName = upload_sanitize_filename((string)$document['name']);
        $documentExtension = strtolower(pathinfo($documentOriginalName, PATHINFO_EXTENSION));
        if (!in_array($documentExtension, $allowedDocumentExtensions, true)) {
          $errors[] = 'Allowed document types are PDF, DOC, and DOCX.';
        } else {
          $documentMime = '';
          if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
              $documentMime = (string)finfo_file($finfo, $document['tmp_name']);
              finfo_close($finfo);
            }
          }
          $allowedDocumentMimes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
          if ($documentMime !== '' && !in_array($documentMime, $allowedDocumentMimes, true)) {
            $errors[] = 'The selected document does not match an allowed file type.';
          }
        }
      }

      if (isset($_FILES['preview_image']) && (int)$_FILES['preview_image']['error'] === UPLOAD_ERR_OK && (int)$_FILES['preview_image']['size'] > 0) {
        $image = $_FILES['preview_image'];
        if ((int)$image['size'] > $maxImageSize) {
          $errors[] = 'Preview image must be 5 MB or smaller.';
        } else {
          $imageOriginalName = upload_sanitize_filename((string)$image['name']);
          $imageExtension = strtolower(pathinfo($imageOriginalName, PATHINFO_EXTENSION));
          if (!in_array($imageExtension, $allowedImageExtensions, true)) {
            $errors[] = 'Allowed preview image types are JPG, JPEG, PNG, and WEBP.';
          } else {
            $imageMime = '';
            if (function_exists('finfo_open')) {
              $finfo = finfo_open(FILEINFO_MIME_TYPE);
              if ($finfo) {
                $imageMime = (string)finfo_file($finfo, $image['tmp_name']);
                finfo_close($finfo);
              }
            }
            $allowedImageMimes = ['image/jpeg', 'image/png', 'image/webp'];
            if ($imageMime !== '' && !in_array($imageMime, $allowedImageMimes, true)) {
              $errors[] = 'The selected preview image does not match an allowed image type.';
            }
          }
        }
      }
    }

    $yearValue = upload_parse_year($academicYear);

    if (!$errors && iqac_table_exists($conn, 'mou_files')) {
      $duplicateStmt = $conn->prepare('SELECT id FROM mou_files WHERE department = ? AND year = ? AND company = ? AND status = ? AND staff_id = ? AND sign_date = ? LIMIT 1');
      if ($duplicateStmt) {
        $duplicateStmt->bind_param('sissis', $selectedDepartment, $yearValue, $company, $status, $staffToSave, $signDate);
        $duplicateStmt->execute();
        $duplicateResult = $duplicateStmt->get_result()->fetch_assoc();
        $duplicateStmt->close();
        if ($duplicateResult) {
          $errors[] = 'A matching upload already exists for this department, year, company, status, and signing date.';
        }
      }
    }

    if (!$errors) {
      $documentExtension = strtolower(pathinfo($documentOriginalName, PATHINFO_EXTENSION));
      $documentStoredName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $documentExtension;
      $documentDestination = $documentDir . DIRECTORY_SEPARATOR . $documentStoredName;
      $documentRelativePath = upload_relative_path($documentDestination);
      if (!move_uploaded_file($document['tmp_name'], $documentDestination)) {
        $errors[] = 'Unable to store the uploaded document.';
      }
    }

    if (!$errors && isset($_FILES['preview_image']) && (int)$_FILES['preview_image']['error'] === UPLOAD_ERR_OK && (int)$_FILES['preview_image']['size'] > 0) {
      $image = $_FILES['preview_image'];
      $imageExtension = strtolower(pathinfo(upload_sanitize_filename((string)$image['name']), PATHINFO_EXTENSION));
      $imageStoredName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $imageExtension;
      $imageDestination = $imageDir . DIRECTORY_SEPARATOR . $imageStoredName;
      $imageRelativePath = upload_relative_path($imageDestination);
      if (!move_uploaded_file($image['tmp_name'], $imageDestination)) {
        $errors[] = 'Unable to store the preview image.';
        $imageRelativePath = '';
      }
    }

    if (!$errors) {
      $stmt = $conn->prepare('INSERT INTO mou_files (department, year, company, status, filename, filepath, image, upload_date, staff_id, sign_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)');
      if (!$stmt) {
        $errors[] = 'Could not prepare the upload insert statement.';
      } else {
        $imagePathForDb = $imageRelativePath !== '' ? $imageRelativePath : null;
        $staffValue = $staffToSave > 0 ? $staffToSave : null;
        $stmt->bind_param('sisssssis', $selectedDepartment, $yearValue, $company, $status, $documentOriginalName, $documentRelativePath, $imagePathForDb, $staffValue, $signDate);
        if (!$stmt->execute()) {
          $errors[] = 'Error saving upload record: ' . $stmt->error;
        }
        $insertId = (int)$stmt->insert_id;
        $stmt->close();

        if (!$errors && $insertId > 0) {
          $meta = [
            'department' => $selectedDepartment,
            'academic_year' => $academicYear,
            'company' => $company,
            'status' => $status,
            'staff_id' => $staffToSave,
            'sign_date' => $signDate,
            'expiry_date' => $expiryDate,
            'description' => $description,
            'category' => $category,
            'tags' => $tags,
            'remarks' => $remarks,
            'document_name' => $documentOriginalName,
            'document_path' => $documentRelativePath,
            'image_path' => $imageRelativePath,
            'uploaded_at' => date('Y-m-d H:i:s'),
            'uploaded_by' => $user['username'] ?? 'ERP User',
          ];
          file_put_contents(upload_meta_path($metaDir, $insertId), json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

          if ($imageRelativePath !== '' && iqac_table_exists($conn, 'gallery_images')) {
            $galleryTitle = $company . ' - ' . $selectedDepartment;
            $galleryStmt = $conn->prepare('INSERT INTO gallery_images (title, file_path, uploaded_by_staff_id, category) VALUES (?, ?, ?, ?)');
            if ($galleryStmt) {
              $galleryCategory = 'department';
              $galleryStmt->bind_param('ssis', $galleryTitle, $imageRelativePath, $staffValue, $galleryCategory);
              $galleryStmt->execute();
              $galleryStmt->close();
            }
          }

          iqac_audit($conn, 'mou_upload_created', $user['id'] ?? null, $role, $company . ' / ' . $selectedDepartment);
          iqac_notify($conn, null, 'admin', 'Department upload added', $company . ' was uploaded for ' . $selectedDepartment . '.', 'upload.php');
          iqac_notify($conn, null, 'super_admin', 'Department upload added', $company . ' was uploaded for ' . $selectedDepartment . '.', 'upload.php');

          header('Location: upload.php?msg=' . urlencode('Upload saved successfully. Gallery and notifications updated.'));
          exit;
        }
      }
    }

    if ($errors) {
      $message = implode(' ', $errors);
      $messageType = 'danger';
    }
  }
}

$files = [];
$result = $conn->query('SELECT * FROM mou_files ORDER BY upload_date DESC LIMIT 10');
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $metaPath = upload_meta_path($metaDir, (int)$row['id']);
    $row['meta'] = is_file($metaPath) ? json_decode((string)file_get_contents($metaPath), true) : [];
    $files[] = $row;
  }
}

$totalUploads = iqac_table_exists($conn, 'mou_files') ? upload_count($conn, 'SELECT COUNT(*) AS count_value FROM mou_files') : 0;
$departmentUploads = 0;
if ($currentDepartment !== '' && iqac_table_exists($conn, 'mou_files')) {
  $stmt = $conn->prepare('SELECT COUNT(*) AS count_value FROM mou_files WHERE department = ?');
  if ($stmt) {
    $stmt->bind_param('s', $currentDepartment);
    $stmt->execute();
    $departmentUploads = (int)($stmt->get_result()->fetch_assoc()['count_value'] ?? 0);
    $stmt->close();
  }
}
$galleryUploads = iqac_table_exists($conn, 'gallery_images') ? upload_count($conn, "SELECT COUNT(*) AS count_value FROM gallery_images WHERE category = 'department'") : 0;
$myUploads = $staffId > 0 && iqac_table_exists($conn, 'mou_files') ? upload_count($conn, 'SELECT COUNT(*) AS count_value FROM mou_files WHERE staff_id = ?', 'i', [$staffId]) : 0;

$backLink = htmlspecialchars(iqac_role_home($role));
$roleLabel = strtoupper($role);
$pageTitle = 'Department Uploads';
$csrfToken = iqac_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Department Uploads - PSG PTC ERP</title>
<link rel="stylesheet" href="assets/erp.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.upload-shell { display: grid; gap: 18px; }
.upload-toolbar { display: flex; justify-content: space-between; gap: 12px; align-items: center; flex-wrap: wrap; }
.upload-preview { display: grid; grid-template-columns: 1fr 320px; gap: 16px; }
.preview-box { border: 1px dashed var(--line); border-radius: 8px; background: #fbfdff; min-height: 220px; padding: 14px; display: grid; place-items: center; text-align: center; color: var(--muted); }
.preview-box img, .preview-box iframe { width: 100%; min-height: 190px; border: 0; border-radius: 8px; object-fit: cover; }
.preview-info { display: grid; gap: 10px; }
.preview-card { border: 1px solid var(--line); background: #fff; border-radius: 8px; padding: 14px; }
.preview-card strong { display: block; color: var(--royal); margin-bottom: 4px; }
.file-pill { display: inline-flex; padding: 4px 10px; border-radius: 999px; background: #eaf1ff; color: var(--royal); font-size: 12px; font-weight: 700; }
.document-meta { display: grid; gap: 6px; color: var(--muted); font-size: 13px; }
.upload-note { color: var(--muted); font-size: 13px; line-height: 1.45; }
.stack-actions { display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap; }
.thumbnail { width: 72px; height: 72px; object-fit: cover; border-radius: 8px; border: 1px solid var(--line); }
@media (max-width: 1080px) {
  .upload-preview { grid-template-columns: 1fr; }
}
@media (max-width: 900px) {
  .erp-layout { grid-template-columns: 1fr; }
  .erp-sidebar { position: relative; height: auto; }
}
</style>
</head>
<body>
<div class="erp-layout">
  <?php iqac_render_sidebar($user, 'upload.php'); ?>
  <main class="erp-main">
    <header class="erp-topbar">
      <div>
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        <div class="eyebrow">PSG PTC ERP / <?= htmlspecialchars($roleLabel) ?></div>
      </div>
      <div class="toolbar-actions">
        <a class="secondary-btn" href="<?= $backLink ?>">Back to Dashboard</a>
      </div>
    </header>
    <div class="erp-content upload-shell">
      <section class="role-hero">
        <div>
          <span class="eyebrow">Department Upload Module</span>
          <h2>Upload documents, preview images, and department evidence in one ERP flow.</h2>
          <p>Use the shared PSG PTC ERP shell, keep department uploads tied to the logged-in role, and publish notifications and gallery entries from the same save action.</p>
        </div>
        <div class="progress-stack">
          <div><span>Total uploads</span><strong><?= (int)$totalUploads ?></strong></div>
          <div><span>Department uploads</span><strong><?= (int)$departmentUploads ?></strong></div>
          <div><span>Gallery entries</span><strong><?= (int)$galleryUploads ?></strong></div>
          <div><span>My uploads</span><strong><?= (int)$myUploads ?></strong></div>
        </div>
      </section>

      <?php if (isset($_GET['msg']) && trim((string)$_GET['msg']) !== ''): ?>
        <div class="stat-card success"><strong>Success</strong><em><?= htmlspecialchars((string)$_GET['msg']) ?></em></div>
      <?php endif; ?>

      <?php if ($message !== ''): ?>
        <div class="stat-card <?= htmlspecialchars($messageType) ?>"><strong>Upload status</strong><em><?= htmlspecialchars($message) ?></em></div>
      <?php endif; ?>

      <section class="panel">
        <div class="panel-header">
          <div>
            <span class="badge gold">ERP Form</span>
            <h2>New Department Upload</h2>
          </div>
          <div class="upload-note">Allowed: PDF, DOCX, DOC, JPG, JPEG, PNG, WEBP. Documents are limited to 15 MB and preview images to 5 MB.</div>
        </div>

        <form class="erp-form" method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
          <?php if (!$isFullAccess): ?>
            <input type="hidden" name="department" value="<?= htmlspecialchars($currentDepartment) ?>">
          <?php endif; ?>

          <div class="upload-preview">
            <div class="form-grid">
              <label class="wide">
                <span>Document</span>
                <input id="documentInput" type="file" name="document" accept=".pdf,.doc,.docx" required>
                <small>Choose the main document to store in the department uploads list.</small>
              </label>
              <label>
                <span>Preview Image</span>
                <input id="imageInput" type="file" name="preview_image" accept=".jpg,.jpeg,.png,.webp">
                <small>Optional cover image for the gallery.</small>
              </label>
              <label>
                <span>Department</span>
                <?php if ($isFullAccess): ?>
                  <select name="department" required>
                    <option value="">Select department</option>
                    <?php foreach ($departmentOptions as $department): ?>
                      <option value="<?= htmlspecialchars($department) ?>" <?= $department === $currentDepartment ? 'selected' : '' ?>><?= htmlspecialchars($department) ?></option>
                    <?php endforeach; ?>
                  </select>
                <?php else: ?>
                  <input type="text" value="<?= htmlspecialchars($currentDepartment !== '' ? $currentDepartment : 'Assigned Department') ?>" readonly>
                <?php endif; ?>
              </label>
              <label>
                <span>Academic Year</span>
                <input type="text" name="academic_year" value="<?= htmlspecialchars($defaultAcademicYear) ?>" placeholder="2024-2025" required>
              </label>
              <label>
                <span>Company / Organization</span>
                <input type="text" name="company" placeholder="Enter company or organization" required>
              </label>
              <label>
                <span>Status</span>
                <select name="status" required>
                  <option value="Pending">Pending</option>
                  <option value="Active">Active</option>
                  <option value="Completed">Completed</option>
                  <option value="Expired">Expired</option>
                </select>
              </label>
              <label>
                <span>Staff</span>
                <?php if ($isFullAccess): ?>
                  <input type="text" name="staff_id" value="<?= $staffId > 0 ? (string)$staffId : '' ?>" placeholder="Staff ID">
                <?php else: ?>
                  <input type="text" value="<?= $staffId > 0 ? (string)$staffId : 'Assigned staff profile' ?>" readonly>
                  <input type="hidden" name="staff_id" value="<?= $staffId > 0 ? (string)$staffId : '' ?>">
                <?php endif; ?>
              </label>
              <label>
                <span>Signed Date</span>
                <input type="date" name="sign_date" required>
              </label>
              <label>
                <span>Expiry Date</span>
                <input type="date" name="expiry_date">
              </label>
              <label>
                <span>Category</span>
                <select name="category">
                  <option value="department" selected>Department</option>
                  <option value="gallery">Gallery</option>
                  <option value="mou">MOU</option>
                </select>
              </label>
              <label>
                <span>Tags</span>
                <input type="text" name="tags" placeholder="mou, department, industry">
              </label>
              <label class="wide">
                <span>Description</span>
                <textarea name="description" placeholder="Document summary, signatories, or approval notes"></textarea>
              </label>
              <label class="wide">
                <span>Remarks</span>
                <textarea name="remarks" placeholder="Internal remarks or follow-up notes"></textarea>
              </label>
            </div>

            <div class="preview-info">
              <div class="preview-card">
                <strong>File preview</strong>
                <div id="documentStatus" class="document-meta">
                  <span class="file-pill">No file selected</span>
                  <span>Choose a PDF or a Word document to see details here.</span>
                </div>
              </div>
              <div class="preview-box" id="imagePreview">
                <div>
                  <i class="fa-solid fa-image" style="font-size: 30px; color: var(--gold);"></i>
                  <div style="margin-top: 10px;">Preview image will appear here</div>
                </div>
              </div>
            </div>
          </div>

          <div class="stack-actions">
            <a class="secondary-btn" href="<?= $backLink ?>">Back to Dashboard</a>
            <button class="primary-btn" type="submit"><i class="fa-solid fa-cloud-arrow-up"></i>&nbsp;Upload Document</button>
          </div>
        </form>
      </section>

      <section class="panel">
        <div class="panel-header">
          <div>
            <span class="badge">Uploads</span>
            <h2>Recent Department Uploads</h2>
          </div>
        </div>
        <div class="table-wrap">
          <table class="erp-table interactive-table">
            <thead>
              <tr>
                <th>File</th>
                <th>Department</th>
                <th>Academic Year</th>
                <th>Company</th>
                <th>Status</th>
                <th>Staff</th>
                <th>Signed</th>
                <th>Expiry</th>
                <th>Category</th>
                <th>Tags</th>
                <th>Remarks</th>
                <th>Preview</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$files): ?>
                <tr><td colspan="13">No files uploaded yet.</td></tr>
              <?php endif; ?>
              <?php foreach ($files as $file): ?>
                <?php $meta = is_array($file['meta'] ?? null) ? $file['meta'] : []; ?>
                <tr>
                  <td><a href="<?= htmlspecialchars((string)($file['filepath'] ?? '')) ?>" target="_blank"><?= htmlspecialchars((string)($file['filename'] ?? 'Document')) ?></a></td>
                  <td><?= htmlspecialchars((string)($file['department'] ?? '')) ?></td>
                  <td><?= htmlspecialchars((string)($meta['academic_year'] ?? (string)($file['year'] ?? ''))) ?></td>
                  <td><?= htmlspecialchars((string)($file['company'] ?? '')) ?></td>
                  <td><?= htmlspecialchars((string)($file['status'] ?? '')) ?></td>
                  <td><?= htmlspecialchars((string)($file['staff_id'] ?? '')) ?></td>
                  <td><?= htmlspecialchars((string)($file['sign_date'] ?? '')) ?></td>
                  <td><?= htmlspecialchars((string)($meta['expiry_date'] ?? '')) ?></td>
                  <td><?= htmlspecialchars((string)($meta['category'] ?? 'department')) ?></td>
                  <td><?= htmlspecialchars((string)($meta['tags'] ?? '')) ?></td>
                  <td><?= htmlspecialchars((string)($meta['remarks'] ?? '')) ?></td>
                  <td>
                    <?php if (!empty($file['image'])): ?>
                      <img class="thumbnail" src="<?= htmlspecialchars((string)$file['image']) ?>" alt="Preview">
                    <?php else: ?>
                      <span class="badge">No image</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($isFullAccess): ?>
                      <div class="toolbar-actions">
                        <a class="secondary-btn" href="edit.php?id=<?= (int)$file['id'] ?>">Edit</a>
                        <a class="secondary-btn" href="upload.php?action=delete&id=<?= (int)$file['id'] ?>" onclick="return confirm('Delete this record?');">Delete</a>
                      </div>
                    <?php else: ?>
                      <span class="badge">View only</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </main>
</div>

<script>
(function () {
  var documentInput = document.getElementById('documentInput');
  var imageInput = document.getElementById('imageInput');
  var documentStatus = document.getElementById('documentStatus');
  var imagePreview = document.getElementById('imagePreview');

  function bytesToSize(bytes) {
  if (!bytes) return '0 B';
  var units = ['B', 'KB', 'MB', 'GB'];
  var index = 0;
  while (bytes >= 1024 && index < units.length - 1) {
    bytes /= 1024;
    index++;
  }
  return bytes.toFixed(index === 0 ? 0 : 1) + ' ' + units[index];
  }

  if (documentInput) {
  documentInput.addEventListener('change', function () {
    var file = this.files && this.files[0];
    if (!file) {
    documentStatus.innerHTML = '<span class="file-pill">No file selected</span><span>Choose a PDF or a Word document to see details here.</span>';
    return;
    }
    var preview = '<span class="file-pill">' + file.name + '</span>' +
    '<span>Type: ' + (file.type || 'unknown') + '</span>' +
    '<span>Size: ' + bytesToSize(file.size) + '</span>';
    if (file.type === 'application/pdf') {
    preview += '<iframe src="' + URL.createObjectURL(file) + '" title="Document preview"></iframe>';
    }
    documentStatus.innerHTML = preview;
  });
  }

  if (imageInput) {
  imageInput.addEventListener('change', function () {
    var file = this.files && this.files[0];
    if (!file) {
    imagePreview.innerHTML = '<div><i class="fa-solid fa-image" style="font-size: 30px; color: var(--gold);"></i><div style="margin-top: 10px;">Preview image will appear here</div></div>';
    return;
    }
    var url = URL.createObjectURL(file);
    imagePreview.innerHTML = '<img src="' + url + '" alt="Image preview">';
  });
  }
})();
</script>
</body>
</html>
