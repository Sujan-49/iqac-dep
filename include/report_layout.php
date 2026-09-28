<?php
declare(strict_types=1);

require_once __DIR__ . '/navigation.php';
require_once __DIR__ . '/report_ui.php';

/**
 * Start the ERP report layout. Call at the top of the HTML section.
 * Renders: DOCTYPE, <html>, <head> with CSS links, <body>, erp-layout, sidebar, main, topbar.
 *
 * @param string $title     Page <title>
 * @param array  $user      Current user array from iqac_require_login()
 * @param string $activePage Filename for sidebar active state (e.g. 'view_std.php')
 * @param array  $breadcrumb Array of ['label'=>'Dashboard','url'=>'academic_erp.php'], last item has no url
 */
function erp_report_layout_start(string $title, array $user, string $activePage = '', array $breadcrumb = []): void
{
    $pageTitle = htmlspecialchars($title);
    $userName = htmlspecialchars($user['username'] ?? 'User');
    $userDept = htmlspecialchars($user['department'] ?? 'Department');
    
    // Attempt to get dashboard link, default to academic_erp.php if not available
    $dashboardUrl = function_exists('iqac_role_home') ? iqac_role_home($user['role'] ?? '') : 'academic_erp.php';

    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - ERP Reports</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/erp.css">
    <link rel="stylesheet" href="assets/report.css">
</head>
<body>
    <div class="erp-layout report-shell">
        <?php 
        if (function_exists('iqac_render_sidebar')) {
            iqac_render_sidebar($user, $activePage);
        }
        ?>
        <main class="erp-main">
            <header class="erp-topbar">
                <div class="topbar-left">
                    <h1><?= $pageTitle ?></h1>
                    <span class="user-info"><?= $userName ?> &middot; <?= $userDept ?></span>
                </div>
                <div class="toolbar-actions">
                    <a class="secondary-btn" href="<?= htmlspecialchars($dashboardUrl) ?>">Dashboard</a>
                </div>
            </header>
            
            <section class="erp-content">
                <?php if (!empty($breadcrumb)): ?>
                    <nav class="erp-breadcrumb" aria-label="breadcrumb">
                        <ol>
                            <?php foreach ($breadcrumb as $idx => $crumb): ?>
                                <?php if ($idx === count($breadcrumb) - 1 || empty($crumb['url'])): ?>
                                    <li class="active" aria-current="page"><?= htmlspecialchars($crumb['label']) ?></li>
                                <?php else: ?>
                                    <li><a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['label']) ?></a></li>
                                    <li class="separator">/</li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                <?php endif; ?>
    <?php
}

/**
 * End the ERP report layout. Call after all content.
 * Renders: closing tags for erp-content, erp-main, erp-layout, report.js script, </body>, </html>
 */
function erp_report_layout_end(): void
{
    ?>
            </section> <!-- .erp-content -->
        </main> <!-- .erp-main -->
    </div> <!-- .erp-layout -->
    
    <!-- Report JS Framework -->
    <script src="assets/report.js"></script>
</body>
</html>
    <?php
}
