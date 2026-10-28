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
    'portfolio_launching_soon.jpg' => 'C:\\Users\\sojin\\.gemini\\antigravity-ide\\brain\\b19355e1-e071-46a6-a0bc-1ed0d088edb6\\launching_soon_template_1791814759267.jpg',
    'launching_soon_template.jpg' => 'C:\\Users\\sojin\\.gemini\\antigravity-ide\\brain\\b19355e1-e071-46a6-a0bc-1ed0d088edb6\\launching_soon_template_1791814759267.jpg',
];

$genTemplateSrc = 'C:\\Users\\sojin\\.gemini\\antigravity-ide\\brain\\b19355e1-e071-46a6-a0bc-1ed0d088edb6\\launching_soon_template_1791814759267.jpg';
if (file_exists($genTemplateSrc) && (!file_exists($img_dir . '/launching_soon_template.jpg') || filesize($img_dir . '/launching_soon_template.jpg') != filesize($genTemplateSrc))) {
    @copy($genTemplateSrc, $img_dir . '/launching_soon_template.jpg');
    @copy($genTemplateSrc, $img_dir . '/portfolio_launching_soon.jpg');
}

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

    <!-- Google Fonts: Luxury Marquee Typography Suite -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Cinzel:wght@700;800;900&family=Italiana&family=Montserrat:wght@800;900&family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Righteous&family=Space+Grotesk:wght@600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/main.css?v=<?php echo @filemtime(dirname(__DIR__) . '/assets/css/main.css') ?: time(); ?>">

    <!-- Dynamic Site Typography & Universal Color Engine -->
    <?php
    $globalFontsMap = [
        'Syne' => "'Syne', sans-serif",
        'Space Grotesk' => "'Space Grotesk', sans-serif",
        'Cinzel' => "'Cinzel', serif",
        'Bebas Neue' => "'Bebas Neue', sans-serif",
        'Outfit' => "'Outfit', sans-serif",
        'Playfair Display' => "'Playfair Display', serif",
        'Righteous' => "'Righteous', cursive",
        'Montserrat' => "'Montserrat', sans-serif",
        'Italiana' => "'Italiana', serif",
        'Plus Jakarta Sans' => "'Plus Jakarta Sans', sans-serif",
    ];

    $fontHeading = $settings['site_font_heading'] ?? 'Syne';
    $fontEditorial = $settings['site_font_editorial'] ?? 'Outfit';
    $fontBody = $settings['site_font_body'] ?? 'Plus Jakarta Sans';
    $fontHero = $settings['hero_headline_font'] ?? $fontHeading;

    $headingFontCss = $globalFontsMap[$fontHeading] ?? "'Syne', sans-serif";
    $editorialFontCss = $globalFontsMap[$fontEditorial] ?? "'Outfit', sans-serif";
    $bodyFontCss = $globalFontsMap[$fontBody] ?? "'Plus Jakarta Sans', sans-serif";
    $heroFontCss = $globalFontsMap[$fontHero] ?? $headingFontCss;

    $colorHeading = $settings['site_color_heading'] ?? '#ffffff';
    $colorSubheading = $settings['site_color_subheading'] ?? '#38bdf8';
    $colorBody = $settings['site_color_body'] ?? '#a0a0ab';
    $colorMuted = $settings['site_color_muted'] ?? '#64748b';
    $colorAccent = $settings['site_color_accent'] ?? '#0066ff';
    $colorHeroHeadline = $settings['hero_color_headline'] ?? $colorHeading;
    $colorHeroGlow = $settings['hero_color_glow'] ?? $colorSubheading;
    $colorHeroMission = $settings['hero_color_mission'] ?? $colorBody;
    $colorBg = $settings['site_color_bg'] ?? '#050505';
    $colorCard = $settings['site_color_card'] ?? '#0c0d12';
    ?>
    <style id="dynamic-site-typography-colors">
        :root {
            --bg-dark: <?php echo htmlspecialchars($colorBg); ?>;
            --bg-card: <?php echo htmlspecialchars($colorCard); ?>;

            --font-heading: <?php echo $headingFontCss; ?>;
            --font-editorial: <?php echo $editorialFontCss; ?>;
            --font-body: <?php echo $bodyFontCss; ?>;

            --text-primary: <?php echo htmlspecialchars($colorHeading); ?>;
            --text-secondary: <?php echo htmlspecialchars($colorBody); ?>;
            --text-subheading: <?php echo htmlspecialchars($colorSubheading); ?>;
            --text-muted: <?php echo htmlspecialchars($colorMuted); ?>;
            --accent: <?php echo htmlspecialchars($colorAccent); ?>;
            --accent-glow: <?php echo htmlspecialchars($colorAccent); ?>33;

            --hero-headline-font: <?php echo $heroFontCss; ?>;
            --hero-headline-color: <?php echo htmlspecialchars($colorHeroHeadline); ?>;
            --hero-glow-color: <?php echo htmlspecialchars($colorHeroGlow); ?>;
            --hero-mission-color: <?php echo htmlspecialchars($colorHeroMission); ?>;
        }

        /* Seamless propagation across whole website full background */
        html, body {
            background-color: var(--bg-dark) !important;
        }

        .webgl-background {
            background: radial-gradient(circle at 50% 50%, <?php echo htmlspecialchars($colorCard); ?> 0%, <?php echo htmlspecialchars($colorBg); ?> 100%) !important;
        }

        .preloader {
            background-color: var(--bg-dark) !important;
        }

        .hero, .hero-immersive-canvas {
            background: transparent !important;
            background-color: transparent !important;
        }

        .intro, .services-section, .portfolio-section, .pricing-section, .location-section, .contact-section, .footer {
            background-color: var(--bg-dark);
        }

        .intro-logos-container {
            background: radial-gradient(ellipse at 50% -20%, <?php echo htmlspecialchars($colorAccent); ?>15 0%, transparent 65%), var(--bg-dark) !important;
        }

        .marquee-fade-left {
            background: linear-gradient(to right, var(--bg-dark) 0%, transparent 100%) !important;
        }

        .marquee-fade-right {
            background: linear-gradient(to left, var(--bg-dark) 0%, transparent 100%) !important;
        }

        .navbar.scrolled {
            background: <?php echo htmlspecialchars($colorBg); ?>cc !important;
        }

        /* Seamless propagation across all headings and text elements */
        h1, h2, h3, h4, .section-title, .intro-col-title, .vision-heading, .services-title, 
        .packages-title, .portfolio-title, .about-title, .contact-headline, .logos-section-title, 
        .pillar-title, .nav-brand, .service-card-new-title, .pricing-card-name, .package-price {
            font-family: var(--font-heading) !important;
            color: var(--text-primary);
        }

        .hero-brand-headline {
            font-family: var(--hero-headline-font, var(--font-heading)) !important;
            color: var(--hero-headline-color, var(--text-primary)) !important;
        }

        .hero-brand-headline .text-glow {
            color: transparent !important;
            -webkit-text-stroke: 1.2px var(--hero-glow-color, var(--text-primary)) !important;
            text-shadow: 0 0 24px <?php echo htmlspecialchars($colorHeroGlow); ?>80 !important;
        }

        .studio-mission-text {
            font-family: var(--font-body) !important;
            color: var(--hero-mission-color, var(--text-secondary)) !important;
        }

        .intro-tagline, .section-label, .collab-badge, .hud-location-badge, 
        .about-tagline, .faq-tagline, .contact-tagline, .badge-tag, .popular-badge,
        .pricing-card-price-label, .telemetry-coordinates, .nav-links a, .theme-switcher-btn {
            font-family: var(--font-editorial) !important;
        }

        .intro-tagline, .section-label, .collab-badge, .hud-location-badge,
        .intro-tagline::before {
            color: var(--text-subheading) !important;
        }

        p, .intro-col-desc, .vision-text, .services-desc, .service-card-new-desc,
        .portfolio-desc, .about-statement, .about-desc, .pillar-desc, .faq-answer, 
        .contact-desc, .footer-copyright, .mobile-nav-contact-info {
            font-family: var(--font-body) !important;
            color: var(--text-secondary);
        }

        .magnetic-btn, .nav-cta-btn, .vision-cta-btn, .pricing-card.popular, .btn-primary {
            border-color: var(--accent);
        }
    </style>

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

            <a href="https://wa.me/919946020716" target="_blank" rel="noopener" class="magnetic-btn nav-cta-btn" id="navCTA">
                <span>Chat with Us</span>
            </a>
        </div>

        <!-- Mobile Menu Toggle -->
        <button class="menu-btn" id="menuBtn" aria-label="Toggle Navigation Drawer" aria-expanded="false" onclick="toggleMenu()">
            <i data-lucide="menu" id="menuIcon" size="26"></i>
        </button>
    </nav>

    <!-- Mobile Navigation Drawer Overlay -->
    <div class="mobile-nav-overlay" id="mobileNav" role="dialog" aria-modal="true" aria-label="Mobile Navigation">
        <div class="mobile-nav-backdrop" onclick="closeMenu()"></div>
        <div class="mobile-nav-panel">
            <div class="mobile-nav-header">
                <a href="#hero" class="mobile-nav-brand" onclick="closeMenu()">
                    Perumalil Creative<span></span>
                </a>
                <button class="mobile-nav-close" onclick="closeMenu()" aria-label="Close Navigation Drawer">
                    <i data-lucide="x" size="22"></i>
                </button>
            </div>

            <div class="mobile-nav-links">
                <a href="#hero" class="mobile-nav-link" onclick="closeMenu()">
                    <span class="nav-num">01</span>
                    <span class="nav-text">Home</span>
                </a>
                <a href="#services" class="mobile-nav-link" onclick="closeMenu()">
                    <span class="nav-num">02</span>
                    <span class="nav-text">Services</span>
                </a>
                <a href="#portfolio" class="mobile-nav-link" onclick="closeMenu()">
                    <span class="nav-num">03</span>
                    <span class="nav-text">Portfolio</span>
                </a>
                <a href="#about" class="mobile-nav-link" onclick="closeMenu()">
                    <span class="nav-num">04</span>
                    <span class="nav-text">About</span>
                </a>
                <a href="#faq" class="mobile-nav-link" onclick="closeMenu()">
                    <span class="nav-num">05</span>
                    <span class="nav-text">FAQs</span>
                </a>
                <a href="#contact" class="mobile-nav-link" onclick="closeMenu()">
                    <span class="nav-num">06</span>
                    <span class="nav-text">Contact</span>
                </a>
            </div>

            <div class="mobile-nav-footer">
                <div class="mobile-nav-contact-info">
                    <a href="tel:+919946020716" class="mobile-contact-item">
                        <i data-lucide="phone" size="14"></i>
                        <span>+91 99460 20716</span>
                    </a>
                    <span class="mobile-nav-location">
                        <i data-lucide="map-pin" size="14"></i>
                        <span>Vazhakulam, Kerala</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Smooth Scroll Workspace -->
    <div id="scroll-container" class="scroll-container">