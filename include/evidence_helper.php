<?php
/**
 * PSG PTC ERP — Evidence Helper Functions & Database Migration
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

// Define base upload directories
define('IQAC_UPLOAD_BASE_DIR', dirname(__DIR__) . '/uploads');
define('IQAC_UPLOAD_BASE_URL', 'uploads');

$uploadCategories = [
    'certificates', 'mou', 'internships', 'projects', 'publications',
    'sports', 'workshops', 'achievements', 'industry', 'guest_lectures',
    'patents', 'research', 'seminars', 'alumni', 'placements'
];

foreach ($uploadCategories as $cat) {
    $dir = IQAC_UPLOAD_BASE_DIR . '/' . $cat;
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

/**
 * Ensure evidence columns exist in database tables
 */
function iqac_ensure_evidence_columns(mysqli $conn): void
{
    $tableColumns = [
        'mou_master' => ['mou_file' => 'VARCHAR(500) DEFAULT NULL', 'mou_type' => 'VARCHAR(50) DEFAULT NULL', 'uploaded_at' => 'DATETIME DEFAULT NULL', 'uploaded_by' => 'INT DEFAULT NULL'],
        'student_sports' => ['certificate_file' => 'VARCHAR(500) DEFAULT NULL', 'uploaded_at' => 'DATETIME DEFAULT NULL', 'uploaded_by' => 'INT DEFAULT NULL'],
        'student_achievements' => ['certificate_file' => 'VARCHAR(500) DEFAULT NULL', 'uploaded_at' => 'DATETIME DEFAULT NULL', 'uploaded_by' => 'INT DEFAULT NULL'],
        'student_publications' => ['publication_file' => 'VARCHAR(500) DEFAULT NULL', 'uploaded_at' => 'DATETIME DEFAULT NULL', 'uploaded_by' => 'INT DEFAULT NULL'],
        'student_internships' => ['certificate_file' => 'VARCHAR(500) DEFAULT NULL', 'uploaded_at' => 'DATETIME DEFAULT NULL', 'uploaded_by' => 'INT DEFAULT NULL'],
        'student_projects' => ['project_file' => 'VARCHAR(500) DEFAULT NULL', 'uploaded_at' => 'DATETIME DEFAULT NULL', 'uploaded_by' => 'INT DEFAULT NULL'],
        'student_patents' => ['patent_file' => 'VARCHAR(500) DEFAULT NULL', 'uploaded_at' => 'DATETIME DEFAULT NULL', 'uploaded_by' => 'INT DEFAULT NULL'],
        'faculty_development_programs' => ['certificate_file' => 'VARCHAR(500) DEFAULT NULL', 'uploaded_at' => 'DATETIME DEFAULT NULL', 'uploaded_by' => 'INT DEFAULT NULL'],
        'research_publications' => ['publication_file' => 'VARCHAR(500) DEFAULT NULL', 'uploaded_at' => 'DATETIME DEFAULT NULL', 'uploaded_by' => 'INT DEFAULT NULL'],
        'industry_interactions' => ['evidence_file' => 'VARCHAR(500) DEFAULT NULL', 'uploaded_at' => 'DATETIME DEFAULT NULL', 'uploaded_by' => 'INT DEFAULT NULL'],
        'guest_lectures' => ['evidence_file' => 'VARCHAR(500) DEFAULT NULL', 'uploaded_at' => 'DATETIME DEFAULT NULL', 'uploaded_by' => 'INT DEFAULT NULL'],
    ];

    foreach ($tableColumns as $table => $cols) {
        if (!iqac_table_exists($conn, $table)) {
            continue;
        }
        foreach ($cols as $colName => $colDef) {
            if (!iqac_column_exists($conn, $table, $colName)) {
                $conn->query("ALTER TABLE `$table` ADD COLUMN `$colName` $colDef");
            }
        }
    }
}

// Auto-run schema migration
iqac_ensure_evidence_columns($conn);

/**
 * Check if user has permission to upload/manage evidence
 */
function iqac_can_manage_evidence(?array $user): bool
{
    if (!$user) return false;
    $role = iqac_effective_role($user);
    return in_array($role, ['admin', 'super_admin', 'iqac', 'hod', 'tutor', 'staff'], true);
}

/**
 * Get Evidence View URL
 */
function iqac_get_evidence_url(string $category, string $filename): string
{
    $cleanCat = preg_replace('/[^a_z0-9_-]/i', '', $category);
    $cleanFile = ltrim(preg_replace('/[^a_z0-9_.-]/i', '', $filename), '/\\.');
    return IQAC_UPLOAD_BASE_URL . '/' . $cleanCat . '/' . $cleanFile;
}

/**
 * Render Interactive Evidence Link / Button for Web UI
 */
