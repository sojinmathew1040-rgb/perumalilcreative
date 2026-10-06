<?php
require_once __DIR__ . '/db.php';

$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Fail silently
}
// Automatic local asset initializer for Perumalil Creative website
$img_dir = dirname(__DIR__) . '/assets/images';
if (!is_dir($img_dir)) {
    mkdir($img_dir, 0777, true);
}
$asset_mapping = [
    'portfolio_festival.png' => 'C:\\Users\\sojin\\.gemini\\antigravity-ide\\brain\\bb806a4a-7260-4e2a-a788-b3c9363c1ff9\\portfolio_festival_1779860110951.png',
    'portfolio_reel.png' => 'C:\\Users\\sojin\\.gemini\\antigravity-ide\\brain\\bb806a4a-7260-4e2a-a788-b3c9363c1ff9\\portfolio_reel_1779860128553.png',
    'portfolio_web.png' => 'C:\\Users\\sojin\\.gemini\\antigravity-ide\\brain\\bb806a4a-7260-4e2a-a788-b3c9363c1ff9\\portfolio_web_1779860146649.png',
    'portfolio_marketing.png' => 'C:\\Users\\sojin\\.gemini\\antigravity-ide\\brain\\bb806a4a-7260-4e2a-a788-b3c9363c1ff9\\portfolio_marketing_1779860181884.png',
    'portfolio_social.png' => 'C:\\Users\\sojin\\.gemini\\antigravity-ide\\brain\\bb806a4a-7260-4e2a-a788-b3c9363c1ff9\\portfolio_social_1779860323113.png',
];

foreach ($asset_mapping as $filename => $source_path) {
    $dest_path = $img_dir . '/' . $filename;
    if (!file_exists($dest_path) && file_exists($source_path)) {
        copy($source_path, $dest_path);
    }
}

// Automatic local cleanup of old files consolidated into header
$redundant_files = [
    dirname(__DIR__) . '/includes/head.php',
    dirname(__DIR__) . '/components/navbar.php',
    dirname(__DIR__) . '/components/preloader.php',
    dirname(__DIR__) . '/components/collaborations.php'
];
foreach ($redundant_files as $file) {
    if (file_exists($file)) {
        @unlink($file);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Premium SEO Metadata -->
    <title><?php echo htmlspecialchars($settings['seo_title'] ?? 'Perumalil Creative'); ?></title>
    <meta name="description"
        content="<?php echo htmlspecialchars($settings['seo_description'] ?? ''); ?>">
    <meta name="keywords"
        content="<?php echo htmlspecialchars($settings['seo_keywords'] ?? ''); ?>">
    <meta name="author" content="Perumalil Creative">

    <!-- Open Graph Metadata -->
    <meta property="og:title" content="<?php echo htmlspecialchars($settings['seo_title'] ?? 'Perumalil Creative'); ?>">
    <meta property="og:description"
        content="<?php echo htmlspecialchars($settings['seo_description'] ?? ''); ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Perumalil Creative">

    <!-- Google Fonts: Outfit, Plus Jakarta Sans, Syne -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Syne:wght@700;800&display=swap"
        rel="stylesheet">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/main.css">

    <!-- Three.js (3D Scene Engine) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    <!-- GSAP & ScrollTrigger -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

    <!-- Lenis Smooth Scrolling -->
    <script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.34/dist/lenis.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Leaflet Map Engine -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>

<body class="loading">

    <!-- WebGL Canvas background -->
    <canvas id="webgl-canvas" class="webgl-background"></canvas>

    <!-- Cinematic Preloader Overlay -->
    <div class="preloader" id="preloader">
        <div class="loader-header">
            <div>PERUMALIL CREATIVE</div>
            <div>LOADING EXPERIENCE &copy; 2026</div>
        </div>
        <div class="loader-brand" id="loaderBrand">
            PERUMALIL CREATIVE
        </div>
        <div class="loader-counter">
            <span id="loaderPercent">000</span>
        </div>
    </div>

    <!-- Sticky Navigation Bar -->
    <nav class="navbar" id="navbar">
        <a href="#hero" class="nav-brand">
            Perumalil Creative<span></span>
        </a>

        <div class="nav-links">
            <a href="#hero" class="nav-link">Home</a>
            <a href="#services" class="nav-link">Services</a>
            <a href="#portfolio" class="nav-link">Portfolio</a>
            <a href="#about" class="nav-link">About</a>
            <a href="#faq" class="nav-link">FAQs</a>
            <a href="#contact" class="nav-link">Contact</a>

            <a href="https://wa.me/919946020716" target="_blank" rel="noopener" class="magnetic-btn" id="navCTA"
                style="background-color: var(--accent); border-color: var(--accent);">
                <span>Chat with Us</span>
            </a>
        </div>

        <!-- Mobile Menu Toggle -->
        <button class="menu-btn" id="menuBtn" aria-label="Toggle Navigation Drawer">
            <i data-lucide="menu" size="28"></i>
        </button>
    </nav>

    <!-- Mobile Navigation Drawer Overlay -->
    <div class="mobile-nav-overlay" id="mobileNav">
        <div class="mobile-nav-links">
            <a href="#hero" class="mobile-nav-link" onclick="toggleMenu()">Home</a>
            <a href="#services" class="mobile-nav-link" onclick="toggleMenu()">Services</a>
            <a href="#portfolio" class="mobile-nav-link" onclick="toggleMenu()">Portfolio</a>
            <a href="#about" class="mobile-nav-link" onclick="toggleMenu()">About</a>
            <a href="#faq" class="mobile-nav-link" onclick="toggleMenu()">FAQs</a>
            <a href="#contact" class="mobile-nav-link" onclick="toggleMenu()">Contact</a>
        </div>
    </div>

    <!-- Main Smooth Scroll Workspace -->
    <div id="scroll-container" class="scroll-container">