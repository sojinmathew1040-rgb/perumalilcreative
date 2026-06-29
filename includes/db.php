<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'perumalil_creative');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Self-healing: check if new settings keys are present, if not, execute setup
    try {
        $pdo->query("SELECT 1 FROM collaborators LIMIT 1");
        $pdo->query("SELECT 1 FROM inquiries LIMIT 1");
        
        $check = $pdo->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = ?");
        $check->execute(['map_latitude']);
        if ($check->fetchColumn() == 0) {
            require_once dirname(__DIR__) . '/setup_db_logic.php';
        }
    } catch (PDOException $ex) {
        require_once dirname(__DIR__) . '/setup_db_logic.php';
    }
} catch (PDOException $e) {
    // If the database doesn't exist, we will try to connect to the host and create it automatically
    try {
        $temp_pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
        $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        // Include setup script to populate database structure and seed data
        require_once dirname(__DIR__) . '/setup_db_logic.php';
    } catch (PDOException $ex) {
        die("Database connection failed. Please ensure MySQL is running in XAMPP. Error: " . $ex->getMessage());
    }
}