function iqac_render_evidence_link(string $category, ?string $filePath, string $label = '👁 View Evidence', string $table = '', int $recordId = 0): string
{
    if (empty($filePath)) {
        return '<span class="text-muted small">No File</span>';
    }

    $url = iqac_get_evidence_url($category, basename($filePath));
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    $icon = ($ext === 'pdf') ? '📄' : '📷';
    
    return sprintf(
        '<button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-pill shadow-sm view-evidence-btn" data-url="%s" data-type="%s" data-label="%s" data-table="%s" data-id="%d" onclick="openEvidenceModal(\'%s\', \'%s\', \'%s\', \'%s\', %d)">%s %s</button>',
        htmlspecialchars($url),
        htmlspecialchars($ext),
        htmlspecialchars($label),
        htmlspecialchars($table),
        $recordId,
        htmlspecialchars($url),
        htmlspecialchars($ext),
        htmlspecialchars($label),
        htmlspecialchars($table),
        $recordId,
        $icon,
        htmlspecialchars($label)
    );
}

/**
 * Render Actual Uploaded Certificate / Evidence Image Element for Web, PDF, DOCX, and Print
 */
function iqac_render_evidence_image(string $category, ?string $filePath, string $table = '', int $recordId = 0, string $alt = 'Certificate', bool $canManage = false): string
{
    $cleanCategory = preg_replace('/[^a-z0-9_-]/i', '', $category);
    
    // Resolve disk path and check physical file existence
    $relPath = '';
    $fullPath = '';
    $ext = '';
    
    if (!empty($filePath)) {
        $cleanFileName = basename($filePath);
        $relPath = IQAC_UPLOAD_BASE_URL . '/' . $cleanCategory . '/' . $cleanFileName;
        $fullPath = IQAC_UPLOAD_BASE_DIR . '/' . $cleanCategory . '/' . $cleanFileName;
        $ext = strtolower(pathinfo($cleanFileName, PATHINFO_EXTENSION));
    }
    
    $fileExists = (!empty($fullPath) && file_exists($fullPath));
    
    // If valid JPG/PNG/JPEG image file exists on disk:
    if ($fileExists && in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        return sprintf(
            '<img src="%s" class="iqac-evidence-thumbnail" style="max-width:110px; max-height:90px; width:auto; height:auto; object-fit:contain; border:1px solid #d1d5db; border-radius:4px; padding:2px; cursor:pointer; display:inline-block; vertical-align:middle;" onclick="openEvidenceModal(\'%s\', \'%s\', \'%s\', \'%s\', %d)" alt="%s">',
            htmlspecialchars($relPath),
            htmlspecialchars($relPath),
            htmlspecialchars($ext),
            htmlspecialchars($alt),
            htmlspecialchars($table),
            $recordId,
            htmlspecialchars($alt)
        );
    }
    
    // If file exists and is PDF, check if rendered JPG thumbnail exists:
    if ($fileExists && $ext === 'pdf') {
        $baseNameWithoutExt = pathinfo($filePath, PATHINFO_FILENAME);
        $jpgFallbackPath = IQAC_UPLOAD_BASE_DIR . '/' . $cleanCategory . '/' . $baseNameWithoutExt . '.jpg';
        if (file_exists($jpgFallbackPath)) {
            $jpgRelPath = IQAC_UPLOAD_BASE_URL . '/' . $cleanCategory . '/' . $baseNameWithoutExt . '.jpg';
            return sprintf(
                '<img src="%s" class="iqac-evidence-thumbnail" style="max-width:110px; max-height:90px; width:auto; height:auto; object-fit:contain; border:1px solid #d1d5db; border-radius:4px; padding:2px; cursor:pointer; display:inline-block; vertical-align:middle;" onclick="openEvidenceModal(\'%s\', \'%s\', \'%s\', \'%s\', %d)" alt="%s">',
                htmlspecialchars($jpgRelPath),
                htmlspecialchars($relPath),
                'pdf',
                htmlspecialchars($alt),
                htmlspecialchars($table),
                $recordId,
                htmlspecialchars($alt)
            );
        }
    }
    
    // If no file exists or file missing on disk:
    // Authorized Admin/HOD/Tutor/Staff see upload action trigger
    if ($canManage && $recordId > 0 && !empty($table)) {
        return sprintf(
            '<button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 rounded small shadow-sm no-print" onclick="openEvidenceModal(\'\', \'\', \'Upload Certificate\', \'%s\', %d)" style="font-size:11px;">+ Upload Certificate</button>',
            htmlspecialchars($table),
            $recordId
        );
    }
    
    // Students / Read-only view when no evidence file exists:
    return '<span class="text-muted small" style="font-size:11px;">No Certificate</span>';
}
