<?php
/**
 * IQAC Document Styles — Shared Constants, CSS & HTML Generators
 * ================================================================
 * Source of truth for page setup, fonts, section mappings, table column
 * widths, and every reusable HTML/CSS fragment used by all export formats
 * (Print, PDF, DOCX, Excel, CSV).
 *
 * Measurements are taken directly from the official Word template:
 *   DIT_IQAC_2023_2024_Edit3-feb2025-update.docx
 *
 * Page setup (twips): Letter 12240×15840, margins 1440 all sides.
 * Usable width = 12240 − 2×1440 = 9360 twips.
 */

// ─── Page Dimensions ─────────────────────────────────────────────────────────

/**
 * Page layout constants in various units for different renderers.
 * Twips: 1 inch = 1440 twips.  Letter = 8.5″×11″.
 */
if (!defined('IQAC_PAGE_WIDTH_TWIPS'))    define('IQAC_PAGE_WIDTH_TWIPS',    12240);
if (!defined('IQAC_PAGE_HEIGHT_TWIPS'))   define('IQAC_PAGE_HEIGHT_TWIPS',   15840);
if (!defined('IQAC_MARGIN_TOP_TWIPS'))    define('IQAC_MARGIN_TOP_TWIPS',     1440);
if (!defined('IQAC_MARGIN_BOTTOM_TWIPS')) define('IQAC_MARGIN_BOTTOM_TWIPS',  1440);
if (!defined('IQAC_MARGIN_LEFT_TWIPS'))   define('IQAC_MARGIN_LEFT_TWIPS',    1440);
if (!defined('IQAC_MARGIN_RIGHT_TWIPS'))  define('IQAC_MARGIN_RIGHT_TWIPS',   1440);
if (!defined('IQAC_HEADER_TWIPS'))        define('IQAC_HEADER_TWIPS',          720);
if (!defined('IQAC_FOOTER_TWIPS'))        define('IQAC_FOOTER_TWIPS',          720);
if (!defined('IQAC_USABLE_WIDTH_TWIPS'))  define('IQAC_USABLE_WIDTH_TWIPS',   9360); // 12240 − 2880

// Millimetres (for mPDF — Letter = 215.9mm × 279.4mm, 1 in = 25.4mm)
if (!defined('IQAC_PAGE_WIDTH_MM'))   define('IQAC_PAGE_WIDTH_MM',   215.9);
if (!defined('IQAC_PAGE_HEIGHT_MM'))  define('IQAC_PAGE_HEIGHT_MM',  279.4);
if (!defined('IQAC_MARGIN_TOP'))      define('IQAC_MARGIN_TOP',       25.4);
if (!defined('IQAC_MARGIN_BOTTOM'))   define('IQAC_MARGIN_BOTTOM',    25.4);
if (!defined('IQAC_MARGIN_LEFT'))     define('IQAC_MARGIN_LEFT',      25.4);
if (!defined('IQAC_MARGIN_RIGHT'))    define('IQAC_MARGIN_RIGHT',     25.4);

// ─── Fonts ───────────────────────────────────────────────────────────────────

if (!defined('IQAC_FONT_BODY'))   define('IQAC_FONT_BODY',   'Calibri');
if (!defined('IQAC_FONT_HEADER')) define('IQAC_FONT_HEADER', 'Calibri');

// Font sizes (pt) — from template default style definitions
if (!defined('IQAC_SIZE_BODY'))       define('IQAC_SIZE_BODY',       11);
if (!defined('IQAC_SIZE_TITLE'))      define('IQAC_SIZE_TITLE',      14);   // "PSG Polytechnic College"
if (!defined('IQAC_SIZE_SUBTITLE'))   define('IQAC_SIZE_SUBTITLE',   14);   // "IQAC Formats and Procedures"
if (!defined('IQAC_SIZE_DATE'))       define('IQAC_SIZE_DATE',       12);   // Date line
if (!defined('IQAC_SIZE_HEADING'))    define('IQAC_SIZE_HEADING',    11);   // Section headings A-E
if (!defined('IQAC_SIZE_TABLE_TITLE'))define('IQAC_SIZE_TABLE_TITLE',11);   // Table heading text
if (!defined('IQAC_SIZE_TABLE_HDR'))  define('IQAC_SIZE_TABLE_HDR',   9);   // Table column header cells
if (!defined('IQAC_SIZE_TABLE_BODY')) define('IQAC_SIZE_TABLE_BODY',  9);   // Table body cells
if (!defined('IQAC_SIZE_META'))       define('IQAC_SIZE_META',       10);   // Metadata block

