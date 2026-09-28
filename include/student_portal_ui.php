<?php
declare(strict_types=1);

function psg_student_profile(mysqli $conn, array $user): array
{
    $profile = [
        'student_id' => (int)($_SESSION['student_id'] ?? 0),
        'full_name' => (string)($user['username'] ?? 'Student'),
        'roll_number' => (string)($user['username'] ?? ''),
        'department' => (string)($user['department'] ?? 'Department Portal'),
        'current_semester' => (string)($user['semester'] ?? '-'),
        'batch' => (string)($user['batch'] ?? '-'),
        'section' => (string)($user['section'] ?? '-'),
        'status' => 'Active',
        'photo' => '',
    ];

    if (!iqac_table_exists($conn, 'student_details')) {
        return $profile;
    }

    $columns = ['student_id', 'full_name', 'roll_number', 'department', 'current_semester', 'batch', 'section'];
    foreach (['stage1_status', 'status', 'student_photo', 'photo', 'profile_photo', 'picture_path'] as $column) {
        if (iqac_column_exists($conn, 'student_details', $column)) {
            $columns[] = $column;
        }
    }
    $select = implode(', ', array_map(fn(string $column): string => "`$column`", array_unique($columns)));
    $row = null;

    if (iqac_column_exists($conn, 'student_details', 'user_id')) {
        $stmt = $conn->prepare("SELECT $select FROM student_details WHERE user_id = ? LIMIT 1");
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (!$row && $profile['student_id'] > 0) {
        $stmt = $conn->prepare("SELECT $select FROM student_details WHERE student_id = ? LIMIT 1");
        $stmt->bind_param('i', $profile['student_id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (!$row) {
        return $profile;
    }

    foreach (['student_id', 'full_name', 'roll_number', 'department', 'current_semester', 'batch', 'section'] as $key) {
        if (isset($row[$key]) && (string)$row[$key] !== '') {
            $profile[$key] = (string)$row[$key];
        }
    }
    $profile['student_id'] = (int)($row['student_id'] ?? $profile['student_id']);
    $profile['status'] = (string)($row['stage1_status'] ?? $row['status'] ?? 'Active');
    foreach (['student_photo', 'photo', 'profile_photo', 'picture_path'] as $photoColumn) {
        if (!empty($row[$photoColumn]) && is_string($row[$photoColumn])) {
            $profile['photo'] = $row[$photoColumn];
            break;
        }
    }

    return $profile;
}

function psg_count_rows(mysqli $conn, string $table, ?string $column = null, int|string|null $value = null): int
{
    if (!iqac_table_exists($conn, $table)) {
        return 0;
    }
    if ($column === null || $value === null || !iqac_column_exists($conn, $table, $column)) {
        $result = $conn->query("SELECT COUNT(*) AS total FROM `$table`");
        return $result ? (int)($result->fetch_assoc()['total'] ?? 0) : 0;
    }
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `$table` WHERE `$column` = ?");
    if (is_int($value)) {
        $stmt->bind_param('i', $value);
    } else {
        $stmt->bind_param('s', $value);
    }
    $stmt->execute();
    $total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();
    return $total;
}

function psg_student_stats(mysqli $conn, array $profile, bool $personal = true): array
{
    $studentId = (int)($profile['student_id'] ?? 0);
    $roll = (string)($profile['roll_number'] ?? '');
    $studentFilter = $personal && $studentId > 0;
    $rollFilter = $personal && $roll !== '';

    $achievementTable = iqac_table_exists($conn, 'student_achievements') ? 'student_achievements' : 'achievement_details';
    $industryTable = iqac_table_exists($conn, 'industry_visits') ? 'industry_visits' : 'student_internships';

    return [
        ['Achievement Count', psg_count_rows($conn, $achievementTable, $studentFilter ? 'student_id' : null, $studentFilter ? $studentId : null), 'Verified activity records', ''],
        ['Sports Count', psg_count_rows($conn, 'student_sports', $studentFilter ? 'student_id' : null, $studentFilter ? $studentId : null), 'Sports participation', 'success'],
        ['Publication Count', psg_count_rows($conn, 'student_publications', $rollFilter ? 'roll_no' : null, $rollFilter ? $roll : null), 'Research output', ''],
        ['Industry Visit Count', psg_count_rows($conn, $industryTable, $studentFilter ? 'student_id' : null, $studentFilter ? $studentId : null), 'Industry exposure', 'warn'],
        ['Placement Count', psg_count_rows($conn, 'placements', $studentFilter ? 'student_id' : null, $studentFilter ? $studentId : null), 'Placement records', ''],
        ['Application Status', psg_count_rows($conn, 'applications', $studentFilter ? 'student_id' : null, $studentFilter ? $studentId : null), 'Submitted requests', ''],
    ];
}

function psg_render_student_hero(mysqli $conn, array $user, string $title, string $subtitle = ''): void
{
    $profile = psg_student_profile($conn, $user);
    $stats = psg_student_stats($conn, $profile, iqac_normalize_role($user['role']) === 'student');
    $photo = (string)($profile['photo'] ?? '');
    echo '<section class="student-hero">';
    echo '<div class="student-hero-copy">';
    echo '<span class="eyebrow">PSG POLYTECHNIC COLLEGE</span>';
    echo '<h2>' . htmlspecialchars($title) . '</h2>';
    echo '<p>' . htmlspecialchars($subtitle !== '' ? $subtitle : 'Academic ERP Portal') . '</p>';
    echo '<div class="student-meta">';
    echo '<span>' . htmlspecialchars((string)$profile['full_name']) . '</span>';
    echo '<span>' . htmlspecialchars((string)$profile['roll_number']) . '</span>';
    echo '<span>' . htmlspecialchars((string)$profile['department']) . '</span>';
    echo '<span>Semester ' . htmlspecialchars((string)$profile['current_semester']) . '</span>';
    echo '</div></div>';
    echo '<div class="student-photo-card">';
    if ($photo !== '') {
        echo '<img src="' . htmlspecialchars($photo) . '" alt="Student profile photo">';
    } else {
        $initials = strtoupper(substr((string)$profile['full_name'], 0, 1) ?: 'S');
        echo '<div class="student-avatar">' . htmlspecialchars($initials) . '</div>';
    }
    echo '<strong>' . htmlspecialchars((string)$profile['full_name']) . '</strong>';
    echo '<span class="status-badge verified">' . htmlspecialchars((string)$profile['status']) . '</span>';
    echo '</div></section>';
    echo '<div class="stat-grid student-stat-grid">';
    foreach ($stats as $stat) {
        $tone = $stat[3] ? ' ' . $stat[3] : '';
        echo '<article class="stat-card' . htmlspecialchars($tone) . '"><span>' . htmlspecialchars($stat[0]) . '</span><strong>' . htmlspecialchars((string)$stat[1]) . '</strong><em>' . htmlspecialchars($stat[2]) . '</em></article>';
    }
    echo '</div>';
}

function psg_render_portal_scripts(): void
{
    echo <<<'HTML'
<script>
document.querySelectorAll('[data-search-table]').forEach(function (input) {
  input.addEventListener('input', function () {
    var table = document.getElementById(input.dataset.searchTable);
    if (!table) return;
    var query = input.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(function (row) {
      row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
  });
});
document.querySelectorAll('[data-file-preview]').forEach(function (input) {
  input.addEventListener('change', function () {
    var label = input.closest('.upload-zone')?.querySelector('[data-file-name]');
    if (label) label.textContent = input.files.length ? input.files[0].name : 'Drop file here or browse';
  });
});
document.querySelectorAll('[data-export-table]').forEach(function (button) {
  button.addEventListener('click', function () {
    var table = document.getElementById(button.dataset.exportTable);
    if (!table) return;
    var rows = Array.from(table.querySelectorAll('tr')).map(function (row) {
      return Array.from(row.children).map(function (cell) { return '"' + cell.textContent.trim().replaceAll('"', '""') + '"'; }).join(',');
    }).join('\n');
    var link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob([rows], {type: 'text/csv'}));
    link.download = (button.dataset.exportName || 'psg_ptc_export') + '.csv';
    link.click();
  });
});
</script>
HTML;
}
?>
