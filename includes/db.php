<?php
//local server

$servername = "localhost";
$username = "root";
$password ="";
$dbname = "perumalil_creative";


// Create connection
$con = new mysqli($servername, $username, $password, $dbname);
$con->set_charset("utf8");

// Backward compatibility constants
if (!defined('DB_HOST')) define('DB_HOST', $servername);
if (!defined('DB_USER')) define('DB_USER', $username);
if (!defined('DB_PASS')) define('DB_PASS', $password);
if (!defined('DB_NAME')) define('DB_NAME', $dbname);

try {
    $pdo = new PDO("mysql:host=" . $servername . ";dbname=" . $dbname . ";charset=utf8mb4", $username, $password, [
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

        // Self-healing: ensure portfolio_categories exists and Web Development is placed first
        try {
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
            } else {
                // One-time update to place Web first if not already initialized
                $orderInit = $pdo->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = 'portfolio_category_order_init'");
                $orderInit->execute();
                if ($orderInit->fetchColumn() == 0) {
                    $pdo->exec("UPDATE portfolio_categories SET order_index = 0 WHERE slug = 'web'");
                    $pdo->exec("UPDATE portfolio_categories SET order_index = 1 WHERE slug = 'video'");
                    $pdo->exec("UPDATE portfolio_categories SET order_index = 2 WHERE slug = 'design'");
                    $pdo->exec("INSERT INTO site_settings (setting_key, setting_value) VALUES ('portfolio_category_order_init', '1') ON DUPLICATE KEY UPDATE setting_value = '1'");
                }
            }

            // Self-healing: ensure is_launching_soon column exists in portfolio_items
            try {
                $pdo->query("SELECT is_launching_soon FROM portfolio_items LIMIT 1");
            } catch (Exception $eCol) {
                try {
                    $pdo->exec("ALTER TABLE portfolio_items ADD COLUMN is_launching_soon TINYINT(1) DEFAULT 0");
                    $pdo->exec("UPDATE portfolio_items SET is_launching_soon = 1 WHERE tag LIKE '%Launching Soon%' OR title LIKE '%Launching Soon%' OR description LIKE '%Launching Soon%'");
                } catch (Exception $eCol2) {}
            }

            // Self-healing: ensure default launching_soon_template_image exists in site_settings
            try {
                $checkTpl = $pdo->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = 'launching_soon_template_image'");
                $checkTpl->execute();
                if ($checkTpl->fetchColumn() == 0) {
                    $pdo->exec("INSERT INTO site_settings (setting_key, setting_value) VALUES ('launching_soon_template_image', 'assets/images/portfolio_launching_soon.jpg')");
                }
            } catch (Exception $eTpl) {}

            // Self-healing: synchronize portfolio items where is_launching_soon is 0 or uncheck was saved
            try {
                $syncStmt = $pdo->query("SELECT id, tag, title, is_launching_soon, image_path FROM portfolio_items");
                $syncItems = $syncStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($syncItems as $sItem) {
                    if (empty($sItem['is_launching_soon'])) {
                        $cleanedTag = trim(preg_replace('/^Launching Soon\s*[\x{2022}\-\:\•\|]?\s*/iu', '', $sItem['tag']));
                        if (empty($cleanedTag)) $cleanedTag = 'Web Architecture';
                        $cleanedTitle = trim(preg_replace('/^Launching Soon\s*[\x{2022}\-\:\•\|]?\s*/iu', '', $sItem['title']));
                        
                        $cleanedImage = $sItem['image_path'];
                        if (empty($cleanedImage) || strpos($cleanedImage, 'portfolio_launching_soon') !== false || strpos($cleanedImage, 'launching_soon_template') !== false) {
                            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $cleanedTitle));
                            $imgOptions = [
                                'assets/images/portfolio_' . $slug . '.jpg',
                                'assets/images/portfolio_' . $slug . '.png',
                                'assets/images/portfolio_' . $slug . '_com.jpg',
                                'assets/images/portfolio_' . $slug . '_in.jpg'
                            ];
                            foreach ($imgOptions as $opt) {
                                if (file_exists(dirname(__DIR__) . '/' . $opt)) {
                                    $cleanedImage = $opt;
                                    break;
                                }
                            }
                        }

                        if ($cleanedTag !== $sItem['tag'] || $cleanedTitle !== $sItem['title'] || $cleanedImage !== $sItem['image_path']) {
                            $updSync = $pdo->prepare("UPDATE portfolio_items SET tag = ?, title = ?, image_path = ? WHERE id = ?");
                            $updSync->execute([$cleanedTag, $cleanedTitle, $cleanedImage, $sItem['id']]);
                        }
                    }
                }
            } catch (Exception $eSync) {}

            // One-time order normalization across portfolio categories
            try {
                $checkNorm = $pdo->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = 'portfolio_orders_normalized_v1'");
                $checkNorm->execute();
                if ($checkNorm->fetchColumn() == 0) {
                    $catList = $pdo->query("SELECT DISTINCT category FROM portfolio_items")->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($catList as $cSlug) {
                        $pItems = $pdo->prepare("SELECT id FROM portfolio_items WHERE category = ? ORDER BY order_index ASC, id ASC");
                        $pItems->execute([$cSlug]);
                        $ids = $pItems->fetchAll(PDO::FETCH_COLUMN);
                        $u = $pdo->prepare("UPDATE portfolio_items SET order_index = ? WHERE id = ?");
                        foreach ($ids as $i => $itemId) {
                            $u->execute([$i + 1, $itemId]);
                        }
                    }
                    $pdo->exec("INSERT INTO site_settings (setting_key, setting_value) VALUES ('portfolio_orders_normalized_v1', '1') ON DUPLICATE KEY UPDATE setting_value = '1'");
                }
            } catch (Exception $eNorm) {}

            // Self-healing: ensure default universal typography & color settings exist in site_settings
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
        } catch (Exception $e) {}
    } catch (PDOException $ex) {
        require_once dirname(__DIR__) . '/setup_db_logic.php';
    }
} catch (PDOException $e) {
    // If the database doesn't exist, we will try to connect to the host and create it automatically
    try {
        $temp_pdo = new PDO("mysql:host=" . $servername . ";charset=utf8mb4", $username, $password);
        $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `" . $dbname . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $pdo = new PDO("mysql:host=" . $servername . ";dbname=" . $dbname . ";charset=utf8mb4", $username, $password, [
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