// ─── Section Headings ────────────────────────────────────────────────────────

/**
 * Maps section letters to their official heading text.
 */
if (!defined('IQAC_SECTION_HEADINGS')) {
    define('IQAC_SECTION_HEADINGS', [
        'A' => 'Program Curriculum',
        'B' => 'Teaching Learning Process',
        'C' => 'Admission Process',
        'D' => 'Students Performance',
        'E' => 'Faculty Performance',
    ]);
}

// ─── Table Titles ────────────────────────────────────────────────────────────

/**
 * Official table heading text for tables 1–21.
 */
if (!defined('IQAC_TABLE_TITLES')) {
    define('IQAC_TABLE_TITLES', [
        1  => 'Table 1: Program Curriculum & its amendments',
        2  => 'Table 2: Curriculum Gaps',
        3  => 'Table 3: Success rate without backlogs* (Data to be given once in a year)',
        4  => 'Table 4: Success rate with backlogs (Data to be given once in a year)',
        5  => 'Table 5: Academic Performance',
        6  => 'Table 6: Methodology to encourage bright Students',
        7  => 'Table 7: Innovative Experiments',
        8  => 'Table 8: Quality of Student Projects',
        9  => 'Table 9: Availability of Facilities and Utilization',
        10 => 'Table 10: Students Centric Learning Initiatives',
        11 => 'Table 11: Students Co-curricular activities',
        12 => 'Table 12: Students Extracurricular activities',
        13 => 'Table 13: Students Intake Information',
        14 => 'Table 14: Students Placement/Higher Studies/Entrepreneurship Details',
        15 => 'Table 15: Technical Events by the Professional Bodies / Students Chapters',
        16 => 'Table 16: Student Publications in Journals / Conferences',
        17 => 'Table 17: Students Participation in National/state level paper presentation / Technical Quiz',
        18 => 'Table 18: Faculty information',
        19 => 'Table 19: Faculty Development / Training Activities',
        20 => 'Table 20: Research Publication, Product Development, Consultancy, Manufacturing contracts, Testing Contracts, MOUs',
        21 => 'Table 21: Faculty interaction with students (Other than contact hours)',
    ]);
}

// ─── Section → Table Mapping ─────────────────────────────────────────────────

/**
 * Maps each section letter to its range of table numbers.
 * Used by renderers that insert section headings before table groups.
 */
if (!defined('IQAC_SECTIONS')) {
    define('IQAC_SECTIONS', [
        'A' => ['title' => 'Program Curriculum',          'tables' => [1, 2]],
        'B' => ['title' => 'Teaching Learning Process',   'tables' => [3, 4, 5, 6, 7, 8, 9, 10]],
        'C' => ['title' => 'Admission Process',           'tables' => [11, 12, 13, 14]],
        'D' => ['title' => 'Students Performance',        'tables' => [15, 16, 17]],
        'E' => ['title' => 'Faculty Performance',         'tables' => [18, 19, 20, 21]],
    ]);
}

// ─── Table Column Widths (twips, from template XML) ──────────────────────────

/**
 * Exact column widths in twips as extracted from the official Word template.
 * These are used directly by the DOCX generator.
 * For HTML/PDF the helper function iqac_table_col_percentages() converts to %.
 */
if (!defined('IQAC_TABLE_COLS_TWIPS')) {
    define('IQAC_TABLE_COLS_TWIPS', [
        1  => [4320, 2610, 2426],
        2  => [851, 1276, 1270, 1281, 1554, 1134, 714, 1276],
        3  => [7064, 2300],
        4  => [7064, 2300],
        5  => [7008, 2622],
        6  => [7008, 2622],
        7  => [4679, 1668, 1668, 1589, 1668],
        8  => [817, 1919, 1814, 1862, 3182],
        9  => [720, 900, 1272, 1593, 850, 2126, 1985],
        10 => [817, 3794, 2505, 2348],
        11 => [648, 1824, 978, 1285, 1446, 959, 2644],
        12 => [1171, 2473, 1403, 1927, 1085, 2965],
        13 => [709, 1418, 709, 1145, 1548, 1984, 3537],
        14 => [709, 1418, 1003, 851, 1548, 1984, 3537],
        15 => [5791, 1471, 1530, 2250],
        16 => [6836, 4064],
        17 => [906, 3082, 2266, 1437, 1826],
        18 => [1066, 1047, 1986, 1560, 1845, 1390, 1276],
        19 => [627, 944, 1710, 1890, 1489, 1350, 1440],
        20 => [739, 1170, 2431, 1170, 1260, 2161, 2289],
        21 => [682, 2249, 1376, 5876],
    ]);
}

