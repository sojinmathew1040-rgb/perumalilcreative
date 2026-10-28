<?php
// Retrieve Dynamic Marquee Settings from Database
$marqueeMode = $settings['hero_marquee_mode'] ?? 'words';
$marqueeColor1 = $settings['hero_marquee_color1'] ?? '#0066ff';
$marqueeColor2 = $settings['hero_marquee_color2'] ?? '#38bdf8';
$marqueeColor3 = $settings['hero_marquee_color3'] ?? '#ffffff';
$marqueeColor4 = $settings['hero_marquee_color4'] ?? '#a855f7';
$marqueePalette = array_values(array_filter([$marqueeColor1, $marqueeColor2, $marqueeColor3, $marqueeColor4]));
if (empty($marqueePalette)) {
    $marqueePalette = ['#0066ff', '#38bdf8', '#ffffff', '#a855f7'];
}

$marqueeFont = $settings['hero_marquee_font'] ?? 'Syne';
$marqueeFontMap = [
    'Syne' => ['family' => "'Syne', sans-serif", 'weight' => '800'],
    'Space Grotesk' => ['family' => "'Space Grotesk', sans-serif", 'weight' => '700'],
    'Cinzel' => ['family' => "'Cinzel', serif", 'weight' => '800'],
    'Bebas Neue' => ['family' => "'Bebas Neue', sans-serif", 'weight' => '400'],
    'Outfit' => ['family' => "'Outfit', sans-serif", 'weight' => '800'],
    'Playfair Display' => ['family' => "'Playfair Display', serif", 'weight' => '900'],
    'Righteous' => ['family' => "'Righteous', cursive", 'weight' => '400'],
    'Montserrat' => ['family' => "'Montserrat', sans-serif", 'weight' => '900'],
    'Italiana' => ['family' => "'Italiana', serif", 'weight' => '400'],
    'Plus Jakarta Sans' => ['family' => "'Plus Jakarta Sans', sans-serif", 'weight' => '800'],
];
$fontConfig = $marqueeFontMap[$marqueeFont] ?? $marqueeFontMap['Syne'];
$activeMarqueeFontFamily = $fontConfig['family'];
$activeMarqueeFontWeight = $fontConfig['weight'];

$rawWaPhone = $settings['contact_phone1'] ?? '919946020716';
$cleanWaPhone = preg_replace('/[^0-9]/', '', $rawWaPhone);
if (strlen($cleanWaPhone) === 10) {
    $cleanWaPhone = '91' . $cleanWaPhone;
}
if (empty($cleanWaPhone)) {
    $cleanWaPhone = '919946020716';
}

$defaultRow1 = 'PERUMALIL CREATIVE • PERUMALIL CREATIVE • PERUMALIL CREATIVE • PERUMALIL CREATIVE • PERUMALIL CREATIVE • PERUMALIL CREATIVE • ';
$defaultRow2 = 'CREATIVE DIRECTION • CINEMATIC PRODUCTION • BRAND ARCHITECTURE • DIGITAL EXPERIENCES • CREATIVE DIRECTION • CINEMATIC PRODUCTION • BRAND ARCHITECTURE • DIGITAL EXPERIENCES • ';

$marqueeRow1Text = !empty($settings['hero_marquee_row1_text']) ? $settings['hero_marquee_row1_text'] : $defaultRow1;
$marqueeRow2Text = !empty($settings['hero_marquee_row2_text']) ? $settings['hero_marquee_row2_text'] : $defaultRow2;

$marqueeRow1Style = $settings['hero_marquee_row1_style'] ?? 'filled';
$marqueeRow2Style = $settings['hero_marquee_row2_style'] ?? 'outline';
$marqueeOpacity = !empty($settings['hero_marquee_opacity']) ? $settings['hero_marquee_opacity'] : '0.85';
if ($marqueeOpacity === '0.35') {
    $marqueeOpacity = '0.85';
}
$marqueeSpeed = (int)($settings['hero_marquee_speed'] ?? 55);
if ($marqueeSpeed < 10) $marqueeSpeed = 55;

