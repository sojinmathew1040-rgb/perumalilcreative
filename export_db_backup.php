<?php
// export_db_backup.php - Database Backup Generator for Perumalil Creative
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/db.php';

if (!isset($pdo)) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit;
}

date_default_timezone_set('Asia/Kolkata');

try {
    $now = date('Y-m-d H:i:s');
    $sqlDump = "-- ========================================================\n";
    $sqlDump .= "-- Perumalil Creative - Database Backup Dump\n";
    $sqlDump .= "-- Backup Date & Time: " . $now . " (IST)\n";
    $sqlDump .= "-- Database: `" . $dbname . "`\n";
    $sqlDump .= "-- Host: " . $servername . "\n";
    $sqlDump .= "-- ========================================================\n\n";
    
    $sqlDump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sqlDump .= "START TRANSACTION;\n";
    $sqlDump .= "SET time_zone = \"+00:00\";\n\n";
    $sqlDump .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
    $sqlDump .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
    $sqlDump .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
    $sqlDump .= "/*!40101 SET NAMES utf8mb4 */;\n\n";
    
    $sqlDump .= "CREATE DATABASE IF NOT EXISTS `" . $dbname . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
    $sqlDump .= "USE `" . $dbname . "`;\n\n";

    // Retrieve all tables in the database
    $tablesStmt = $pdo->query("SHOW FULL TABLES WHERE Table_Type = 'BASE TABLE'");
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

    $stats = [];

    foreach ($tables as $table) {
        $sqlDump .= "-- --------------------------------------------------------\n";
        $sqlDump .= "-- Table structure for table `" . $table . "`\n";
        $sqlDump .= "-- --------------------------------------------------------\n\n";
        
        $sqlDump .= "DROP TABLE IF EXISTS `" . $table . "`;\n";
        
        $createStmt = $pdo->query("SHOW CREATE TABLE `" . $table . "`");
        $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
        $createTableSql = $createRow['Create Table'] ?? '';
        $sqlDump .= $createTableSql . ";\n\n";

        // Table data
        $dataStmt = $pdo->query("SELECT * FROM `" . $table . "`");
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
        $rowCount = count($rows);
        $stats[$table] = $rowCount;

        if ($rowCount > 0) {
            $sqlDump .= "-- Dumping data for table `" . $table . "` (" . $rowCount . " rows)\n";
            $columns = array_keys($rows[0]);
            $colList = '`' . implode('`, `', $columns) . '`';
            
            $chunks = array_chunk($rows, 50);
            foreach ($chunks as $chunk) {
                $valuesArr = [];
                foreach ($chunk as $row) {
                    $escapedVals = [];
                    foreach ($row as $val) {
                        if (is_null($val)) {
                            $escapedVals[] = 'NULL';
                        } else {
                            $escapedVals[] = $pdo->quote($val);
                        }
                    }
                    $valuesArr[] = "(" . implode(', ', $escapedVals) . ")";
                }
                $sqlDump .= "INSERT INTO `" . $table . "` (" . $colList . ") VALUES\n" . implode(",\n", $valuesArr) . ";\n";
            }
            $sqlDump .= "\n";
        }
    }

    $sqlDump .= "COMMIT;\n\n";
    $sqlDump .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
    $sqlDump .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
    $sqlDump .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";

    // Write directly to db/perumalil_creative.sql
    $targetFile = __DIR__ . '/db/perumalil_creative.sql';
    file_put_contents($targetFile, $sqlDump);

    echo json_encode([
        'success' => true,
        'message' => 'Database backup generated successfully!',
        'file' => $targetFile,
        'file_size' => strlen($sqlDump),
        'backup_time' => $now,
        'tables_count' => count($tables),
        'table_stats' => $stats
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
