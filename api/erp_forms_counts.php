<?php
require_once __DIR__ . '/../include/auth.php';
require_once __DIR__ . '/../include/erp_forms_config.php';

header('Content-Type: application/json');
$user = iqac_require_login(['student', 'staff', 'tutor', 'hod', 'iqac', 'admin', 'super_admin']);
$role = iqac_effective_role($user);
$allowedScopes = erp_form_allowed_scopes($role);
$scope = strtolower((string)($_GET['scope'] ?? ($allowedScopes[0] ?? 'student')));
if (!in_array($scope, $allowedScopes, true)) {
    echo json_encode(['ok' => false, 'error' => 'scope_not_allowed']);
    exit;
}

function erp_forms_api_where(array $user): array
{
    $role = iqac_effective_role($user);
    if ($role === 'student' || $role === 'staff') {
        return ['submitted_by_user_id = ?', 'i', [$user['id']]];
    }
    if ($role === 'tutor' && (int)$user['staff_id'] > 0) {
        return ['(submitted_by_user_id = ? OR student_id IN (SELECT student_id FROM student_details WHERE tutor_staff_id = ?))', 'ii', [$user['id'], $user['staff_id']]];
    }
    if ($role === 'hod' && $user['department'] !== '') {
        return ['(department = ? OR submitted_by_user_id = ?)', 'si', [$user['department'], $user['id']]];
    }
    return ['1=1', '', []];
}

$counts = ['submitted' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'returned' => 0];
if (iqac_table_exists($conn, 'erp_form_submissions')) {
    [$where, $types, $params] = erp_forms_api_where($user);
    $stmt = $conn->prepare("SELECT status, COUNT(*) AS total FROM erp_form_submissions WHERE form_scope = ? AND $where GROUP BY status");
    $allTypes = 's' . $types;
    $allParams = array_merge([$scope], $params);
    $stmt->bind_param($allTypes, ...$allParams);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $counts[$row['status']] = (int)$row['total'];
    }
    $stmt->close();
}

echo json_encode(['ok' => true, 'scope' => $scope, 'counts' => $counts]);
