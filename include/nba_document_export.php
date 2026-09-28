<?php
/**
 * NBA / IQAC Document Export Functions
 * ====================================
 * Complete set of export functions for generating IQAC reports in multiple
 * formats: Print (HTML), PDF (mPDF), DOCX (PhpWord), Excel (XML), CSV.
 *
 * All formatting uses shared constants and helpers from iqac_document_styles.php.
 *
 * Every function is guarded with function_exists() to prevent redeclaration
 * errors when included from multiple entry points.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../iqac_document_styles.php';

use Mpdf\Mpdf;

// ═══════════════════════════════════════════════════════════════════════════════
// Utility Functions (unchanged logic)
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Returns the most recent academic year from the database.
 */
if (!function_exists('nba_default_academic_year')) {
    function nba_default_academic_year(mysqli $conn): string
    {
        if (iqac_table_exists($conn, 'semester_management')) {
            $result = $conn->query("SELECT academic_year FROM semester_management WHERE academic_year IS NOT NULL AND academic_year <> '' ORDER BY academic_year DESC, semester DESC LIMIT 1");
            if ($result && ($row = $result->fetch_assoc())) {
                return (string)$row['academic_year'];
            }
        }

        if (iqac_table_exists($conn, 'student_marks')) {
            $result = $conn->query("SELECT academic_year FROM student_marks WHERE academic_year IS NOT NULL AND academic_year <> '' ORDER BY academic_year DESC, semester DESC LIMIT 1");
            if ($result && ($row = $result->fetch_assoc())) {
                return (string)$row['academic_year'];
            }
        }

        return '';
    }
}

/**
 * Returns a human-readable label for a study year number.
 */
if (!function_exists('nba_study_year_label')) {
    function nba_study_year_label(int|string|null $studyYear): string
    {
        return match ((int)$studyYear) {
            1 => '1st Year',
            2 => '2nd Year',
            3 => '3rd Year',
            default => 'All Years',
        };
    }
}

/**
 * Returns the pair of semester numbers for a study year.
 */
if (!function_exists('nba_study_year_semesters')) {
    function nba_study_year_semesters(int|string|null $studyYear): array
    {
        return match ((int)$studyYear) {
            1 => [1, 2],
            2 => [3, 4],
            3 => [5, 6],
            default => [],
        };
    }
}

/**
 * Generates a unique report ID string.
 */
if (!function_exists('nba_doc_report_id')) {
    function nba_doc_report_id(string $prefix): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]+/i', '', $prefix)) . '-' . date('Ymd-His');
    }
}

/**
 * Wraps a single title/headers/rows set into the sections array format.
 */
if (!function_exists('nba_sheet_sections')) {
    function nba_sheet_sections(string $title, array $headers, array $rows): array
    {
        return [['title' => $title, 'headers' => $headers, 'rows' => $rows]];
    }
}

/**
 * Clean helper function to check table existence in the database.
 */
if (!function_exists('iqac_table_exists')) {
    function iqac_table_exists(mysqli $conn, string $tableName): bool
    {
        $res = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($tableName) . "'");
        return $res && $res->num_rows > 0;
    }
}

/**
 * Escapes a string for safe embedding in PDF text.
 */
