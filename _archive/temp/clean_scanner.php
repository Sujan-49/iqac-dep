<?php
// Scan the project for unused files and references before clean up

$root = 'C:/xampp/htdocs/Iqac';
$archiveDir = "$root/_archive";

// Create required archive subfolders
$subdirs = [
    'old_php', 'old_sql', 'backups', 'test_files', 'debug',
    'old_assets', 'temp', 'unused', 'screenshots', 'docs', 'exports'
];
foreach ($subdirs as $s) {
    @mkdir("$archiveDir/$s", 0777, true);
}

// 1. Gather all files in the root
$rootFiles = [];
foreach (scandir($root) as $item) {
    if ($item === '.' || $item === '..') continue;
    $fullPath = "$root/$item";
    if (is_file($fullPath)) {
        $rootFiles[] = $item;
    }
}

// 2. Identify all search target files (exclude folders, archive, git, agents, vendor)
// We will scan these files for references to root files
function getScanFiles($dir, &$results = []) {
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        if ($item === '_archive' || $item === 'archive' || $item === '.git' || $item === '.agents' || $item === 'vendor' || $item === 'scratch') continue;
        
        $path = "$dir/$item";
        if (is_dir($path)) {
            getScanFiles($path, $results);
        } else {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if (in_array($ext, ['php', 'html', 'js', 'css', 'json'])) {
                $results[] = $path;
            }
        }
    }
    return $results;
}

$scanFiles = getScanFiles($root);

echo "Found " . count($rootFiles) . " files in root.\n";
echo "Found " . count($scanFiles) . " active project files to scan for references.\n\n";

$referenced = [];
$unused = [];

// For each root file, scan all active files to see if its name is referenced
foreach ($rootFiles as $rf) {
    // Exclude basic entries that we know we must keep
    $alwaysKeep = ['index.php', 'login.php', 'logout.php', 'composer.json', 'composer.lock', 'composer.phar', 'README.md'];
    if (in_array($rf, $alwaysKeep)) {
        $referenced[$rf] = ['always_keep'];
        continue;
    }
    
    $refCount = 0;
    $refSources = [];
    
    foreach ($scanFiles as $sf) {
        // Skip scanning the file itself
        if (basename($sf) === $rf) continue;
        
        $content = file_get_contents($sf);
        if ($content === false) continue;
        
        // Match exact filename (e.g. "student_dashboard.php", 'student_dashboard.php', etc.)
        if (stripos($content, $rf) !== false) {
            $refCount++;
            $refSources[] = basename($sf);
        }
    }
    
    if ($refCount > 0) {
        $referenced[$rf] = $refSources;
    } else {
        $unused[] = $rf;
    }
}

echo "--- REFERENCED FILES (" . count($referenced) . ") ---\n";
foreach ($referenced as $file => $sources) {
    echo "$file is referenced by: " . implode(', ', array_slice($sources, 0, 5)) . (count($sources) > 5 ? " ... and " . (count($sources) - 5) . " more" : "") . "\n";
}

echo "\n--- UNREFERENCED FILES (" . count($unused) . ") ---\n";
foreach ($unused as $file) {
    echo "$file\n";
}
