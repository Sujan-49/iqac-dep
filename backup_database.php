<?php
/**
 * PSG PTC ERP — Database Backup Script (Phase 2)
 */

declare(strict_types=1);

require_once __DIR__ . '/dp_connection.php';

$logsDir = __DIR__ . '/logs';
$dbDir   = __DIR__ . '/database';
$prodDir = __DIR__ . '/production';
$prodLogsDir = __DIR__ . '/production/logs';

foreach ([$logsDir, $dbDir, $prodDir, $prodLogsDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

$logFile = $logsDir . '/backup.log';

function writeLog(string $msg, string $logFile): void {
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] $msg\n";
    file_put_contents($logFile, $line, FILE_APPEND);
    echo $line;
}

writeLog("Starting database backup process...", $logFile);

if ($conn->connect_error) {
    writeLog("CRITICAL ERROR: Connection failed: " . $conn->connect_error, $logFile);
    exit(1);
}

$timestamp = date('Ymd_His');
$backupFileName = "backup_iqac_{$timestamp}.sql";
$backupFilePath = $dbDir . '/' . $backupFileName;

writeLog("Target database: {$db_name}", $logFile);
writeLog("Backup output path: {$backupFilePath}", $logFile);

// Fetch all tables
$tablesResult = $conn->query("SHOW TABLES");
if (!$tablesResult) {
    writeLog("CRITICAL ERROR: Failed to list database tables: " . $conn->error, $logFile);
    exit(1);
}

$tables = [];
while ($row = $tablesResult->fetch_row()) {
    $tables[] = $row[0];
}

writeLog("Found " . count($tables) . " tables in database '{$db_name}'", $logFile);

$sqlDump = "-- ==================================================\n";
$sqlDump .= "-- PSG PTC ERP Database Backup\n";
$sqlDump .= "-- Database: {$db_name}\n";
$sqlDump .= "-- Timestamp: " . date('Y-m-d H:i:s') . "\n";
$sqlDump .= "-- ==================================================\n\n";
$sqlDump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tables as $table) {
    // Structure
    $createResult = $conn->query("SHOW CREATE TABLE `$table`");
    if ($createResult && $createRow = $createResult->fetch_row()) {
        $sqlDump .= "-- Table structure for `$table` --\n";
        $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
        $sqlDump .= $createRow[1] . ";\n\n";
    }

    // Data
    $dataResult = $conn->query("SELECT * FROM `$table`");
    if ($dataResult && $dataResult->num_rows > 0) {
        $sqlDump .= "-- Data for `$table` --\n";
        while ($dataRow = $dataResult->fetch_assoc()) {
            $keys = array_keys($dataRow);
            $values = array_values($dataRow);
            
            $escapedKeys = array_map(fn($k) => "`" . $conn->real_escape_string((string)$k) . "`", $keys);
            $escapedValues = array_map(function($v) use ($conn) {
                if ($v === null) return 'NULL';
                return "'" . $conn->real_escape_string((string)$v) . "'";
            }, $values);

            $sqlDump .= "INSERT INTO `$table` (" . implode(', ', $escapedKeys) . ") VALUES (" . implode(', ', $escapedValues) . ");\n";
        }
        $sqlDump .= "\n";
    }
}

$sqlDump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

$writtenBytes = file_put_contents($backupFilePath, $sqlDump);

if ($writtenBytes === false || $writtenBytes === 0) {
    writeLog("CRITICAL ERROR: Failed to write backup file or backup is empty!", $logFile);
    exit(1);
}

writeLog("Backup file written successfully. Size: " . number_format($writtenBytes) . " bytes.", $logFile);

// Verify Backup
if (file_exists($backupFilePath) && filesize($backupFilePath) > 0) {
    // Copy to production folder as well
    $prodBackupPath = $prodDir . '/' . $backupFileName;
    copy($backupFilePath, $prodBackupPath);
    copy($logFile, $prodLogsDir . '/backup.log');

    writeLog("BACKUP VERIFICATION PASS: Backup file exists and is valid ({$backupFileName})", $logFile);
    echo "\n=== BACKUP COMPLETED SUCCESSFULLY ===\n";
} else {
    writeLog("CRITICAL ERROR: Backup verification failed!", $logFile);
    exit(1);
}