if (!function_exists('renderHeroMarqueeHTML')) {
    function renderHeroMarqueeHTML($rawText, $mode, $palette, $style = 'filled') {
        $cleanText = str_replace(['&bull;', '&#8226;'], '•', $rawText);
        $cleanText = preg_replace('/\s+/', ' ', trim($cleanText));
        $paletteCount = count($palette);

        if ($mode === 'solid') {
            $color = $palette[0];
            if ($style === 'outline') {
                return '<span class="typo-line outline-text" style="-webkit-text-stroke: 1.5px ' . htmlspecialchars($color) . '; color: transparent;">' . htmlspecialchars($cleanText) . ' </span>';
            } else {
                return '<span class="typo-line" style="color: ' . htmlspecialchars($color) . ';">' . htmlspecialchars($cleanText) . ' </span>';
            }
        }

        if ($mode === 'gradient') {
            $gradStr = implode(', ', $palette);
            $gradCss = 'background: linear-gradient(90deg, ' . $gradStr . '); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;';
            if ($style === 'outline') {
                return '<span class="typo-line outline-text" style="-webkit-text-stroke: 1.5px ' . htmlspecialchars($palette[0]) . '; color: transparent;">' . htmlspecialchars($cleanText) . ' </span>';
            } else {
                return '<span class="typo-line" style="' . $gradCss . '">' . htmlspecialchars($cleanText) . ' </span>';
            }
        }

        if ($mode === 'letters') {
            $output = '';
            $chars = mb_str_split($cleanText);
            $charIdx = 0;
            foreach ($chars as $ch) {
                if ($ch === '•') {
                    $output .= '<span class="typo-bullet" style="color: rgba(255,255,255,0.4);">&bull;</span>';
                } elseif ($ch === ' ' || $ch === "\t" || $ch === "\n") {
                    $output .= ' ';
                } else {
                    $col = $palette[$charIdx % $paletteCount];
                    $charIdx++;
                    if ($style === 'outline') {
                        $output .= '<span class="typo-char" style="-webkit-text-stroke: 1.5px ' . htmlspecialchars($col) . '; color: transparent;">' . htmlspecialchars($ch) . '</span>';
                    } else {
                        $output .= '<span class="typo-char" style="color: ' . htmlspecialchars($col) . ';">' . htmlspecialchars($ch) . '</span>';
                    }
                }
            }
            $cls = ($style === 'outline') ? 'typo-line outline-text' : 'typo-line';
            return '<span class="' . $cls . '">' . $output . ' </span>';
        }

        // Default: 'words' (Word-by-Word Multi-Color)
        $tokens = preg_split('/(\s+|•)/u', $cleanText, -1, PREG_SPLIT_DELIM_CAPTURE);
        $output = '';
        $wordIdx = 0;
        foreach ($tokens as $part) {
            if ($part === '•') {
                $output .= '<span class="typo-bullet" style="color: rgba(255,255,255,0.4);">&bull;</span>';
            } elseif (trim($part) === '') {
                $output .= $part;
            } else {
                $col = $palette[$wordIdx % $paletteCount];
                $wordIdx++;
                if ($style === 'outline') {
                    $output .= '<span class="typo-word" style="-webkit-text-stroke: 1.5px ' . htmlspecialchars($col) . '; color: transparent;">' . htmlspecialchars($part) . '</span>';
                } else {
                    $output .= '<span class="typo-word" style="color: ' . htmlspecialchars($col) . ';">' . htmlspecialchars($part) . '</span>';
                }
            }
        }
        $cls = ($style === 'outline') ? 'typo-line outline-text' : 'typo-line';
        return '<span class="' . $cls . '">' . $output . ' </span>';
    }
}

