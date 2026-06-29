<!-- Perumalil Creative - Luxury Grid-Breaking Cinematic Hero Section -->
<section class="hero hero-immersive-canvas" id="hero">

    <!-- Custom Interactive Cursor Spotlight Glow -->
    <div class="canvas-spotlight" id="canvasSpotlight"></div>

    <!-- Colossal Interactive Typographic Background Layer -->
    <div class="colossal-typography-bg">
        <!-- Row 1: Right to Left Marquee -->
        <div class="typo-marquee-row row-1" id="typoRow1">
            <div class="typo-marquee-container track-left">
                <span class="typo-line">PERUMALIL CREATIVE &bull; PERUMALIL CREATIVE &bull; PERUMALIL CREATIVE &bull;
                    PERUMALIL CREATIVE &bull; PERUMALIL CREATIVE &bull; PERUMALIL CREATIVE &bull; </span>
            </div>
        </div>
        <!-- Row 3: Left to Right Marquee -->
        <div class="typo-marquee-row row-3" id="typoRow3">
            <div class="typo-marquee-container track-right">
                <span class="typo-line outline-text">DESIGN YOUR EVERY OCCASION &bull; DESIGN YOUR EVERY OCCASION &bull;
                    DESIGN YOUR EVERY OCCASION &bull; DESIGN YOUR EVERY OCCASION &bull; DESIGN YOUR EVERY OCCASION
                    &bull; DESIGN YOUR EVERY OCCASION &bull; </span>
            </div>
        </div>
    </div>

    <!-- Foreground Creative Meta Details (Overlaid) -->
    <div class="immersive-hero-overlay">

        <div class="overlay-left-details">
            <div class="hud-location-badge">
                <span class="dot"></span>
                <span>STUDIO: VAZHAKULAM, KERALA</span>
            </div>
            <p class="studio-mission-text">
                <?php echo htmlspecialchars($settings['hero_mission_text'] ?? ''); ?>
            </p>
        </div>

        <div class="overlay-right-ctas">
            <div class="hero-cta-group">
                <a href="#services" class="magnetic-btn accent-filled-btn">
                    <span>EXPLORE ARCHIVE</span>
                </a>
                <button onclick="openLightbox('assets/images/hero.mp4')" class="magnetic-btn outline-glass-btn cta-play-btn">
                    <i data-lucide="play" class="btn-play-icon" style="width: 14px; height: 14px; margin-right: 6px; vertical-align: middle;"></i>
                    <span>PLAY SHOWREEL</span>
                </button>
                <a href="https://wa.me/919946020716" target="_blank" rel="noopener"
                    class="magnetic-btn outline-glass-btn">
                    <span>SECURE PIPELINE</span>
                </a>
            </div>
            <div class="telemetry-coordinates">
                <span>COORD: <?php echo htmlspecialchars($settings['hero_coords'] ?? ''); ?></span>
                <span id="liveIstClock">15:45:32 IST</span>
            </div>
        </div>

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
                    <video id="lightboxVideo" class="lightbox-video" controls playsinline style="display: none; width: 100%; height: 100%; object-fit: contain;"></video>
                </div>
            </div>
        </div>
    </div>

</section>