if (!function_exists('nba_pdf_escape')) {
    function nba_pdf_escape(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Thin Wrapper Functions (pass-through to format-specific exporters)
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('nba_doc_export_excel')) {
    function nba_doc_export_excel(string $filename, string $title, array $meta, array $headers, array $rows): void
    {
        nba_export_excel(nba_sheet_sections($title, $headers, $rows), $meta, $filename);
    }
}

if (!function_exists('nba_doc_export_pdf')) {
    function nba_doc_export_pdf(string $filename, string $title, array $meta, array $headers, array $rows): void
    {
        nba_export_pdf(nba_sheet_sections($title, $headers, $rows), $meta, $filename);
    }
}

if (!function_exists('nba_doc_export_docx')) {
    function nba_doc_export_docx(string $filename, string $title, array $meta, array $headers, array $rows): void
    {
        nba_export_docx(nba_sheet_sections($title, $headers, $rows), $meta, $filename);
    }
}

if (!function_exists('nba_doc_export_csv')) {
    function nba_doc_export_csv(string $filename, string $title, array $meta, array $headers, array $rows): void
    {
        nba_export_csv(nba_sheet_sections($title, $headers, $rows), $meta, $filename);
    }
}

if (!function_exists('nba_doc_export_print')) {
    function nba_doc_export_print(string $title, array $meta, array $headers, array $rows): void
    {
        nba_export_print(nba_sheet_sections($title, $headers, $rows), $meta, $title . '.html');
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// 1. PRINT EXPORT (HTML) — Full Word-like document with auto-print
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('nba_export_print')) {
    /**
     * Outputs a complete HTML page that looks like Microsoft Word output.
     * Uses the shared CSS from iqac_document_css() and shared HTML generators.
     * Triggers window.print() on load for immediate printing.
     *
     * @param array  $sections Array of ['title'=>..., 'headers'=>[...], 'rows'=>[[...],...]]
     * @param array  $meta     Metadata: department, batch, academic_year, semester, etc.
     * @param string $filename Filename for Content-Disposition header
     */
    function nba_export_print(array $sections, array $meta, string $filename): void
    {
        $dept       = $meta['department'] ?? '';
        $generatedAt = $meta['generated_at'] ?? date('Y-m-d H:i:s');
        $datePart   = date('d.m.Y', strtotime($generatedAt));
        $reportTitle = strtoupper($meta['report_type'] ?? 'IQAC');

        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: inline; filename="' . $filename . '"');

        echo '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>' . htmlspecialchars($reportTitle) . ' Export — Print Preview</title>
<style>' . iqac_document_css() . '</style>
</head>
<body class="print-mode">
<div class="iqac-no-print">
    <button class="iqac-print-btn" onclick="window.print()">Print / Save PDF</button>
    <span style="font-size:11px;margin-left:10px;color:#555;">Use Letter size, Portrait orientation, Margins: Default (1 inch).</span>
</div>';

        echo '<div class="iqac-page">';

        // ── Header ──
        echo iqac_header_html($dept, $datePart);

        // ── Metadata table ──
        $batch      = htmlspecialchars($meta['batch'] ?? '');
        $studyYear  = htmlspecialchars($meta['study_year'] ?? 'All');
        $semester   = htmlspecialchars($meta['semester'] ?? 'All');
        $acadYear   = htmlspecialchars($meta['academic_year'] ?? '');
        $criterion  = htmlspecialchars($meta['criterion'] ?? 'IQAC');
        $generatedBy= htmlspecialchars($meta['generated_by'] ?? '');
        $eDept      = htmlspecialchars($dept);
        $eDatePart  = htmlspecialchars($datePart);
        $timePart   = htmlspecialchars(date('H:i:s', strtotime($generatedAt)));
        $eReportType= htmlspecialchars(strtoupper($meta['report_type'] ?? ''));
        $version    = htmlspecialchars($meta['version'] ?? '1.0');

        echo '<table class="iqac-meta-table">
<tr><td><strong>College:</strong> PSG Polytechnic College</td><td><strong>Department:</strong> ' . $eDept . '</td></tr>
<tr><td><strong>Academic Batch:</strong> ' . $batch . '</td><td><strong>Study Year:</strong> ' . $studyYear . '</td></tr>
<tr><td><strong>Semester:</strong> ' . $semester . '</td><td><strong>Academic Year:</strong> ' . $acadYear . '</td></tr>
<tr><td><strong>Criterion:</strong> ' . $criterion . '</td><td><strong>Report Type:</strong> ' . $eReportType . '</td></tr>
<tr><td><strong>Generated By:</strong> ' . $generatedBy . '</td><td><strong>Version:</strong> ' . $version . '</td></tr>
<tr><td><strong>Generated Date:</strong> ' . $eDatePart . '</td><td><strong>Generated Time:</strong> ' . $timePart . '</td></tr>
</table>';

        // ── Sections and Tables ──
        $lastLetter     = '';
        $isFirstSection = true;

        foreach ($sections as $tIdx => $tInfo) {
            $title    = $tInfo['title'] ?? '';
            $headers  = $tInfo['headers'] ?? [];
            $rows     = $tInfo['rows'] ?? [];
            $tableNum = iqac_parse_table_index($title);

            // Parse section letter from title prefix
            $currentLetter = '';
            $cleanTitle    = $title;
            if (preg_match('/^([A-E])\.\s*(.*)/i', $title, $matches)) {
                $currentLetter = strtoupper($matches[1]);
                $cleanTitle    = $matches[2];
            } else {
                $currentLetter = iqac_table_section_letter($tableNum);
            }

            // Section heading on letter change — page break between major sections
            if ($currentLetter !== '' && $currentLetter !== $lastLetter) {
                if (!$isFirstSection) {
                    echo '<div class="iqac-page-break"></div>';
                }
                echo iqac_section_heading_html($currentLetter);
                $lastLetter     = $currentLetter;
                $isFirstSection = false;
            }

            // Table title
            echo iqac_table_title_html($tableNum);

            // Border class and column count
            $borderClass = iqac_table_border_class($tableNum);
            $numCols     = count($headers);
            $pcts        = IQAC_TABLE_LAYOUTS[$tableNum] ?? [];

            echo '<table class="iqac-table ' . $borderClass . '">';

            // Header row with inline width percentages
            echo '<thead><tr>';
            foreach ($headers as $cIdx => $hdr) {
                $w = isset($pcts[$cIdx]) ? $pcts[$cIdx] . '%' : (round(100 / max(1, $numCols), 1) . '%');
                echo '<th style="width:' . $w . ';">' . htmlspecialchars((string)$hdr) . '</th>';
            }
            echo '</tr></thead>';

            // Body rows
            echo '<tbody>';
            if (empty($rows)) {
                echo '<tr><td colspan="' . max(1, $numCols) . '" class="iqac-empty-row">No records available</td></tr>';
            } else {
                foreach ($rows as $row) {
                    echo '<tr>';
                    foreach ($headers as $cIdx => $_) {
                        $val = isset($row[$cIdx]) ? trim((string)$row[$cIdx]) : '';
                        if ($val === '' || strtolower($val) === 'placeholder' || strtolower($val) === 'null') {
                            $val = '&nbsp;';
                        } elseif (str_contains($val, '<img') || str_contains($val, '<button') || str_contains($val, '<a ')) {
                            // Keep raw HTML for img elements
                        } else {
                            $val = htmlspecialchars($val);
                        }
                        echo '<td>' . $val . '</td>';
                    }
                    echo '</tr>';
                }
            }
            echo '</tbody></table>';
        }

        // ── Signature Block ──
        echo iqac_signature_html();

        echo '</div>'; // .iqac-page

        // Auto-print on load
        echo '<script>window.onload = function() { window.print(); };</script>';
        echo '</body></html>';
        exit;
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// 2. PDF EXPORT (mPDF) — High-fidelity PDF matching template layout
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('nba_export_pdf')) {
    /**
     * Generates a PDF document using mPDF library.
     * Uses the same HTML content as the print export, processed through mPDF
     * with proper headers, footers, and page breaks.
     *
     * @param array  $sections Array of ['title'=>..., 'headers'=>[...], 'rows'=>[[...],...]]
     * @param array  $meta     Metadata: department, batch, academic_year, semester, etc.
     * @param string $filename Output filename
     */
    function nba_export_pdf(array $sections, array $meta, string $filename): void
    {
        $dept        = $meta['department'] ?? '';
        $generatedAt = $meta['generated_at'] ?? date('Y-m-d H:i:s');
        $datePart    = date('d.m.Y', strtotime($generatedAt));

        // ── Create mPDF instance ──
        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => [IQAC_PAGE_WIDTH_MM, IQAC_PAGE_HEIGHT_MM], // Letter size
            'orientation'   => 'P',
            'margin_left'   => IQAC_MARGIN_LEFT,
            'margin_right'  => IQAC_MARGIN_RIGHT,
            'margin_top'    => IQAC_MARGIN_TOP + 18, // Extra space for HTML header
            'margin_bottom' => IQAC_MARGIN_BOTTOM + 10, // Extra space for footer
            'margin_header' => 5,
            'margin_footer' => 5,
            'default_font'  => 'dejavusans',
        ]);
        $mpdf->shrink_tables_to_fit = 0;

        // ── HTML Header (appears on every page) ──
        $headerHtml = '<div style="text-align:center;font-family:Calibri,sans-serif;">'
            . '<div style="font-size:14pt;font-weight:bold;text-decoration:underline;">PSG Polytechnic College</div>'
            . '<div style="font-size:14pt;font-weight:bold;">Internal Quality Assurance Cell (IQAC) Formats and Procedures</div>'
            . '<div style="font-size:12pt;font-weight:bold;text-align:right;">' . htmlspecialchars($datePart) . '</div>'
            . '</div>';
        $mpdf->SetHTMLHeader($headerHtml);

        // ── HTML Footer (page numbers + signatory roles) ──
        $sigRoles = implode(' | ', IQAC_SIGNATORIES);
        $footerHtml = '<div style="font-size:8pt;font-family:Calibri,sans-serif;border-top:0.5pt solid #000;padding-top:3pt;">'
            . '<div style="text-align:center;font-size:7pt;color:#666;">' . htmlspecialchars($sigRoles) . '</div>'
            . '<div style="text-align:center;">Page {PAGENO} of {nbpg}</div>'
            . '</div>';
        $mpdf->SetHTMLFooter($footerHtml);

        // ── Build document body HTML ──
        $css = '
        <style>
        body { font-family: Calibri, sans-serif; font-size: 11pt; color: #000; }
        table.iqac-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 8pt; clear: both; display: table; }
        table.iqac-table tr { page-break-inside: avoid; }
        table.iqac-table th, table.iqac-table td { padding: 2pt 4pt; font-size: 9pt; text-align: center; vertical-align: middle; border: 0.5pt solid #000; word-wrap: break-word; overflow-wrap: break-word; }
        table.iqac-table th { font-weight: bold; }
        table.iqac-table.border-heavy th, table.iqac-table.border-heavy td { border: 0.75pt solid #000; }
        .iqac-meta-table { width: 100%; border-collapse: collapse; margin-bottom: 10pt; }
        .iqac-meta-table td { border: 0.5pt solid #000; padding: 3pt 5pt; font-size: 10pt; width: 50%; }
        .iqac-section-heading { font-size: 11pt; font-weight: bold; margin-top: 10pt; margin-bottom: 4pt; clear: both; page-break-after: avoid; }
        .iqac-table-title { font-size: 11pt; font-weight: bold; text-decoration: underline; margin-top: 6pt; margin-bottom: 4pt; clear: both; page-break-after: avoid; }
        .iqac-empty-row { font-style: italic; color: #888; }
        .iqac-sig-table { width: 100%; border-collapse: collapse; }
        .iqac-sig-table td { border: 0.5pt solid #000; width: 16.66%; height: 50px; vertical-align: bottom; text-align: center; font-size: 9pt; font-weight: bold; padding-bottom: 5pt; }
        .iqac-seal-box { margin-top: 6pt; border: 0.5pt dashed #000; height: 40px; text-align: center; font-size: 9pt; font-weight: bold; padding-top: 12pt; }
        </style>';

        $body = $css;

        // Metadata table
        $batch       = htmlspecialchars($meta['batch'] ?? '');
        $studyYear   = htmlspecialchars($meta['study_year'] ?? 'All');
        $semester    = htmlspecialchars($meta['semester'] ?? 'All');
        $acadYear    = htmlspecialchars($meta['academic_year'] ?? '');
        $criterion   = htmlspecialchars($meta['criterion'] ?? 'IQAC');
        $generatedBy = htmlspecialchars($meta['generated_by'] ?? '');
        $eDept       = htmlspecialchars($dept);
        $timePart    = htmlspecialchars(date('H:i:s', strtotime($generatedAt)));
        $eReportType = htmlspecialchars(strtoupper($meta['report_type'] ?? ''));
        $version     = htmlspecialchars($meta['version'] ?? '1.0');
        $eDatePart   = htmlspecialchars($datePart);

        $body .= '<table class="iqac-meta-table">
<tr><td><strong>College:</strong> PSG Polytechnic College</td><td><strong>Department:</strong> ' . $eDept . '</td></tr>
<tr><td><strong>Academic Batch:</strong> ' . $batch . '</td><td><strong>Study Year:</strong> ' . $studyYear . '</td></tr>
<tr><td><strong>Semester:</strong> ' . $semester . '</td><td><strong>Academic Year:</strong> ' . $acadYear . '</td></tr>
<tr><td><strong>Criterion:</strong> ' . $criterion . '</td><td><strong>Report Type:</strong> ' . $eReportType . '</td></tr>
<tr><td><strong>Generated By:</strong> ' . $generatedBy . '</td><td><strong>Version:</strong> ' . $version . '</td></tr>
<tr><td><strong>Generated Date:</strong> ' . $eDatePart . '</td><td><strong>Generated Time:</strong> ' . $timePart . '</td></tr>
</table>';

        // Sections and tables
        $lastLetter     = '';
        $isFirstSection = true;

        foreach ($sections as $tIdx => $tInfo) {
            $title    = $tInfo['title'] ?? '';
            $headers  = $tInfo['headers'] ?? [];
            $rows     = $tInfo['rows'] ?? [];
            $tableNum = iqac_parse_table_index($title);

            $currentLetter = '';
            $cleanTitle    = $title;
            if (preg_match('/^([A-E])\.\s*(.*)/i', $title, $matches)) {
                $currentLetter = strtoupper($matches[1]);
                $cleanTitle    = $matches[2];
            } else {
                $currentLetter = iqac_table_section_letter($tableNum);
            }

            // Page break between major sections
            if ($currentLetter !== '' && $currentLetter !== $lastLetter) {
                if (!$isFirstSection) {
                    $body .= '<pagebreak />';
                }
                $sectionName = IQAC_SECTION_HEADINGS[$currentLetter] ?? '';
                $body .= '<div class="iqac-section-heading">' . htmlspecialchars($currentLetter . '. ' . $sectionName) . '</div>';
                $lastLetter     = $currentLetter;
                $isFirstSection = false;
            }

            // Table title
            $tableTitleText = IQAC_TABLE_TITLES[$tableNum] ?? $cleanTitle;
            $body .= '<div class="iqac-table-title">' . htmlspecialchars($tableTitleText) . '</div>';

            // Table with column widths
            $borderClass = iqac_table_border_class($tableNum);
            $numCols     = count($headers);
            $pcts        = IQAC_TABLE_LAYOUTS[$tableNum] ?? [];

            $body .= '<table class="iqac-table ' . $borderClass . '">';

            // Header row with inline width percentages
            $body .= '<thead><tr>';
            foreach ($headers as $cIdx => $hdr) {
                $w = isset($pcts[$cIdx]) ? $pcts[$cIdx] . '%' : (round(100 / max(1, $numCols), 1) . '%');
                $body .= '<th style="width:' . $w . ';">' . htmlspecialchars((string)$hdr) . '</th>';
            }
            $body .= '</tr></thead>';

            // Rows
            $body .= '<tbody>';
            if (empty($rows)) {
                $body .= '<tr><td colspan="' . max(1, $numCols) . '" class="iqac-empty-row">No records available</td></tr>';
            } else {
                foreach ($rows as $row) {
                    $body .= '<tr>';
                    foreach ($headers as $cIdx => $_) {
                        $val = isset($row[$cIdx]) ? trim((string)$row[$cIdx]) : '';
                        if ($val === '' || strtolower($val) === 'placeholder' || strtolower($val) === 'null') {
                            $val = '&nbsp;';
                        } elseif (str_contains($val, '<img') || str_contains($val, '<button') || str_contains($val, '<a ')) {
                            // Keep raw HTML for img elements
                        } else {
                            $val = htmlspecialchars($val);
                        }
                        $body .= '<td>' . $val . '</td>';
                    }
                    $body .= '</tr>';
                }
            }
            $body .= '</tbody></table>';
        }

        // Signature block
        $body .= '<div style="margin-top:16pt;">';
        $body .= '<table class="iqac-sig-table"><tr>';
        foreach (IQAC_SIGNATORIES as $role) {
            $body .= '<td>' . htmlspecialchars($role) . '</td>';
        }
        $body .= '</tr></table>';
        $body .= '<div class="iqac-seal-box">Department Seal&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Institution Seal</div>';
        $body .= '</div>';

        $mpdf->WriteHTML($body);
        $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// 3. DOCX EXPORT (PhpWord) — From scratch, matching template column widths
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('nba_export_docx')) {
    /**
     * Generates a DOCX document from scratch using PhpWord.
     * Uses exact column widths from the official template (in twips),
     * Calibri font, proper borders, cell padding, and signature block.
     *
     * @param array  $sections Array of ['title'=>..., 'headers'=>[...], 'rows'=>[[...],...]]
     * @param array  $meta     Metadata
     * @param string $filename Output filename
     */
    function nba_export_docx(array $sections, array $meta, string $filename): void
    {
        $templatePath = __DIR__ . '/../assets/DIT_IQAC_2023_2024_Edit3-feb2025-update.docx';
        if (file_exists($templatePath)) {
            nba_export_docx_template($templatePath, $sections, $meta, $filename);
            return;
        }

        $phpWord = new \PhpOffice\PhpWord\PhpWord();

        // ── Default document font ──
        $phpWord->setDefaultFontName(IQAC_FONT_BODY);
        $phpWord->setDefaultFontSize(IQAC_SIZE_BODY);

        // ── Section with Letter page margins ──
        $section = $phpWord->addSection([
            'pageSizeW'    => IQAC_PAGE_WIDTH_TWIPS,
            'pageSizeH'    => IQAC_PAGE_HEIGHT_TWIPS,
            'marginTop'    => IQAC_MARGIN_TOP_TWIPS,
            'marginBottom' => IQAC_MARGIN_BOTTOM_TWIPS,
            'marginLeft'   => IQAC_MARGIN_LEFT_TWIPS,
            'marginRight'  => IQAC_MARGIN_RIGHT_TWIPS,
            'headerHeight' => IQAC_HEADER_TWIPS,
            'footerHeight' => IQAC_FOOTER_TWIPS,
            'orientation'  => 'portrait',
        ]);

        // ── Paragraph alignment styles ──
        $paraCenter = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0];
        $paraLeft   = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,   'spaceBefore' => 0, 'spaceAfter' => 0];
        $paraRight  = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT,  'spaceBefore' => 0, 'spaceAfter' => 0];
        $paraBoth   = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,   'spaceBefore' => 0, 'spaceAfter' => 0];

        // ── Font styles ──
        $titleFont     = ['name' => IQAC_FONT_HEADER, 'size' => IQAC_SIZE_TITLE,      'bold' => true, 'underline' => 'single'];
        $subtitleFont  = ['name' => IQAC_FONT_HEADER, 'size' => IQAC_SIZE_SUBTITLE,   'bold' => true];
        $dateFont      = ['name' => IQAC_FONT_HEADER, 'size' => IQAC_SIZE_DATE,       'bold' => true];
        $headingFont   = ['name' => IQAC_FONT_HEADER, 'size' => IQAC_SIZE_HEADING,    'bold' => true];
        $tblTitleFont  = ['name' => IQAC_FONT_HEADER, 'size' => IQAC_SIZE_TABLE_TITLE,'bold' => true, 'underline' => 'single'];
        $tblHdrFont    = ['name' => IQAC_FONT_HEADER, 'size' => IQAC_SIZE_TABLE_HDR,  'bold' => true];
        $tblBodyFont   = ['name' => IQAC_FONT_BODY,   'size' => IQAC_SIZE_TABLE_BODY];
        $bodyFont      = ['name' => IQAC_FONT_BODY,   'size' => IQAC_SIZE_BODY];
        $metaKeyFont   = ['name' => IQAC_FONT_HEADER, 'size' => IQAC_SIZE_META,       'bold' => true];
        $metaValFont   = ['name' => IQAC_FONT_BODY,   'size' => IQAC_SIZE_META];

        // ── Document Header Block ──
        // P1: 14pt bold underline center — "PSG Polytechnic College"
        $section->addText('PSG Polytechnic College', $titleFont, $paraCenter);

        // P2: 14pt bold center — "Internal Quality Assurance Cell (IQAC) Formats and Procedures"
        $section->addText('Internal Quality Assurance Cell (IQAC) Formats and Procedures', $subtitleFont, $paraCenter);

        // P3: 12pt bold right — date
        $datePart = date('d.m.Y', strtotime($meta['generated_at'] ?? 'now'));
        $section->addText($datePart, $dateFont, $paraRight);

        $section->addTextBreak(1);

        // ── Metadata Grid Table ──
        $metaBorderSz = 6;
        $metaCellStyle = [
            'borderTopSize'    => $metaBorderSz, 'borderTopColor'    => '000000',
            'borderBottomSize' => $metaBorderSz, 'borderBottomColor' => '000000',
            'borderLeftSize'   => $metaBorderSz, 'borderLeftColor'   => '000000',
            'borderRightSize'  => $metaBorderSz, 'borderRightColor'  => '000000',
            'valign'           => 'center',
        ];
        $metaTableStyle = array_merge($metaCellStyle, [
            'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);

        $mTable   = $section->addTable($metaTableStyle);
        $colWidth = (int)(IQAC_USABLE_WIDTH_TWIPS / 4); // 4 columns

        $metaFields = [
            ['College:',        'PSG Polytechnic College',       'Department:',    $meta['department'] ?? ''],
            ['Academic Batch:', $meta['batch'] ?? '',             'Study Year:',    $meta['study_year'] ?? ''],
            ['Semester:',       $meta['semester'] ?? '',          'Academic Year:', $meta['academic_year'] ?? ''],
            ['Criterion:',      $meta['criterion'] ?? 'IQAC',   'Report Type:',   $meta['report_type'] ?? ''],
            ['Generated By:',  $meta['generated_by'] ?? '',      'Version:',       $meta['version'] ?? '1.0'],
            ['Generated Date:', $datePart,                       'Generated Time:',date('H:i:s', strtotime($meta['generated_at'] ?? 'now'))],
        ];

        $keyWidth = (int)($colWidth * 0.85);
        $valWidth = (int)($colWidth * 1.15);

        foreach ($metaFields as $mRow) {
            $row = $mTable->addRow();
            $cell = $row->addCell($keyWidth, $metaCellStyle);
            $cell->addText($mRow[0], $metaKeyFont, $paraLeft);
            $cell = $row->addCell($valWidth, $metaCellStyle);
            $cell->addText($mRow[1], $metaValFont, $paraLeft);
            $cell = $row->addCell($keyWidth, $metaCellStyle);
            $cell->addText($mRow[2], $metaKeyFont, $paraLeft);
            $cell = $row->addCell($valWidth, $metaCellStyle);
            $cell->addText($mRow[3], $metaValFont, $paraLeft);
        }

        $section->addTextBreak(1);

        // ── Data Tables ──
        $lastLetter = '';

        foreach ($sections as $tIdx => $tInfo) {
            $title    = $tInfo['title'] ?? '';
            $headers  = $tInfo['headers'] ?? [];
            $rows     = $tInfo['rows'] ?? [];
            $tableNum = iqac_parse_table_index($title);

            // Parse section letter
            $currentLetter = '';
            $cleanTitle    = $title;
            if (preg_match('/^([A-E])\.\s*(.*)/i', $title, $matches)) {
                $currentLetter = strtoupper($matches[1]);
                $cleanTitle    = $matches[2];
            } else {
                $currentLetter = iqac_table_section_letter($tableNum);
            }

            // Section heading on letter change
            if ($currentLetter !== '' && $currentLetter !== $lastLetter) {
                $sectionName = IQAC_SECTION_HEADINGS[$currentLetter] ?? $cleanTitle;
                $section->addText(
                    $currentLetter . '. ' . $sectionName,
                    $headingFont,
                    array_merge($paraBoth, ['spaceBefore' => 240, 'spaceAfter' => 80])
                );
                $lastLetter = $currentLetter;
            }

            // Table title (bold, underlined)
            $tableTitleText = IQAC_TABLE_TITLES[$tableNum] ?? $cleanTitle;
            $section->addText(
                $tableTitleText,
                $tblTitleFont,
                array_merge($paraLeft, ['spaceBefore' => 120, 'spaceAfter' => 60])
            );

            // ── Column widths from template ──
            $numCols   = count($headers);
            $colWidths = iqac_table_col_twips($tableNum, $numCols);
            $bSz       = iqac_table_border_sz($tableNum);

            // Cell style with proper borders and padding
            $dataCellStyle = [
                'borderTopSize'    => $bSz, 'borderTopColor'    => '000000',
                'borderBottomSize' => $bSz, 'borderBottomColor' => '000000',
                'borderLeftSize'   => $bSz, 'borderLeftColor'   => '000000',
                'borderRightSize'  => $bSz, 'borderRightColor'  => '000000',
                'valign'           => 'center',
            ];

            $dataTableStyle = array_merge($dataCellStyle, [
                'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
                'cellMarginTop'    => 40,  // 2pt vertical padding
                'cellMarginBottom' => 40,
                'cellMarginLeft'   => 80,  // 4pt horizontal padding
                'cellMarginRight'  => 80,
            ]);

            $table = $section->addTable($dataTableStyle);

            // Header row
            $headerRow = $table->addRow();
            try {
                $headerRow->getStyle()->setTblHeader(true);
                $headerRow->getStyle()->setCantSplit(true);
            } catch (\Throwable $e) {
                // PhpWord version may not support these
            }

            foreach ($headers as $cIdx => $hdr) {
                $cw   = $colWidths[$cIdx] ?? (int)(IQAC_USABLE_WIDTH_TWIPS / max(1, $numCols));
                $cell = $headerRow->addCell($cw, $dataCellStyle);
                $cell->addText((string)$hdr, $tblHdrFont, $paraCenter);
            }

            // Data rows
            if (empty($rows)) {
                $dataRow = $table->addRow();
                $totalWidth = array_sum($colWidths);
                $cell = $dataRow->addCell($totalWidth, $dataCellStyle);
                $cell->addText('No records available', array_merge($tblBodyFont, ['italic' => true]), $paraCenter);
            } else {
                foreach ($rows as $rowValues) {
                    $dataRow = $table->addRow();
                    try {
                        $dataRow->getStyle()->setCantSplit(true);
                    } catch (\Throwable $e) {
                        // Silently continue
                    }

                    foreach ($headers as $cIdx => $_) {
                        $cw  = $colWidths[$cIdx] ?? (int)(IQAC_USABLE_WIDTH_TWIPS / max(1, $numCols));
                        $val = isset($rowValues[$cIdx]) ? trim((string)$rowValues[$cIdx]) : '';
                        if ($val === '' || strtolower($val) === 'placeholder' || strtolower($val) === 'null') {
                            $val = '';
                        }

                        $cell = $dataRow->addCell($cw, $dataCellStyle);
                        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $val, $m)) {
                            $cleanText = trim(strip_tags(preg_replace('/<img[^>]*>/i', '', $val)));
                            if ($cleanText !== '') {
                                $cell->addText($cleanText, $tblBodyFont, $paraCenter);
                            }
                            $imgRel = html_entity_decode($m[1]);
                            $imgPath = IQAC_UPLOAD_BASE_DIR . '/' . preg_replace('#^uploads/#i', '', $imgRel);
                            if (file_exists($imgPath)) {
                                try {
                                    $cell->addImage($imgPath, ['width' => 80, 'height' => 60, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                                } catch (\Throwable $e) {
                                    $cell->addText('[Image Error]', $tblBodyFont, $paraCenter);
                                }
                            }
                        } else {
                            $cell->addText($val, $tblBodyFont, $paraCenter);
                        }
                    }
                }
            }

            $section->addTextBreak(1);
        }

        // ── Signature Block ──
        $section->addText('Approval and Signature Area', $headingFont, $paraLeft);

        $sigBorderStyle = [
            'borderTopSize'    => 6, 'borderTopColor'    => '000000',
            'borderBottomSize' => 6, 'borderBottomColor' => '000000',
            'borderLeftSize'   => 6, 'borderLeftColor'   => '000000',
            'borderRightSize'  => 6, 'borderRightColor'  => '000000',
        ];

        $sigTable = $section->addTable(array_merge($sigBorderStyle, [
            'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]));

        $sigRow = $sigTable->addRow();
        try {
            $sigRow->getStyle()->setCantSplit(true);
        } catch (\Throwable $e) {
            // Continue
        }

        $sigWidth = (int)(IQAC_USABLE_WIDTH_TWIPS / count(IQAC_SIGNATORIES));

        foreach (IQAC_SIGNATORIES as $roleName) {
            $cell = $sigRow->addCell($sigWidth, array_merge($sigBorderStyle, [
                'valign' => 'bottom',
            ]));
            $cell->addTextBreak(2);
            $cell->addText($roleName, ['name' => IQAC_FONT_HEADER, 'size' => 9, 'bold' => true], $paraCenter);
        }

        $section->addTextBreak(1);

        // Seal row
        $sealTable = $section->addTable(array_merge($sigBorderStyle, [
            'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]));
        $sealRow  = $sealTable->addRow();
        $sealCell = $sealRow->addCell(IQAC_USABLE_WIDTH_TWIPS, array_merge($sigBorderStyle, [
            'valign' => 'center',
        ]));
        $sealCell->addTextBreak(1);
        $sealCell->addText(
            'Department Seal Area                           Institution Seal Area',
            ['name' => IQAC_FONT_HEADER, 'size' => 9, 'bold' => true],
            $paraCenter
        );
        $sealCell->addTextBreak(1);

        // ── Write and output ──
        $outputPath = tempnam(sys_get_temp_dir(), 'docx');
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($outputPath);

        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($outputPath));
        flush();
        readfile($outputPath);
        unlink($outputPath);
        exit;
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// 3b. DOCX EXPORT (Template-based) — Loads official .docx template
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('nba_export_docx_template')) {
    /**
     * Generates a DOCX by loading the official template file and populating it.
     * Uses PhpWord IOFactory::load() with Reflection for row clearing where
     * needed (PhpWord 1.4 requires it).
     *
     * The template has 24 tables; the first 3 are header/meta tables.
     * Tables 4-24 map to report tables 1-21.
     *
     * @param string $templatePath Path to the .docx template file
     * @param array  $sections     Array of ['title'=>..., 'headers'=>[...], 'rows'=>[[...],...]]
     * @param array  $meta         Metadata
     * @param string $filename     Output filename
     */
    function nba_export_docx_template(string $templatePath, array $sections, array $meta, string $filename): void
    {
        // ── Validate template ──
        if (!file_exists($templatePath)) {
            // Fall back to from-scratch DOCX export
            error_log('IQAC: Template file not found at ' . $templatePath . ', falling back to scratch export');
            nba_export_docx($sections, $meta, $filename);
            return;
        }

        // ── Load template ──
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($templatePath);

        // ── Get all sections from the document ──
        $docSections = $phpWord->getSections();
        if (empty($docSections)) {
            nba_export_docx($sections, $meta, $filename);
            return;
        }

        $docSection = $docSections[0];
        $elements   = $docSection->getElements();

        // ── Locate all tables in the document ──
        $templateTables = [];
        foreach ($elements as $element) {
            if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                $templateTables[] = $element;
            }
        }

        // ── Text replacement in all text elements (department, dates) ──
        $dept     = $meta['department'] ?? '';
        $acadYear = $meta['academic_year'] ?? '';
        $datePart = date('d.m.Y', strtotime($meta['generated_at'] ?? 'now'));
        $batch    = $meta['batch'] ?? '';
        $semester = $meta['semester'] ?? '';

        $replacements = [
            '<<Department>>'    => $dept,
            '<<department>>'    => $dept,
            '<<AcademicYear>>'  => $acadYear,
            '<<academic_year>>' => $acadYear,
            '<<Date>>'          => $datePart,
            '<<date>>'          => $datePart,
            '<<Batch>>'         => $batch,
            '<<batch>>'         => $batch,
            '<<Semester>>'      => $semester,
            '<<semester>>'      => $semester,
        ];

        // Walk all text elements and perform replacements
        _nba_docx_replace_text_recursive($elements, $replacements);

        // ── Populate data tables ──
        // Template mapping: template tables index 0-23 → report tables 1-24
        $templateOffset = 0; // The template tables start directly at index 0

        $paraCenter = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0];
        $tblHdrFont  = ['name' => IQAC_FONT_HEADER, 'size' => IQAC_SIZE_TABLE_HDR,  'bold' => true];
        $tblBodyFont = ['name' => IQAC_FONT_BODY,   'size' => IQAC_SIZE_TABLE_BODY];

        foreach ($sections as $tIdx => $tInfo) {
            $headers       = $tInfo['headers'] ?? [];
            $rows          = $tInfo['rows'] ?? [];
            $tableNum      = iqac_parse_table_index($tInfo['title'] ?? '');
            $templateIndex = $tableNum - 1;

            if (!isset($templateTables[$templateIndex])) {
                continue;
            }

            $table   = $templateTables[$templateIndex];
            
            // Disable table floating positioning to ensure proper inline document flow
            $style = $table->getStyle();
            if (is_object($style)) {
                $style->setPosition(null);
                $style->setAlignment(\PhpOffice\PhpWord\SimpleType\JcTable::CENTER);
            }
            
            $numCols = count($headers);

            // Get column widths (scaled to fit within margins if necessary)
            $colWidths = iqac_table_col_twips($tableNum, $numCols);
            $bSz       = iqac_table_border_sz($tableNum);

            $dataCellStyle = [
                'borderTopSize'    => $bSz, 'borderTopColor'    => '000000',
                'borderBottomSize' => $bSz, 'borderBottomColor' => '000000',
                'borderLeftSize'   => $bSz, 'borderLeftColor'   => '000000',
                'borderRightSize'  => $bSz, 'borderRightColor'  => '000000',
                'valign'           => 'center',
            ];

            // Clear existing rows beyond the header using Reflection if needed
            $existingRows = $table->getRows();
            if (!empty($existingRows)) {
                $headerRow = $existingRows[0];
                try {
                    $headerRow->getStyle()->setTblHeader(true);
                    $headerRow->getStyle()->setCantSplit(true);
                } catch (\Throwable $e) {}
            }

            if (count($existingRows) > 1) {
                try {
                    $refProp = new \ReflectionProperty($table, 'rows');
                    $refProp->setAccessible(true);
                    $currentRows = $refProp->getValue($table);
                    // Keep only the first row (header)
                    $refProp->setValue($table, array_slice($currentRows, 0, 1));
                } catch (\ReflectionException $e) {
                    // If reflection fails, we cannot clear rows — just add data below
                    error_log('IQAC Template: Could not clear rows via Reflection: ' . $e->getMessage());
                }
            }

            // Add data rows
            if (empty($rows)) {
                $dataRow = $table->addRow();
                $totalWidth = array_sum($colWidths);
                $cell = $dataRow->addCell($totalWidth, $dataCellStyle);
                $cell->addText('No records available', array_merge($tblBodyFont, ['italic' => true]), $paraCenter);
            } else {
                foreach ($rows as $rowValues) {
                    $dataRow = $table->addRow();
                    try {
                        $dataRow->getStyle()->setCantSplit(true);
                    } catch (\Throwable $e) {}

                    foreach ($headers as $cIdx => $_) {
                        $cw  = $colWidths[$cIdx] ?? (int)(IQAC_USABLE_WIDTH_TWIPS / max(1, $numCols));
                        $val = isset($rowValues[$cIdx]) ? trim((string)$rowValues[$cIdx]) : '';
                        if ($val === '' || strtolower($val) === 'placeholder' || strtolower($val) === 'null') {
                            $val = '';
                        }

                        $cell = $dataRow->addCell($cw, $dataCellStyle);
                        $cell->addText($val, $tblBodyFont, $paraCenter);
                    }
                }
            }
        }

        // ── Write and output ──
        $outputPath = tempnam(sys_get_temp_dir(), 'docx');
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($outputPath);

        // Fix raw ampersands in generated XML to prevent Word corruption
        $zip = new \ZipArchive();
        if ($zip->open($outputPath) === true) {
            $xml = $zip->getFromName('word/document.xml');
            if ($xml !== false) {
                $xml = preg_replace('/&(?!(amp|lt|gt|quot|apos);)/', '&amp;', $xml);
                $zip->addFromString('word/document.xml', $xml);
            }
            $zip->close();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($outputPath));
        flush();
        readfile($outputPath);
        unlink($outputPath);
        exit;
    }
}

/**
 * Recursively replaces text placeholders in PhpWord elements.
 */
if (!function_exists('_nba_docx_replace_text_recursive')) {
    function _nba_docx_replace_text_recursive(array $elements, array $replacements): void
    {
        foreach ($elements as $element) {
            if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                foreach ($element->getElements() as $child) {
                    if ($child instanceof \PhpOffice\PhpWord\Element\Text) {
                        $text = $child->getText();
                        $newText = str_replace(array_keys($replacements), array_values($replacements), $text);
                        if ($newText !== $text) {
                            try {
                                $refProp = new \ReflectionProperty($child, 'text');
                                $refProp->setAccessible(true);
                                $refProp->setValue($child, $newText);
                            } catch (\ReflectionException $e) {
                                // Cannot modify text
                            }
                        }
                    }
                }
            } elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                $text = $element->getText();
                $newText = str_replace(array_keys($replacements), array_values($replacements), $text);
                if ($newText !== $text) {
                    try {
                        $refProp = new \ReflectionProperty($element, 'text');
                        $refProp->setAccessible(true);
                        $refProp->setValue($element, $newText);
                    } catch (\ReflectionException $e) {
                        // Cannot modify text
                    }
                }
            } elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                foreach ($element->getRows() as $row) {
                    foreach ($row->getCells() as $cell) {
                        _nba_docx_replace_text_recursive($cell->getElements(), $replacements);
                    }
                }
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// 3c. Legacy mPDF export — renamed to avoid conflicts
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('_nba_export_pdf_legacy_mpdf')) {
    /**
     * Legacy PDF export function. Renamed from nba_export_pdf_mpdf().
     * Kept for backward compatibility; callers should use nba_export_pdf() instead.
     */
    function _nba_export_pdf_legacy_mpdf(array $sections, array $meta, string $filename): void
    {
        // Delegates to the primary PDF export
        nba_export_pdf($sections, $meta, $filename);
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// 4. EXCEL EXPORT (XML Spreadsheet) — Enhanced with template-matching layout
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('nba_export_excel')) {
    /**
     * Generates an XML Spreadsheet (Excel) file with proper header block,
     * column widths matching the template, section heading rows, and
     * signature rows at the bottom.
     *
     * @param array  $sections Array of ['title'=>..., 'headers'=>[...], 'rows'=>[[...],...]]
     * @param array  $meta     Metadata
     * @param string $filename Output filename
     */
    function nba_export_excel(array $sections, array $meta, string $filename): void
    {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $font     = IQAC_FONT_BODY;
        $hdrFont  = IQAC_FONT_HEADER;
        $dept     = htmlspecialchars($meta['department'] ?? '');
        $acadYear = htmlspecialchars($meta['academic_year'] ?? '');
        $semester = htmlspecialchars($meta['semester'] ?? '');
        $batch    = htmlspecialchars($meta['batch'] ?? '');
        $genBy    = htmlspecialchars($meta['generated_by'] ?? '');
        $genAt    = htmlspecialchars($meta['generated_at'] ?? date('Y-m-d H:i:s'));
        $reportId = htmlspecialchars($meta['report_id'] ?? '');

        echo '<?xml version="1.0"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'
            . ' xmlns:o="urn:schemas-microsoft-com:office:office"'
            . ' xmlns:x="urn:schemas-microsoft-com:office:excel"'
            . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';

        // ── Styles ──
        echo '<Styles>
    <Style ss:ID="Default" ss:Name="Normal">
        <Font ss:Name="' . $font . '" ss:Size="11"/>
    </Style>
    <Style ss:ID="Title">
        <Font ss:Bold="1" ss:Size="14" ss:Name="' . $hdrFont . '"/>
        <Alignment ss:Horizontal="Center"/>
    </Style>
    <Style ss:ID="SubTitle">
        <Font ss:Bold="1" ss:Size="12" ss:Name="' . $hdrFont . '"/>
        <Alignment ss:Horizontal="Center"/>
    </Style>
    <Style ss:ID="SectionHead">
        <Font ss:Bold="1" ss:Size="11" ss:Name="' . $hdrFont . '"/>
        <Alignment ss:Horizontal="Left"/>
    </Style>
    <Style ss:ID="TableTitle">
        <Font ss:Bold="1" ss:Size="11" ss:Name="' . $hdrFont . '" ss:Underline="Single"/>
        <Alignment ss:Horizontal="Left"/>
    </Style>
    <Style ss:ID="Header">
        <Font ss:Bold="1" ss:Size="9" ss:Name="' . $hdrFont . '"/>
        <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
        </Borders>
    </Style>
    <Style ss:ID="Cell">
        <Font ss:Size="9" ss:Name="' . $font . '"/>
        <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
        </Borders>
    </Style>
    <Style ss:ID="MetaKey">
        <Font ss:Bold="1" ss:Size="9" ss:Name="' . $hdrFont . '"/>
        <Alignment ss:Horizontal="Left"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
        </Borders>
    </Style>
    <Style ss:ID="MetaVal">
        <Font ss:Size="10" ss:Name="' . $font . '"/>
        <Alignment ss:Horizontal="Left"/>
        <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
        </Borders>
    </Style>
</Styles>';

        // ── One worksheet per table section ──
        $lastLetter = '';

        foreach ($sections as $tIdx => $tInfo) {
            $title    = $tInfo['title'] ?? '';
            $headers  = $tInfo['headers'] ?? [];
            $rows     = $tInfo['rows'] ?? [];
            $tableNum = iqac_parse_table_index($title);

            // Parse section letter
            $currentLetter = '';
            $cleanTitle    = $title;
            if (preg_match('/^([A-E])\.\s*(.*)/i', $title, $matches)) {
                $currentLetter = strtoupper($matches[1]);
                $cleanTitle    = $matches[2];
            } else {
                $currentLetter = iqac_table_section_letter($tableNum);
            }

            // Sheet name (max 31 chars, no special chars)
            $sheetTitle = preg_replace('/[\[\]:*?\/\\\\]+/', ' ', $title) ?: ('Table ' . $tableNum);
            $sheetTitle = trim(preg_replace('/\s+/', ' ', $sheetTitle));
            $sheetTitle = mb_substr($sheetTitle, 0, 28) . '-' . $tableNum;

            $numCols     = count($headers);
            $mergeAcross = max(7, $numCols - 1);

            echo '<Worksheet ss:Name="' . htmlspecialchars($sheetTitle) . '"><Table ss:DefaultColumnWidth="95">';

            // Column width elements from template twips (convert to points: 1 twip = 1/20 pt)
            $colTwips = IQAC_TABLE_COLS_TWIPS[$tableNum] ?? [];
            if (!empty($colTwips)) {
                foreach ($colTwips as $tw) {
                    $widthPt = round($tw / 20, 1);
                    echo '<Column ss:Width="' . $widthPt . '"/>';
                }
                // If headers have more columns than template, add default-width columns
                for ($i = count($colTwips); $i < $numCols; $i++) {
                    echo '<Column ss:Width="95"/>';
                }
            }

            // ── Header block ──
            echo '<Row><Cell ss:MergeAcross="' . $mergeAcross . '" ss:StyleID="Title"><Data ss:Type="String">PSG Polytechnic College</Data></Cell></Row>';
            echo '<Row><Cell ss:MergeAcross="' . $mergeAcross . '" ss:StyleID="SubTitle"><Data ss:Type="String">Internal Quality Assurance Cell (IQAC) Formats and Procedures</Data></Cell></Row>';
            echo '<Row><Cell ss:MergeAcross="' . $mergeAcross . '" ss:StyleID="SubTitle"><Data ss:Type="String">Department of ' . $dept . '</Data></Cell></Row>';

            // Section heading row (A, B, C, D, E)
            if ($currentLetter !== '') {
                $sectionName = IQAC_SECTION_HEADINGS[$currentLetter] ?? '';
                echo '<Row><Cell ss:MergeAcross="' . $mergeAcross . '" ss:StyleID="SectionHead"><Data ss:Type="String">' . htmlspecialchars($currentLetter . '. ' . $sectionName) . '</Data></Cell></Row>';
            }

            // Table title row
            $tableTitleText = IQAC_TABLE_TITLES[$tableNum] ?? $cleanTitle;
            echo '<Row><Cell ss:MergeAcross="' . $mergeAcross . '" ss:StyleID="TableTitle"><Data ss:Type="String">' . htmlspecialchars($tableTitleText) . '</Data></Cell></Row>';

            // Metadata rows
            echo '<Row>'
                . '<Cell ss:StyleID="MetaKey"><Data ss:Type="String">Academic Year</Data></Cell>'
                . '<Cell ss:StyleID="MetaVal"><Data ss:Type="String">' . $acadYear . '</Data></Cell>'
                . '<Cell ss:StyleID="MetaKey"><Data ss:Type="String">Semester</Data></Cell>'
                . '<Cell ss:StyleID="MetaVal"><Data ss:Type="String">' . $semester . '</Data></Cell>'
                . '<Cell ss:StyleID="MetaKey"><Data ss:Type="String">Batch</Data></Cell>'
                . '<Cell ss:StyleID="MetaVal"><Data ss:Type="String">' . $batch . '</Data></Cell>'
                . '</Row>';
            echo '<Row>'
                . '<Cell ss:StyleID="MetaKey"><Data ss:Type="String">Prepared By</Data></Cell>'
                . '<Cell ss:StyleID="MetaVal"><Data ss:Type="String">' . $genBy . '</Data></Cell>'
                . '<Cell ss:StyleID="MetaKey"><Data ss:Type="String">Generated Date</Data></Cell>'
                . '<Cell ss:StyleID="MetaVal"><Data ss:Type="String">' . $genAt . '</Data></Cell>'
                . '<Cell ss:StyleID="MetaKey"><Data ss:Type="String">Report ID</Data></Cell>'
                . '<Cell ss:StyleID="MetaVal"><Data ss:Type="String">' . $reportId . '</Data></Cell>'
                . '</Row>';
            echo '<Row/>';

            // ── Column headers ──
            echo '<Row>';
            foreach ($headers as $header) {
                echo '<Cell ss:StyleID="Header"><Data ss:Type="String">' . htmlspecialchars((string)$header) . '</Data></Cell>';
            }
            echo '</Row>';

            // ── Data rows ──
            foreach ($rows as $row) {
                echo '<Row>';
                foreach ($headers as $i => $_) {
                    $val = isset($row[$i]) ? (string)$row[$i] : '';
                    if ($val === '0' || $val === '0.00%' || strtolower($val) === 'no data' || strtolower($val) === 'placeholder') {
                        $val = '';
                    }
                    $type = is_numeric($val) && $val !== '' ? 'Number' : 'String';
                    echo '<Cell ss:StyleID="Cell"><Data ss:Type="' . $type . '">' . htmlspecialchars($val) . '</Data></Cell>';
                }
                echo '</Row>';
            }

            // ── Signature row ──
            echo '<Row/>';
            echo '<Row>';
            foreach (IQAC_SIGNATORIES as $roleName) {
                echo '<Cell ss:StyleID="Header"><Data ss:Type="String">' . htmlspecialchars($roleName) . '</Data></Cell>';
            }
            echo '<Cell ss:StyleID="Header"><Data ss:Type="String">Department Seal</Data></Cell>';
            echo '<Cell ss:StyleID="Header"><Data ss:Type="String">College Seal</Data></Cell>';
            echo '</Row>';

            // ── Page setup (Portrait A4) ──
            echo '</Table>';
            echo '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">'
                . '<PageSetup>'
                . '<Layout x:Orientation="Portrait"/>'
                . '<Header x:Margin="0.3"/>'
                . '<Footer x:Margin="0.3"/>'
                . '<PageMargins x:Bottom="1" x:Left="1" x:Right="1" x:Top="1"/>'
                . '</PageSetup>'
                . '<FitToPage/>'
                . '<Print>'
                . '<FitWidth>1</FitWidth>'
                . '<FitHeight>0</FitHeight>'
                . '</Print>'
                . '</WorksheetOptions>';
            echo '</Worksheet>';
        }

        echo '</Workbook>';
        exit;
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// 5. CSV EXPORT — Simple tabular format (unchanged logic)
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('nba_export_csv')) {
    /**
     * Generates a CSV file with metadata header and all table data.
     *
     * @param array  $sections Array of ['title'=>..., 'headers'=>[...], 'rows'=>[[...],...]]
     * @param array  $meta     Metadata
     * @param string $filename Output filename
     */
    function nba_export_csv(array $sections, array $meta, string $filename): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');

        fputcsv($out, ['PSG Polytechnic College', strtoupper($meta['report_type'] ?? 'NBA') . ' NBA Report', $meta['generated_at'] ?? date('Y-m-d H:i:s')]);
        fputcsv($out, ['Department', $meta['department'] ?? '', 'Academic Year', $meta['academic_year'] ?? '', 'Semester', $meta['semester'] ?? '', 'Batch', $meta['batch'] ?? '']);
        fputcsv($out, ['Scope', $meta['scope'] ?? '', 'Export Authority', $meta['export_authority'] ?? '']);
        fputcsv($out, []);

        foreach ($sections as $tIdx => $tInfo) {
            $title   = $tInfo['title'] ?? '';
            $headers = $tInfo['headers'] ?? [];
            $rows    = $tInfo['rows'] ?? [];

            fputcsv($out, [$title]);
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fputcsv($out, []);
        }

        fclose($out);
        exit;
    }
}
