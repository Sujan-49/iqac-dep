<?php
declare(strict_types=1);

function iqac_first_existing_table(mysqli $conn, array $tables): ?string
{
    foreach ($tables as $table) {
        if (iqac_table_exists($conn, $table)) {
            return $table;
        }
    }
    return null;
}

function iqac_student_table(mysqli $conn): ?string
{
    return iqac_first_existing_table($conn, ['student_details', 'students']);
}

function iqac_student_pk(mysqli $conn, ?string $table = null): string
{
    $table = $table ?: iqac_student_table($conn);
    return $table === 'student_details' ? 'student_id' : 'id';
}

function iqac_student_name_expr(?string $table = null, string $alias = 's'): string
{
    return $table === 'student_details'
        ? "$alias.full_name"
        : "CONCAT(COALESCE($alias.first_name, ''), ' ', COALESCE($alias.last_name, ''))";
}

function iqac_student_list(mysqli $conn): array
{
    $table = iqac_student_table($conn);
    if ($table === null) {
        return [];
    }

    if ($table === 'student_details') {
        $sql = "SELECT student_id AS id, full_name AS student_name, roll_number FROM student_details ORDER BY full_name";
    } else {
        $sql = "SELECT id, CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) AS student_name, NULL AS roll_number FROM students ORDER BY first_name, last_name";
    }

    $rows = [];
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $parts = explode(' ', trim((string)$row['student_name']), 2);
            $row['first_name'] = $parts[0] ?? '';
            $row['last_name'] = $parts[1] ?? '';
            $rows[] = $row;
        }
    }
    return $rows;
}

function iqac_subject_pk(mysqli $conn): string
{
    return iqac_column_exists($conn, 'subjects', 'id') ? 'id' : 'subject_code';
}
?>