$renderedRow1 = renderHeroMarqueeHTML($marqueeRow1Text, $marqueeMode, $marqueePalette, $marqueeRow1Style);
$renderedRow2 = renderHeroMarqueeHTML($marqueeRow2Text, $marqueeMode, $marqueePalette, $marqueeRow2Style);
?>
<!-- Perumalil Creative - Luxury Grid-Breaking Cinematic Hero Section -->
<section class="hero hero-immersive-canvas" id="hero">

    <!-- Custom Interactive Cursor Spotlight Glow -->
    <div class="canvas-spotlight" id="canvasSpotlight"></div>

    <!-- Colossal Interactive Typographic Background Layer -->
    <div class="colossal-typography-bg" style="opacity: <?php echo htmlspecialchars($marqueeOpacity); ?>; --marquee-speed: <?php echo $marqueeSpeed; ?>s; --marquee-font: <?php echo htmlspecialchars($activeMarqueeFontFamily); ?>; --marquee-weight: <?php echo htmlspecialchars($activeMarqueeFontWeight); ?>;">
        <!-- Row 1: Right to Left Marquee -->
        <div class="typo-marquee-row row-1" id="typoRow1">
            <div class="typo-marquee-container track-left">
                <?php echo $renderedRow1 . $renderedRow1; ?>
            </div>
        </div>
        <!-- Row 3: Left to Right Marquee -->
        <div class="typo-marquee-row row-3" id="typoRow3">
            <div class="typo-marquee-container track-right">
                <?php echo $renderedRow2 . $renderedRow2; ?>
            </div>
        </div>
    </div>

    <!-- Foreground Creative Meta Details (Overlaid) -->
    <div class="immersive-hero-overlay">

        <div class="overlay-left-details">
            <div class="hud-meta-row">
                <div class="hud-location-badge">
                    <span class="dot"></span>
                    <span>STUDIO: VAZHAKULAM, KERALA</span>
                </div>
                <?php 
                $isAiEnabled = !isset($settings['ai_enabled']) || $settings['ai_enabled'] === '1' || $settings['ai_enabled'] === 'true' || $settings['ai_enabled'] === 1;
                $aiBotName = !empty($settings['ai_bot_name']) ? $settings['ai_bot_name'] : 'Nova';
                if ($isAiEnabled): 
                ?>
                <button type="button" class="hud-ai-badge" id="heroAiTriggerBtn" onclick="if(window.openNovaAiAssistant) window.openNovaAiAssistant();" aria-label="Meet <?php echo htmlspecialchars($aiBotName); ?> AI Assistant" title="Meet <?php echo htmlspecialchars($aiBotName); ?>, our Studio AI Assistant">
                    <span class="hud-ai-dot"></span>
                    <span class="hud-ai-sparkle">✦</span>
                    <span>AI ASSISTANT • CHAT WITH <?php echo strtoupper(htmlspecialchars($aiBotName)); ?></span>
                </button>
                <?php endif; ?>
            </div>
            <h1 class="hero-brand-headline">
                <?php echo htmlspecialchars($settings['hero_headline_part1'] ?? 'Design Your'); ?> <br>
                <span class="text-glow"><?php echo htmlspecialchars($settings['hero_headline_part2'] ?? 'Every Occasion.'); ?></span>
            </h1>
            <p class="studio-mission-text">
                <?php echo htmlspecialchars($settings['hero_mission_text'] ?? ''); ?>
            </p>
        </div>

        <div class="overlay-right-ctas">
            <div class="telemetry-coordinates">
                <span>COORD: <?php echo htmlspecialchars($settings['hero_coords'] ?? ''); ?></span>
                <span id="liveIstClock">15:45:32 IST</span>
            </div>
        </div>

    </div>

    <!-- Mobile Hero WhatsApp CTA (Displayed on Mobile View) -->
    <div class="hero-mobile-cta-wrap">
        <a href="https://wa.me/<?php echo htmlspecialchars($cleanWaPhone); ?>" target="_blank" rel="noopener" class="hero-mobile-whatsapp-btn" id="heroMobileWhatsApp">
            <svg class="whatsapp-btn-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
            </svg>
            <span>CHAT ON WHATSAPP</span>
        </a>
    </div>

    <!-- Luxury Scroll indicator -->
    <div class="scroll-indicator" id="scrollIndicator" onclick="scrollToNextSection()">
        <span></span>Scroll to Explore
    </div>

    <!-- Interactive Video Lightbox Markup -->
    <div id="videoLightbox" class="video-lightbox">
        <div class="lightbox-overlay" onclick="closeLightbox()"></div>
        <div class="lightbox-container">
            <button class="lightbox-close" onclick="closeLightbox()" aria-label="Close Lightbox">
                <i data-lucide="x" size="20"></i>
            </button>
            <div class="lightbox-content">
                <div class="video-ratio-wrapper">
                    <iframe id="lightboxIframe" src="" frameborder="0" allow="autoplay; encrypted-media; fullscreen" allowfullscreen></iframe>
                    <video id="lightboxVideo" class="lightbox-video" controls playsinline></video>
                </div>
            </div>
        </div>
    </div>

</section>