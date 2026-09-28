<?php
require_once __DIR__ . '/../include/auth.php';

$user = iqac_require_login(['staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
header('Content-Type: application/json');

$role = iqac_effective_role($user);
$staffId = null;
$stmt = $conn->prepare('SELECT staff_id FROM staff_details WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($row) {
    $staffId = (int)$row['staff_id'];
}

$isAdmin = in_array($role, ['admin', 'super_admin', 'hod', 'iqac'], true);
$pending = 0;
$recent = [];

if ($isAdmin) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM student_marks WHERE status='submitted'");
    $pending = (int)(($result ? $result->fetch_assoc() : ['total' => 0])['total'] ?? 0);
    $result = $conn->query("SELECT sm.id, sm.subject_code, sm.status, sd.full_name, sm.updated_at FROM student_marks sm JOIN student_details sd ON sd.student_id = sm.student_id WHERE sm.status='submitted' ORDER BY sm.updated_at DESC LIMIT 10");
    $recent = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
} elseif ($role === 'tutor' && $staffId) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM student_marks sm JOIN student_details sd ON sd.student_id = sm.student_id WHERE sd.tutor_staff_id = ? AND sm.status='submitted'");
    $stmt->bind_param('i', $staffId);
    $stmt->execute();
    $pending = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();
} elseif ($staffId) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total
        FROM student_marks sm
        JOIN student_details sd ON sd.student_id = sm.student_id
        JOIN staff_subject_allocation ssa ON ssa.subject_code = sm.subject_code COLLATE utf8mb4_unicode_ci
            AND ssa.staff_id = ?
            AND (ssa.class_year = sd.class_year OR ssa.class_year = '' OR ssa.class_year IS NULL)
        WHERE sm.status='submitted'");
    $stmt->bind_param('i', $staffId);
    $stmt->execute();
    $pending = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();
}

echo json_encode([
    'ok' => true,
    'pending' => $pending,
    'recent' => $recent,
    'generated_at' => date('c'),
]);
?>
