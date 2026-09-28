<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';
require_once __DIR__ . '/include/student_portal_ui.php';

$user = iqac_require_login(['student', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
$role = iqac_effective_role($user);
$message = '';
$error = '';
$token = iqac_csrf_token();

$studentProfile = null;
if ($role === 'student') {
    $stmt = $conn->prepare('SELECT roll_number, full_name, department, current_semester FROM student_details WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $studentProfile = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}

$hasPublicationTable = iqac_table_exists($conn, 'student_publications');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!iqac_verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (!$hasPublicationTable) {
        $error = 'Publication table is not available in the current IQAC database.';
    } else {
        $rollNo = trim((string)($_POST['roll_no'] ?? ''));
        $studentName = trim((string)($_POST['student_name'] ?? ''));
        if ($studentProfile) {
            $rollNo = (string)$studentProfile['roll_number'];
            $studentName = (string)$studentProfile['full_name'];
        }
        $paperTitle = trim((string)($_POST['paper_title'] ?? ''));
        $journalConference = trim((string)($_POST['journal_conference'] ?? ''));
        $issnIsbn = trim((string)($_POST['issn_isbn'] ?? ''));
        $dateOfPublication = trim((string)($_POST['date_of_publication'] ?? ''));

        $stmt = $conn->prepare('INSERT INTO student_publications (roll_no, student_name, paper_title, journal_conference, issn_isbn, date_of_publication) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssssss', $rollNo, $studentName, $paperTitle, $journalConference, $issnIsbn, $dateOfPublication);
        if ($stmt->execute()) {
            $message = 'Publication record added successfully.';
            iqac_audit($conn, 'publication_submitted', $user['id'], $role, $paperTitle);
            iqac_notify($conn, null, 'tutor', 'Publication submitted', $studentName . ' submitted publication: ' . $paperTitle, 'std_publication.php');
            iqac_notify($conn, null, 'admin', 'Publication submitted', $studentName . ' submitted publication: ' . $paperTitle, 'std_publication.php');
        } else {
            $error = 'Unable to save publication record.';
        }
        $stmt->close();
    }
}

$where = '';
$types = '';
$params = [];
if ($role === 'student' && $studentProfile) {
    $where = 'WHERE roll_no = ?';
    $types = 's';
    $params[] = $studentProfile['roll_number'];
}

$publications = [];
if ($hasPublicationTable) {
    $sql = "SELECT * FROM student_publications $where ORDER BY date_of_publication DESC, id DESC";
    if ($types !== '') {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $publications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $result = $conn->query($sql);
        $publications = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

$total = count($publications);
$conference = 0;
$journal = 0;
$thisSemester = 0;
$sixMonthsAgo = strtotime('-6 months');
foreach ($publications as $publication) {
    $venue = strtolower((string)$publication['journal_conference']);
    if (str_contains($venue, 'conference')) {
        $conference++;
    } else {
        $journal++;
    }
    $publishedAt = strtotime((string)$publication['date_of_publication']);
    if ($publishedAt !== false && $publishedAt >= $sixMonthsAgo) {
        $thisSemester++;
    }
}
$scopus = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Publications</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/erp.css">
</head>
<body>
<div class="erp-layout">
    <?php iqac_render_sidebar($user, 'std_publication.php'); ?>
    <main class="erp-main">
        <header class="erp-topbar">
            <div>
                <h1>Publication Management</h1>
                <span><?= htmlspecialchars($studentProfile['department'] ?? $user['department'] ?: 'Department Portal') ?></span>
            </div>
            <div class="toolbar-actions">
                <button class="secondary-btn" type="button" onclick="window.print()">Print</button>
                <button class="primary-btn" type="button" data-export-table="publication-table">Export</button>
            </div>
        </header>

        <section class="erp-content">
            <?php psg_render_student_hero($conn, $user, 'Publication Portfolio', 'Capture student research output with PSG PTC reporting-ready records.'); ?>
            <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <section class="module-band">
                <div class="panel-header">
                    <h2>Publication Statistics</h2>
                    <span class="badge">Research Output</span>
                </div>
                <div class="stat-grid">
                    <article class="stat-card"><span>Total Publications</span><strong><?= $total ?></strong><em>Saved records</em></article>
                    <article class="stat-card"><span>Scopus Indexed</span><strong><?= $scopus ?></strong><em>Metadata pending</em></article>
                    <article class="stat-card"><span>Conference Papers</span><strong><?= $conference ?></strong><em>Venue contains conference</em></article>
                    <article class="stat-card"><span>Journal Papers</span><strong><?= $journal ?></strong><em>All other publication venues</em></article>
                    <article class="stat-card"><span>This Semester</span><strong><?= $thisSemester ?></strong><em>Last six months</em></article>
                </div>
            </section>

            <section class="panel glass-panel">
                <div class="panel-header">
                    <h2>Add Publication</h2>
                    <span class="badge gold">Student details auto-filled</span>
                </div>
                <form method="post" class="erp-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <div class="form-grid">
                        <label><span>Roll Number</span><input type="text" name="roll_no" value="<?= htmlspecialchars($studentProfile['roll_number'] ?? '') ?>" <?= $studentProfile ? 'readonly' : '' ?> required></label>
                        <label><span>Student Name</span><input type="text" name="student_name" value="<?= htmlspecialchars($studentProfile['full_name'] ?? '') ?>" <?= $studentProfile ? 'readonly' : '' ?> required></label>
                        <label class="wide"><span>Title of the Paper</span><input type="text" name="paper_title" required></label>
                        <label><span>Journal / Conference</span><input type="text" name="journal_conference" required></label>
                        <label><span>ISSN / ISBN</span><input type="text" name="issn_isbn" required></label>
                        <label><span>Date of Publication</span><input type="date" name="date_of_publication" required></label>
                        <label class="wide upload-zone"><strong>Upload Evidence</strong><span data-file-name>Evidence storage is not enabled in the current table</span><input type="file" disabled></label>
                    </div>
                    <button class="primary-btn" type="submit">Add Record</button>
                </form>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <h2>Publication History</h2>
                    <div class="table-tools"><input class="table-search" type="search" placeholder="Search publications" data-search-table="publication-table"></div>
                </div>
                <div class="table-wrap">
                    <table class="erp-table interactive-table" id="publication-table">
                        <thead>
                            <tr><th>S.No</th><th>Roll No</th><th>Student</th><th>Paper Title</th><th>Journal / Conference</th><th>ISSN / ISBN</th><th>Date</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if (!$publications): ?>
                                <tr><td colspan="9">No publications found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($publications as $index => $publication): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars((string)$publication['roll_no']) ?></td>
                                    <td><?= htmlspecialchars((string)$publication['student_name']) ?></td>
                                    <td><?= htmlspecialchars((string)$publication['paper_title']) ?></td>
                                    <td><?= htmlspecialchars((string)$publication['journal_conference']) ?></td>
                                    <td><?= htmlspecialchars((string)$publication['issn_isbn']) ?></td>
                                    <td><?= htmlspecialchars((string)$publication['date_of_publication']) ?></td>
                                    <td><span class="status-badge verified">Recorded</span></td>
                                    <td><span class="muted-action">Edit / Delete via admin data tools</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-pills"><span>1</span><span><?= max(1, (int)ceil(count($publications) / 10)) ?></span></div>
            </section>
        </section>
    </main>
</div>
<?php psg_render_portal_scripts(); ?>
</body>
</html>