/**
 * Border size per table.  Tables 3–6 use sz=6 (0.75pt), others use sz=4 (0.5pt).
 */
if (!defined('IQAC_TABLE_BORDER_SZ')) {
    define('IQAC_TABLE_BORDER_SZ', [
        3 => 6, 4 => 6, 5 => 6, 6 => 6, 16 => 6,
    ]);
}

// ─── Signatories ─────────────────────────────────────────────────────────────

if (!defined('IQAC_SIGNATORIES')) {
    define('IQAC_SIGNATORIES', [
        'Prepared By',
        'Faculty In-charge',
        'Tutor',
        'HOD',
        'IQAC Coordinator',
        'Principal',
    ]);
}

// ─── Column‑Width Percentages (computed from twips) ──────────────────────────

/**
 * Percentage-based column widths for HTML and PDF rendering.
 * Computed lazily and cached in a static variable.
 */
if (!defined('IQAC_TABLE_LAYOUTS')) {
    // Build percentage layouts from twips
    $__iqac_layouts = [];
    foreach (IQAC_TABLE_COLS_TWIPS as $tNum => $cols) {
        $total = array_sum($cols);
        $pcts  = [];
        foreach ($cols as $tw) {
            $pcts[] = $total > 0 ? round(($tw / $total) * 100, 1) : 0;
        }
        $__iqac_layouts[$tNum] = $pcts;
    }
    define('IQAC_TABLE_LAYOUTS', $__iqac_layouts);
    unset($__iqac_layouts);
}

// ═══════════════════════════════════════════════════════════════════════════════
// HTML / CSS Generator Functions
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Returns the complete CSS for print/PDF export that mimics Microsoft Word output.
 */
