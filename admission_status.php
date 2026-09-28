<?php
require_once __DIR__ . '/include/auth.php';
require_once __DIR__ . '/include/navigation.php';
require_once __DIR__ . '/include/student_portal_ui.php';

$user = iqac_require_login(['student']);
$profile = psg_student_profile($conn, $user);
$status = strtolower((string)($profile['status'] ?? $user['approval_status'] ?? 'pending'));
$steps = [
    ['Profile Completion', 'submitted', 'Student completes admission profile and documents'],
    ['Tutor Verification', $status === 'pending' ? 'pending' : 'submitted', 'Tutor checks student identity and batch mapping'],
    ['Department Staff Verification', in_array($status, ['approved', 'active'], true) ? 'submitted' : 'pending', 'Department validates academic and contact details'],
    ['Admin Approval', in_array($status, ['approved', 'active'], true) ? 'submitted' : 'pending', 'Admin activates full ERP access'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admission Status | PSG PTC ERP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/erp.css">
</head>
<body>
<div class="erp-layout">
    <?php iqac_render_sidebar($user, 'admission_status.php'); ?>
    <main class="erp-main">
        <header class="erp-topbar">
            <div>
                <h1>Admission Approval Status</h1>
                <span>Full ERP access starts only after approval</span>
            </div>
            <a class="secondary-btn" href="std_index.php">Profile Completion</a>
        </header>
        <section class="erp-content">
            <?php psg_render_student_hero($conn, $user, 'Admission Gate', 'Track your profile verification and ERP activation status.'); ?>
            <section class="panel glass-panel">
                <div class="panel-header">
                    <h2>Approval Workflow</h2>
                    <span class="status-badge <?= iqac_student_is_approved($status) ? 'verified' : 'pending' ?>"><?= htmlspecialchars(strtoupper($status ?: 'PENDING')) ?></span>
                </div>
                <div class="timeline-list">
                    <?php foreach ($steps as $step): ?>
                        <article>
                            <span><?= htmlspecialchars($step[1]) ?></span>
                            <strong><?= htmlspecialchars($step[0]) ?></strong>
                            <p><?= htmlspecialchars($step[2]) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php if (!iqac_student_is_approved($status)): ?>
                <section class="panel">
                    <div class="panel-header"><h2>Limited Access Active</h2><span class="badge gold">Admission pending</span></div>
                    <p>Until approval, only Profile Completion, Application Status, Change Password, and Logout are available.</p>
                </section>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>
