<?php
declare(strict_types=1);

/**
 * Render the report toolbar.
 * @param array $config Keys:
 *   'back_url'   => string (default: 'academic_erp.php')
 *   'back_label' => string (default: 'Back to Dashboard')
 *   'page_name'  => string — used to build export URLs
 *   'exports'    => array of strings: 'excel','csv','word','pdf','print' (default: all)
 *   'show_filter'  => bool (default: true)
 *   'show_refresh' => bool (default: true)
 *   'show_fullscreen' => bool (default: true)
 *   'target_table' => string (default: 'main-report-table')
 */
function erp_report_toolbar(array $config = []): void
{
    $backUrl = htmlspecialchars($config['back_url'] ?? 'academic_erp.php');
    $backLabel = htmlspecialchars($config['back_label'] ?? 'Back to Dashboard');
    $pageName = htmlspecialchars($config['page_name'] ?? 'report');
    $targetTable = htmlspecialchars($config['target_table'] ?? 'main-report-table');
    $exports = $config['exports'] ?? ['excel', 'csv', 'word', 'print'];
    
    $showFilter = $config['show_filter'] ?? true;
    $showRefresh = $config['show_refresh'] ?? true;
    $showFullscreen = $config['show_fullscreen'] ?? true;

    echo '<div class="report-toolbar">';
    echo '  <div class="toolbar-left">';
    echo '    <a href="' . $backUrl . '" class="btn-back"><i class="fa-solid fa-arrow-left"></i> ' . $backLabel . '</a>';
    
    if ($showFilter) {
        echo '    <button type="button" class="btn-toolbar toggle-filter-btn" onclick="window.ReportUI.toggleFilter()" aria-expanded="false"><i class="fa-solid fa-filter"></i> Filter</button>';
    }
    if ($showRefresh) {
        echo '    <button type="button" class="btn-toolbar btn-refresh" onclick="window.location.reload()"><i class="fa-solid fa-rotate-right"></i> Refresh</button>';
    }
    echo '  </div>';

    echo '  <div class="toolbar-right">';
    if (in_array('excel', $exports)) {
        echo '    <button type="button" class="btn-toolbar btn-export" onclick="window.ReportUI.exportExcel(\'' . $targetTable . '\', \'' . $pageName . '.xls\')"><i class="fa-solid fa-file-excel"></i> Excel</button>';
    }
    if (in_array('csv', $exports)) {
        echo '    <button type="button" class="btn-toolbar btn-export" onclick="window.ReportUI.exportCSV(\'' . $targetTable . '\', \'' . $pageName . '.csv\')"><i class="fa-solid fa-file-csv"></i> CSV</button>';
    }
    if (in_array('word', $exports)) {
        echo '    <button type="button" class="btn-toolbar btn-export" onclick="window.ReportUI.exportWord(\'' . $targetTable . '\', \'' . $pageName . '.doc\')"><i class="fa-solid fa-file-word"></i> Word</button>';
    }
    if (in_array('print', $exports)) {
        echo '    <button type="button" class="btn-toolbar btn-print" onclick="window.ReportUI.reportPrint()"><i class="fa-solid fa-print"></i> Print</button>';
    }
    if ($showFullscreen) {
        echo '    <button type="button" class="btn-toolbar btn-fullscreen" onclick="window.ReportUI.toggleFullscreen()"><i class="fa-solid fa-expand"></i> Fullscreen</button>';
    }
    echo '  </div>';
    echo '</div>';
}

/**
 * Render the collapsible filter panel.
 * @param array $filters Each filter is an assoc array:
 *   ['label'=>'Department', 'name'=>'department', 'type'=>'select', 'options'=>['IT','CS',...], 'value'=>$_GET['department']??'']
 * @param string $action Form action URL (default: current page)
 * @param string $resetUrl URL for reset button (default: current page without query)
 */
function erp_report_filters(array $filters, string $action = '', string $resetUrl = ''): void
{
    if (empty($action)) $action = htmlspecialchars($_SERVER['PHP_SELF']);
    if (empty($resetUrl)) $resetUrl = htmlspecialchars(strtok($_SERVER["REQUEST_URI"], '?'));

    echo '<div class="report-filter-panel">';
    echo '  <form action="' . $action . '" method="GET" class="filter-form">';
    echo '    <div class="filter-grid">';
    
    foreach ($filters as $f) {
        $name = htmlspecialchars($f['name']);
        $label = htmlspecialchars($f['label']);
        $value = htmlspecialchars((string)($f['value'] ?? ''));
        $type = $f['type'] ?? 'text';
        
        echo '<div class="filter-group">';
        echo '  <label for="filter_' . $name . '">' . $label . '</label>';
        
        if ($type === 'select') {
            echo '  <select name="' . $name . '" id="filter_' . $name . '" class="filter-input">';
            echo '    <option value="">All</option>';
            if (isset($f['options']) && is_array($f['options'])) {
                foreach ($f['options'] as $optVal => $optLabel) {
                    if (is_int($optVal)) {
                        $optVal = $optLabel; // If indexed array
                    }
                    $selected = ((string)$optVal === $value) ? 'selected' : '';
                    echo '    <option value="' . htmlspecialchars((string)$optVal) . '" ' . $selected . '>' . htmlspecialchars((string)$optLabel) . '</option>';
                }
            }
            echo '  </select>';
        } else {
            $placeholder = htmlspecialchars($f['placeholder'] ?? '');
            echo '  <input type="' . htmlspecialchars($type) . '" name="' . $name . '" id="filter_' . $name . '" value="' . $value . '" placeholder="' . $placeholder . '" class="filter-input">';
        }
        echo '</div>';
    }
    
    echo '    </div>';
    echo '    <div class="filter-actions">';
    echo '      <button type="submit" class="btn-primary"><i class="fa-solid fa-check"></i> Apply Filters</button>';
    echo '      <a href="' . $resetUrl . '" class="btn-secondary"><i class="fa-solid fa-xmark"></i> Clear</a>';
    echo '    </div>';
    echo '  </form>';
    echo '</div>';
}