function iqac_document_css(): string
{
    $font = IQAC_FONT_BODY;
    return '
    /* ── Page Setup ─────────────────────────────────────────── */
    @page {
        size: letter;
        margin: 1in;
    }

    *, *::before, *::after {
        box-sizing: border-box;
    }

    body {
        font-family: "' . $font . '", Calibri, sans-serif;
        font-size: 11pt;
        color: #000;
        line-height: 1.15;
        margin: 0;
        padding: 0;
        background: #f0f0f0;
    }

    /* ── Page Container ────────────────────────────────────── */
    .iqac-page {
        width: 8.5in;
        min-height: 11in;
        padding: 1in;
        margin: 12px auto;
        background: #fff;
        box-shadow: 0 0 8px rgba(0,0,0,.12);
        font-family: "' . $font . '", Calibri, sans-serif;
        font-size: 11pt;
        color: #000;
        line-height: 1.15;
    }

    /* ── Header Block ──────────────────────────────────────── */
    .iqac-header {
        text-align: center;
        margin-bottom: 8pt;
    }
    .iqac-header .iqac-h1 {
        font-family: "' . $font . '", Calibri, sans-serif;
        font-size: 14pt;
        font-weight: bold;
        text-decoration: underline;
        margin: 0 0 2pt 0;
        padding: 0;
    }
    .iqac-header .iqac-h2 {
        font-family: "' . $font . '", Calibri, sans-serif;
        font-size: 14pt;
        font-weight: bold;
        margin: 0 0 2pt 0;
        padding: 0;
    }
    .iqac-header .iqac-date {
        font-family: "' . $font . '", Calibri, sans-serif;
        font-size: 12pt;
        font-weight: bold;
        text-align: right;
        margin: 4pt 0 0 0;
        padding: 0;
    }

    /* ── Metadata Table ────────────────────────────────────── */
    .iqac-meta-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12pt;
        table-layout: fixed;
    }
    .iqac-meta-table td {
        border: 0.5pt solid #000;
        padding: 3pt 5pt;
        font-size: 10pt;
        vertical-align: middle;
    }
    .iqac-meta-table td strong {
        font-weight: bold;
    }

    /* ── Section Headings (A, B, C, D, E) ──────────────────── */
    .iqac-section-heading {
        font-family: "' . $font . '", Calibri, sans-serif;
        font-size: 11pt;
        font-weight: bold;
        margin-top: 12pt;
        margin-bottom: 4pt;
        text-align: justify;
        page-break-after: avoid;
        clear: both;
    }

    /* ── Table Titles ──────────────────────────────────────── */
    .iqac-table-title {
        font-family: "' . $font . '", Calibri, sans-serif;
        font-size: 11pt;
        font-weight: bold;
        text-decoration: underline;
        margin-top: 6pt;
        margin-bottom: 4pt;
        page-break-after: avoid;
        clear: both;
    }

    /* ── Data Tables ───────────────────────────────────────── */
    table.iqac-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin-bottom: 10pt;
        clear: both;
        display: table;
    }
    table.iqac-table thead {
        display: table-header-group;
    }
    table.iqac-table tr {
        page-break-inside: avoid;
    }
    table.iqac-table th,
    table.iqac-table td {
        padding: 2pt 4pt;
        font-size: 9pt;
        text-align: center;
        vertical-align: middle;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    table.iqac-table th {
        font-weight: bold;
        background: none;
    }
    table.iqac-table td {
        background: none;
    }
    /* Default border 0.5pt */
    table.iqac-table.border-normal th,
    table.iqac-table.border-normal td {
        border: 0.5pt solid #000;
    }
    /* Heavy border 0.75pt for tables 3-6, 16 */
    table.iqac-table.border-heavy th,
    table.iqac-table.border-heavy td {
        border: 0.75pt solid #000;
    }

    .iqac-empty-row {
        font-style: italic;
        color: #888;
    }

    /* ── Signature Block ───────────────────────────────────── */
    .iqac-signature-block {
        margin-top: 20pt;
        page-break-inside: avoid;
    }
    table.iqac-sig-table {
        width: 100%;
        border-collapse: collapse;
    }
    table.iqac-sig-table td {
        border: 0.5pt solid #000;
        width: 16.66%;
        min-height: 60px;
        height: 60px;
        vertical-align: bottom;
        text-align: center;
        font-size: 9pt;
        font-weight: bold;
        padding: 4pt 2pt 6pt 2pt;
    }
    .iqac-seal-box {
        margin-top: 8pt;
        border: 0.5pt dashed #000;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9pt;
        font-weight: bold;
    }

    /* ── Page Breaks ───────────────────────────────────────── */
    .iqac-page-break {
        page-break-before: always;
    }

    /* ── Print Button ──────────────────────────────────────── */
    .iqac-no-print {
        padding: 10px;
        background: #eee;
        border-bottom: 1px solid #ccc;
        text-align: center;
    }
    .iqac-print-btn {
        padding: 8px 18px;
        background: #0d6efd;
        color: #fff;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        font-weight: bold;
        font-size: 13px;
    }
    .iqac-print-btn:hover {
        background: #0b5ed7;
    }

    /* ── Print Media ───────────────────────────────────────── */
    @media print {
        body {
            background: #fff !important;
        }
        .iqac-page {
            width: 100%;
            min-height: auto;
            padding: 0;
            margin: 0;
            box-shadow: none;
        }
        .iqac-no-print {
            display: none !important;
        }
    }';
}

