<!-- Introduction & Strategic Solutions Section -->
<section class="intro" id="intro">
    <!-- Premium Backdrop & Cinematic Spotlights -->
    <div class="intro-backdrop"></div>
    <div class="intro-spotlight-left"></div>
    <div class="intro-spotlight-right"></div>

    <!-- Dual Column Value Grid -->
    <div class="intro-dual-grid">
        <!-- Grow Your Business Column -->
        <div class="intro-col-card intro-reveal-left">
            <span class="intro-tagline"><?php echo htmlspecialchars($settings['intro_col_left_tagline'] ?? ''); ?></span>
            <h2 class="intro-col-title"><?php echo $settings['intro_col_left_title'] ?? ''; ?></h2>
            <p class="intro-col-desc">
                <?php echo htmlspecialchars($settings['intro_col_left_desc'] ?? ''); ?>
            </p>
        </div>

        <!-- Vertical Accent Grid Separator -->
        <div class="intro-separator"></div>

        <!-- Design & Marketing Column -->
        <div class="intro-col-card intro-reveal-right">
            <span class="intro-tagline"><?php echo htmlspecialchars($settings['intro_col_right_tagline'] ?? ''); ?></span>
            <h2 class="intro-col-title"><?php echo $settings['intro_col_right_title'] ?? ''; ?></h2>
            <p class="intro-col-desc">
                <?php echo htmlspecialchars($settings['intro_col_right_desc'] ?? ''); ?>
            </p>
        </div>
    </div>

    <!-- Core Pillars Showcase Grid -->
    <div class="intro-pillars-grid">
        <?php
        try {
            $pillarStmt = $pdo->query("SELECT * FROM intro_pillars ORDER BY order_index ASC");
            while ($pillar = $pillarStmt->fetch()) {
                ?>
                <div class="pillar-card intro-reveal-up" data-delay="<?php echo htmlspecialchars($pillar['delay']); ?>">
                    <div class="pillar-icon-wrapper">
                        <i data-lucide="<?php echo htmlspecialchars($pillar['icon']); ?>" class="pillar-icon"></i>
                        <?php if ($pillar['icon'] === 'clock'): ?>
                            <span class="pillar-pulse-dot"></span>
                        <?php endif; ?>
                    </div>
                    <h3 class="pillar-title"><?php echo htmlspecialchars($pillar['title']); ?></h3>
                    <p class="pillar-desc">
                        <?php echo htmlspecialchars($pillar['description']); ?>
                    </p>
                    <div class="pillar-hover-effect"></div>
                </div>
                <?php
            }
        } catch (Exception $e) {
            // Fail silently
        }
        ?>
    </div>

    <!-- Immersive Visual Vision Spotlight Container -->
    <div class="vision-banner intro-reveal-scale">
        <div class="vision-radial-glow"></div>
        <div class="vision-grid">
            <div class="vision-left">
                <div class="section-label mini-label"><?php echo htmlspecialchars($settings['intro_vision_tagline'] ?? ''); ?></div>
                <h2 class="vision-heading"><?php echo $settings['intro_vision_title'] ?? ''; ?></h2>
            </div>
            <div class="vision-right">
                <p class="vision-text">
                    <?php echo htmlspecialchars($settings['intro_vision_text'] ?? ''); ?>
                </p>
                <div class="vision-cta-wrap">
                    <a href="#contact" class="magnetic-btn vision-cta"
                        style="background-color: var(--accent); border-color: var(--accent);">
                        <span>Launch Your Story</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- New Brand Logo Marquee Section -->
    <div class="intro-logos-container intro-reveal-scale">
        <div class="intro-logos-header">
            <span class="intro-tagline" style="margin-bottom: 15px; gap: 8px;">
                <span class="hud-pulse-dot"
                    style="background-color: var(--accent); box-shadow: 0 0 10px var(--accent);"></span>
                Collaborations
            </span>
            <h2 class="logos-section-title">Our <span class="text-glow">Collaborators</span></h2>
        </div>
        <div class="intro-logo-marquee">
            <!-- Left and Right Gradient Masks for Seamless Edge Fading -->
            <div class="marquee-fade-left"></div>
            <div class="marquee-fade-right"></div>

            <div class="marquee-track-logos" id="logoMarqueeTrack">
                <?php
                try {
                    $collabStmt = $pdo->query("SELECT * FROM collaborators ORDER BY order_index ASC");
                    $collabList = $collabStmt->fetchAll();
                    
                    if (count($collabList) > 0) {
                        // Render logos
                        foreach ($collabList as $collab) {
                            echo '<div class="logo-item-wrapper"><img src="' . htmlspecialchars($collab['image_path']) . '" alt="' . htmlspecialchars($collab['name']) . '" class="marquee-logo-img"></div>';
                        }
                        // Duplicate once for seamless infinite loop
                        foreach ($collabList as $collab) {
                            echo '<div class="logo-item-wrapper"><img src="' . htmlspecialchars($collab['image_path']) . '" alt="' . htmlspecialchars($collab['name']) . '" class="marquee-logo-img"></div>';
                        }
                    } else {
                        // Fallback static if empty
                        for ($i = 1; $i <= 22; $i++) {
                            echo '<div class="logo-item-wrapper"><img src="assets/images/logo' . $i . '.png" alt="Brand Partner ' . $i . '" class="marquee-logo-img"></div>';
                        }
                        for ($i = 1; $i <= 22; $i++) {
                            echo '<div class="logo-item-wrapper"><img src="assets/images/logo' . $i . '.png" alt="Brand Partner ' . $i . '" class="marquee-logo-img"></div>';
                        }
                    }
                } catch (Exception $e) {
                    // Fallback
                    for ($i = 1; $i <= 22; $i++) {
                        echo '<div class="logo-item-wrapper"><img src="assets/images/logo' . $i . '.png" alt="Brand Partner ' . $i . '" class="marquee-logo-img"></div>';
                    }
                    for ($i = 1; $i <= 22; $i++) {
                        echo '<div class="logo-item-wrapper"><img src="assets/images/logo' . $i . '.png" alt="Brand Partner ' . $i . '" class="marquee-logo-img"></div>';
                    }
                }
                ?>
            </div>
        </div>
    </div>
</section>