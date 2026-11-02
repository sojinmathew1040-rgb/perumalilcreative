<?php
// Database configuration
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "perumalil_creative";

// Backward compatibility constants
if (!defined('DB_HOST')) define('DB_HOST', $servername);
if (!defined('DB_USER')) define('DB_USER', $username);
if (!defined('DB_PASS')) define('DB_PASS', $password);
if (!defined('DB_NAME')) define('DB_NAME', $dbname);

// Helper function to import SQL file with multi-query support
function import_sql_dump($pdo, $sqlFilePath) {
    if (!file_exists($sqlFilePath)) {
        return false;
    }
    try {
        $sql = file_get_contents($sqlFilePath);
        if (!empty($sql)) {
            $pdo->exec($sql);
            return true;
        }
    } catch (PDOException $e) {
        error_log("Failed to import SQL dump: " . $e->getMessage());
    }
    return false;
}

try {
    // 1. Ensure the database exists
    try {
        $temp_pdo = new PDO("mysql:host=" . $servername . ";charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `" . $dbname . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        unset($temp_pdo);
    } catch (PDOException $eDb) {
        // If host connection fails, will be caught below
    }

    // 2. Establish PDO connection to the database
    $pdo = new PDO("mysql:host=" . $servername . ";dbname=" . $dbname . ";charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // 3. Establish MySQLi connection for legacy scripts
    $con = new mysqli($servername, $username, $password, $dbname);
    if ($con->connect_error) {
        throw new Exception("MySQLi connection failed: " . $con->connect_error);
    }
    $con->set_charset("utf8mb4");

    // 4. Auto-initialize database tables & data from db/perumalil_creative.sql if not yet present
    $needsInit = false;
    try {
        $check = $pdo->query("SELECT 1 FROM `admin_users` LIMIT 1");
        $check2 = $pdo->query("SELECT 1 FROM `site_settings` LIMIT 1");
    } catch (Exception $eCheck) {
        $needsInit = true;
    }

    if ($needsInit) {
        $sqlDumpFile = dirname(__DIR__) . '/db/perumalil_creative.sql';
        if (file_exists($sqlDumpFile)) {
            import_sql_dump($pdo, $sqlDumpFile);
        } else if (file_exists(dirname(__DIR__) . '/setup_db_logic.php')) {
            require_once dirname(__DIR__) . '/setup_db_logic.php';
        }
    }

    // 5. Self-healing & dynamic schema updates
    try {
        $checkLatitude = $pdo->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = ?");
        $checkLatitude->execute(['map_latitude']);
        if ($checkLatitude->fetchColumn() == 0 && file_exists(dirname(__DIR__) . '/setup_db_logic.php')) {
            require_once dirname(__DIR__) . '/setup_db_logic.php';
        }

        // Ensure portfolio_categories exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS `portfolio_categories` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `slug` VARCHAR(50) NOT NULL UNIQUE,
            `name` VARCHAR(100) NOT NULL,
            `order_index` INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $catCheck = $pdo->query("SELECT COUNT(*) FROM portfolio_categories");
        if ($catCheck->fetchColumn() == 0) {
            $pdo->exec("INSERT IGNORE INTO `portfolio_categories` (`slug`, `name`, `order_index`) VALUES 
                ('web', 'Web Development', 0),
                ('video', 'Video Production', 1),
                ('design', 'Graphic Design', 2)
            ");
        }

        // Ensure is_launching_soon column exists in portfolio_items
        try {
            $pdo->query("SELECT is_launching_soon FROM portfolio_items LIMIT 1");
        } catch (Exception $eCol) {
            try {
                $pdo->exec("ALTER TABLE portfolio_items ADD COLUMN is_launching_soon TINYINT(1) DEFAULT 0");
                $pdo->exec("UPDATE portfolio_items SET is_launching_soon = 1 WHERE tag LIKE '%Launching Soon%' OR title LIKE '%Launching Soon%' OR description LIKE '%Launching Soon%'");
            } catch (Exception $eCol2) {}
        }

        // Ensure default launching_soon_template_image exists in site_settings
        try {
            $checkTpl = $pdo->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = 'launching_soon_template_image'");
            $checkTpl->execute();
            if ($checkTpl->fetchColumn() == 0) {
                $pdo->exec("INSERT INTO site_settings (setting_key, setting_value) VALUES ('launching_soon_template_image', 'assets/images/portfolio_launching_soon.jpg')");
            }
        } catch (Exception $eTpl) {}

        // Ensure default universal typography & color settings exist in site_settings
        try {
            $defaultSiteSettings = [
                'site_font_heading' => 'Syne',
                'site_font_editorial' => 'Outfit',
                'site_font_body' => 'Plus Jakarta Sans',
                'hero_headline_font' => 'Syne',
                'site_color_heading' => '#ffffff',
                'site_color_subheading' => '#38bdf8',
                'site_color_body' => '#a0a0ab',
                'site_color_accent' => '#0066ff',
                'site_color_muted' => '#64748b',
                'hero_color_headline' => '#ffffff',
                'hero_color_glow' => '#38bdf8',
                'hero_color_mission' => '#cbd5e1',
                'site_color_bg' => '#050505',
                'site_color_card' => '#0c0d12',
                'ai_enabled' => '1',
                'ai_bot_name' => 'Nova',
                'ai_greeting_title' => 'Hi! I am Nova 👋',
                'ai_greeting_desc' => 'How can I help you today?'
            ];
            $checkKey = $pdo->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = ?");
            $insKey = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($defaultSiteSettings as $k => $v) {
                $checkKey->execute([$k]);
                if ($checkKey->fetchColumn() == 0) {
                    $insKey->execute([$k, $v]);
                }
            }
        } catch (Exception $eTypoDefaults) {}

    } catch (Exception $e) {
        // Non-fatal configuration error
    }

} catch (Exception $e) {
    die("Database connection failed. Please ensure MySQL is running in XAMPP. Error: " . $e->getMessage());
}