// Backward-compat alias (old code may reference this)
if (!function_exists('iqac_document_styles_css')) {
    function iqac_document_styles_css(): string
    {
        return iqac_document_css();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// HTML Fragment Generators
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Returns the centred header HTML block matching the official template.
 *
 * P1: 14pt BOLD UNDERLINE center — "PSG Polytechnic College"
 * P2: 14pt BOLD center           — "Internal Quality Assurance Cell (IQAC) Formats and Procedures"
 * P3: 12pt BOLD right            — date
 */
function iqac_header_html(string $department = '', string $date = ''): string
{
    if ($date === '') {
        $date = date('d.m.Y');
    }
    $dept = htmlspecialchars($department);
    $dt   = htmlspecialchars($date);

    return '<div class="iqac-header">
    <p class="iqac-h1">PSG Polytechnic College</p>
    <p class="iqac-h2">Internal Quality Assurance Cell (IQAC) Formats and Procedures</p>
    <p class="iqac-date">' . $dt . '</p>
</div>';
}

/**
 * Returns the signature block HTML matching the template.
 */
function iqac_signature_html(): string
{
    $html = '<div class="iqac-signature-block">' . "\n";
    $html .= '<table class="iqac-sig-table"><tr>' . "\n";
    foreach (IQAC_SIGNATORIES as $role) {
        $html .= '    <td>' . htmlspecialchars($role) . '</td>' . "\n";
    }
    $html .= '</tr></table>' . "\n";
    $html .= '<div class="iqac-seal-box">Department Seal&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Institution Seal</div>' . "\n";
    $html .= '</div>';
    return $html;
}

/**
 * Returns a bold underlined table heading HTML for the given table number.
 */
function iqac_table_title_html(int $tableNumber): string
{
    $text = IQAC_TABLE_TITLES[$tableNumber] ?? ('Table ' . $tableNumber);
    return '<div class="iqac-table-title">' . htmlspecialchars($text) . '</div>';
}

/**
 * Returns a bold section heading HTML (e.g. "A. Program Curriculum").
 */
function iqac_section_heading_html(string $sectionLetter): string
{
    $letter = strtoupper(trim($sectionLetter));
    $title  = IQAC_SECTION_HEADINGS[$letter] ?? '';
    return '<div class="iqac-section-heading">' . htmlspecialchars($letter . '. ' . $title) . '</div>';
}

/**
 * Determines which section letter a table number belongs to.
 * Returns '' if none found.
 */
function iqac_table_section_letter(int $tableNumber): string
{
    foreach (IQAC_SECTIONS as $letter => $info) {
        if (in_array($tableNumber, $info['tables'], true)) {
            return $letter;
        }
    }
    return '';
}

/**
 * Returns the CSS class for a table's border weight.
 */
function iqac_table_border_class(int $tableNumber): string
{
    return isset(IQAC_TABLE_BORDER_SZ[$tableNumber]) ? 'border-heavy' : 'border-normal';
}

/**
 * Returns <colgroup> HTML with column widths for the given table number.
 * Uses percentage widths computed from the template twips.
 */
function iqac_colgroup_html(int $tableNumber, int $numCols): string
{
    $pcts = IQAC_TABLE_LAYOUTS[$tableNumber] ?? [];
    $html = '<colgroup>';
    for ($i = 0; $i < $numCols; $i++) {
        $w = isset($pcts[$i]) ? $pcts[$i] . '%' : (round(100 / max(1, $numCols), 1) . '%');
        $html .= '<col style="width:' . $w . '">';
    }
    $html .= '</colgroup>';
    return $html;
}

/**
 * Returns column widths in twips for a specific table for DOCX generation.
 * Falls back to equal division of usable width.
 */
function iqac_table_col_twips(int $tableNumber, int $numCols): array
{
    $twips = IQAC_TABLE_COLS_TWIPS[$tableNumber] ?? [];
    if (!empty($twips) && count($twips) === $numCols) {
        return $twips;
    }
    // Fallback: divide usable width equally
    $w = (int) floor(IQAC_USABLE_WIDTH_TWIPS / max(1, $numCols));
    return array_fill(0, $numCols, $w);
}

/**
 * Returns the DOCX border size (in eighths of a point) for a table.
 * sz=4 → 0.5pt, sz=6 → 0.75pt.
 */
function iqac_table_border_sz(int $tableNumber): int
{
    return IQAC_TABLE_BORDER_SZ[$tableNumber] ?? 4;
}

// ─────────────────────────────────────────────────────────────────────────────
// Full Document Renderers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Renders the body content HTML inside the .iqac-page container.
 *
 * $tables is an array of associative arrays:
 *   [ 'title' => '...', 'headers' => [...], 'rows' => [[...], ...] ]
 *
 * The title may optionally have a section-letter prefix like "A. ..." which
 * is used to emit section headings automatically.
 */
function iqac_render_document_body_html(array $meta, array $tables): string
{
    $dept       = htmlspecialchars($meta['department'] ?? '');
    $batch      = htmlspecialchars($meta['batch'] ?? '');
    $studyYear  = htmlspecialchars($meta['study_year'] ?? 'All');
    $semester   = htmlspecialchars($meta['semester'] ?? 'All');
    $acadYear   = htmlspecialchars($meta['academic_year'] ?? '');
    $criterion  = htmlspecialchars($meta['criterion'] ?? 'IQAC');
    $generatedBy= htmlspecialchars($meta['generated_by'] ?? '');
    $generatedAt= $meta['generated_at'] ?? date('Y-m-d H:i:s');
    $datePart   = htmlspecialchars(date('d.m.Y', strtotime($generatedAt)));
    $timePart   = htmlspecialchars(date('H:i:s', strtotime($generatedAt)));
    $reportType = htmlspecialchars(strtoupper($meta['report_type'] ?? ''));
    $version    = htmlspecialchars($meta['version'] ?? '1.0');

    $html = '<div class="iqac-page">';

    // ── Document Header ──
    $html .= iqac_header_html($meta['department'] ?? '', $datePart);

    // ── Metadata Grid ──
    $html .= '<table class="iqac-meta-table">
    <tr><td><strong>College:</strong> PSG Polytechnic College</td><td><strong>Department:</strong> ' . $dept . '</td></tr>
    <tr><td><strong>Academic Batch:</strong> ' . $batch . '</td><td><strong>Study Year:</strong> ' . $studyYear . '</td></tr>
    <tr><td><strong>Semester:</strong> ' . $semester . '</td><td><strong>Academic Year:</strong> ' . $acadYear . '</td></tr>
    <tr><td><strong>Criterion:</strong> ' . $criterion . '</td><td><strong>Report Type:</strong> ' . $reportType . '</td></tr>
    <tr><td><strong>Generated By:</strong> ' . $generatedBy . '</td><td><strong>Version:</strong> ' . $version . '</td></tr>
    <tr><td><strong>Generated Date:</strong> ' . $datePart . '</td><td><strong>Generated Time:</strong> ' . $timePart . '</td></tr>
    </table>';

    // ── Tables with Section Headings ──
    $lastLetter = '';
    $isFirstSection = true;

    foreach ($tables as $tIdx => $tInfo) {
        $tableNum = $tIdx + 1;
        $title    = $tInfo['title'] ?? '';
        $headers  = $tInfo['headers'] ?? [];
        $rows     = $tInfo['rows'] ?? [];

        // Parse section letter from title prefix
        $currentLetter = '';
        $cleanTitle    = $title;
        if (preg_match('/^([A-E])\.\s*(.*)/i', $title, $matches)) {
            $currentLetter = strtoupper($matches[1]);
            $cleanTitle    = $matches[2];
        } else {
            // Fallback: look up from the section mapping
            $currentLetter = iqac_table_section_letter($tableNum);
        }

        // Section heading on letter change
        if ($currentLetter !== '' && $currentLetter !== $lastLetter) {
            if (!$isFirstSection) {
                $html .= '<div class="iqac-page-break"></div>';
            }
            $html .= iqac_section_heading_html($currentLetter);
            $lastLetter    = $currentLetter;
            $isFirstSection = false;
        }

        // Table title
        $html .= iqac_table_title_html($tableNum);

        // Border class
        $borderClass = iqac_table_border_class($tableNum);
        $numCols     = count($headers);

        $html .= '<table class="iqac-table ' . $borderClass . '">';
        $html .= iqac_colgroup_html($tableNum, $numCols);

        // Header row
        $html .= '<thead><tr>';
        foreach ($headers as $hdr) {
            $html .= '<th>' . htmlspecialchars((string)$hdr) . '</th>';
        }
        $html .= '</tr></thead>';

        // Body rows
        $html .= '<tbody>';
        if (empty($rows)) {
            $html .= '<tr><td colspan="' . max(1, $numCols) . '" class="iqac-empty-row">No records available</td></tr>';
        } else {
            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($headers as $cIdx => $_) {
                    $val = isset($row[$cIdx]) ? trim((string)$row[$cIdx]) : '';
                    if ($val === '' || strtolower($val) === 'placeholder' || strtolower($val) === 'null') {
                        $val = '&nbsp;';
                    } elseif (str_contains($val, '<img') || str_contains($val, '<button') || str_contains($val, '<a ')) {
                        // Render controlled raw HTML image / element
                    } else {
                        $val = htmlspecialchars($val);
                    }
                    $html .= '<td>' . $val . '</td>';
                }
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table>';
    }

    // ── Signature Block ──
    $html .= iqac_signature_html();

    $html .= '</div>'; // .iqac-page
    return $html;
}

/**
 * Generates a complete standalone HTML page for print/PDF export.
 */
function iqac_render_document_html(array $meta, array $tables): string
{
    $reportTitle = strtoupper($meta['report_type'] ?? 'IQAC');

    $html = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>' . htmlspecialchars($reportTitle) . ' Export</title>
<style>' . iqac_document_css() . '</style>
</head>
<body>';

    // Print control bar
    $html .= '<div class="iqac-no-print">
    <button class="iqac-print-btn" onclick="window.print()">Print / Save PDF</button>
    <span style="font-size:11px;margin-left:10px;color:#555;">Use Letter size, Portrait orientation, Margins: Default (1 inch).</span>
</div>';

    $html .= iqac_render_document_body_html($meta, $tables);

    $html .= '</body>
</html>';

    return $html;
}