/**
 * Render summary stat cards.
 * @param array $cards Each card: ['label'=>'Total Students', 'value'=>150, 'icon'=>'fa-users', 'tone'=>''] 
 */
function erp_report_cards(array $cards): void
{
    if (empty($cards)) return;
    
    echo '<div class="report-cards-grid">';
    foreach ($cards as $card) {
        $tone = $card['tone'] ?? '';
        $toneClass = $tone ? 'card-tone-' . htmlspecialchars($tone) : '';
        $icon = htmlspecialchars($card['icon'] ?? 'fa-circle-info');
        $label = htmlspecialchars($card['label'] ?? '');
        $value = htmlspecialchars((string)($card['value'] ?? '0'));
        
        echo '  <div class="report-card ' . $toneClass . '">';
        echo '    <div class="card-icon"><i class="fa-solid ' . $icon . '"></i></div>';
        echo '    <div class="card-content">';
        echo '      <div class="card-value">' . $value . '</div>';
        echo '      <div class="card-label">' . $label . '</div>';
        echo '    </div>';
        echo '  </div>';
    }
    echo '</div>';
}

/**
 * Render the official PSG report header with logo.
 * @param string $reportTitle e.g. 'NBA ACCREDITATION REPORT - STUDENT DETAILS'
 * @param string $department e.g. 'INFORMATION TECHNOLOGY' or 'ALL'
 * @param array $meta Associative: ['date'=>date('Y-m-d'), 'by'=>$user['username'], 'academic_year'=>'2024-25']
 */
function erp_report_header(string $reportTitle, string $department, array $meta = []): void
{
    echo '<div class="report-header">';
    echo '  <div class="report-header-inner">';
    echo '    <div class="header-logo">';
    echo '      <div class="psg-logo-circle">PSG</div>'; // Placeholder for SVG or img
    echo '    </div>';
    echo '    <div class="header-title-area">';
    echo '      <h2 class="college-name">PSG POLYTECHNIC COLLEGE</h2>';
    echo '      <h3 class="department-name">DEPARTMENT OF ' . htmlspecialchars(strtoupper($department)) . '</h3>';
    echo '      <h1 class="report-title">' . htmlspecialchars(strtoupper($reportTitle)) . '</h1>';
    echo '    </div>';
    echo '    <div class="header-meta">';
    foreach ($meta as $k => $v) {
        echo '      <div class="meta-item"><span class="meta-key">' . htmlspecialchars((string)$k) . ':</span> <span class="meta-value">' . htmlspecialchars((string)$v) . '</span></div>';
    }
    echo '    </div>';
    echo '  </div>';
    echo '</div>';
}

/**
 * Render the metadata bar (academic year, semester, batch, section, etc).
 * @param array $fields Associative: ['Academic Year'=>'2024-25', 'Semester'=>'All', ...]
 */
function erp_report_meta_bar(array $fields): void
{
    if (empty($fields)) return;
    
    echo '<div class="report-meta-bar">';
    foreach ($fields as $k => $v) {
        echo '  <div class="meta-badge">';
        echo '    <span class="badge-label">' . htmlspecialchars((string)$k) . '</span>';
        echo '    <span class="badge-value">' . htmlspecialchars((string)$v) . '</span>';
        echo '  </div>';
    }
    echo '</div>';
}

/**
 * Render the table search box.
 * @param string $placeholder Default 'Search records...'
 * @param string $targetTableId Default 'main-report-table'
 */
function erp_report_search(string $placeholder = 'Search records...', string $targetTableId = 'main-report-table'): void
{
    echo '<div class="report-search-container">';
    echo '  <i class="fa-solid fa-magnifying-glass search-icon"></i>';
    echo '  <input type="text" id="report-search" class="report-search-input" placeholder="' . htmlspecialchars($placeholder) . '" data-table-target="' . htmlspecialchars($targetTableId) . '">';
    echo '</div>';
}

/**
 * Render the signature block.
 * @param array $roles Default: ['Prepared By', 'Tutor', 'Faculty In-Charge', 'HOD', 'Principal']
 */
function erp_report_signatures(array $roles = []): void
{
    if (empty($roles)) {
        $roles = ['Prepared By', 'Tutor', 'Faculty In-Charge', 'HOD', 'Principal'];
    }
    
    echo '<div class="report-signatures">';
    foreach ($roles as $role) {
        echo '  <div class="signature-box">';
        echo '    <div class="signature-line"></div>';
        echo '    <div class="signature-role">' . htmlspecialchars($role) . '</div>';
        echo '  </div>';
    }
    echo '</div>';
}

/**
 * Render the print footer with seal areas and timestamp.
 */
function erp_report_footer(): void
{
    echo '<div class="report-footer-print-only">';
    echo '  <div class="print-timestamp">Generated on: ' . date('Y-m-d H:i:s') . '</div>';
    echo '  <div class="print-page-number">Page <span class="pageNumber"></span> of <span class="totalPages"></span></div>';
    echo '</div>';
}

/**
 * Render the pagination container (populated by report.js).
 * @param string $targetTableId Default 'main-report-table'
 * @param int $rowsPerPage Default 20
 */
function erp_report_pagination(string $targetTableId = 'main-report-table', int $rowsPerPage = 20): void
{
    echo '<div class="report-pagination" data-table-target="' . htmlspecialchars($targetTableId) . '" data-rows="' . $rowsPerPage . '"></div>';
}
