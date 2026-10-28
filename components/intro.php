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
                    <a href="#contact" class="magnetic-btn vision-cta vision-cta-btn">
                        <span>Launch Your Story</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- New Brand Logo Marquee Section -->
    <div class="intro-logos-container intro-reveal-scale">
        <div class="intro-logos-header">
            <div class="intro-logos-header-left">
                <div class="collab-badge">
                    <span class="hud-pulse-dot hud-pulse-dot-accent"></span>
                    <span>TRUSTED NETWORK &amp; ALLIANCES</span>
                </div>
                <h2 class="logos-section-title">Our <span class="text-glow">Collaborators</span></h2>
                <p class="logos-section-desc">
                    Partnering with visionary enterprises, high-growth startups, and heritage brands to craft commanding visual identities and scalable digital dominance.
                </p>
            </div>
            <div class="collab-metrics-bar">
                <div class="collab-metric-item">
                    <span class="collab-metric-num">25<span class="collab-metric-accent">+</span></span>
                    <span class="collab-metric-label">Client Partners</span>
                </div>
                <div class="collab-metric-divider"></div>
                <div class="collab-metric-item">
                    <span class="collab-metric-num">100<span class="collab-metric-accent">%</span></span>
                    <span class="collab-metric-label">Bespoke Craft</span>
                </div>
                <div class="collab-metric-divider"></div>
                <div class="collab-metric-item">
                    <span class="collab-metric-num">4.9<span class="collab-metric-star">★</span></span>
                    <span class="collab-metric-label">Excellence Score</span>
                </div>
            </div>
        </div>

        <div class="intro-logo-marquee-dual">
            <!-- Left and Right Gradient Masks for Seamless Edge Fading -->
            <div class="marquee-fade-left"></div>
            <div class="marquee-fade-right"></div>

            <?php
            try {
                $collabStmt = $pdo->query("SELECT * FROM collaborators ORDER BY order_index ASC");
                $collabList = $collabStmt->fetchAll();
            } catch (Exception $e) {
                $collabList = [];
            }

            if (empty($collabList)) {
                $collabList = [];
                for ($i = 1; $i <= 22; $i++) {
                    $collabList[] = [
                        'id' => $i,
                        'name' => "Brand Partner $i",
                        'image_path' => "assets/images/logo$i.png"
                    ];
                }
            }

            // Split into two complementary rows for dual-track velocity
            $mid = (int)ceil(count($collabList) / 2);
            $row1 = array_slice($collabList, 0, $mid);
            $row2 = array_slice($collabList, $mid);
            ?>

            <!-- Row 1: Flowing Leftward -->
            <div class="marquee-row marquee-row-1">
                <div class="marquee-track-logos track-scroll-left" id="logoMarqueeTrack1">
                    <?php
                    // Repeat row 1 twice for seamless continuous loop
                    for ($rep = 0; $rep < 2; $rep++):
                        foreach ($row1 as $collab):
                    ?>
                        <div class="logo-item-wrapper" title="<?php echo htmlspecialchars($collab['name']); ?>">
                            <div class="logo-card-ambient"></div>
                            <div class="logo-card-inner">
                                <img src="<?php echo htmlspecialchars($collab['image_path']); ?>" alt="<?php echo htmlspecialchars($collab['name']); ?>" class="marquee-logo-img" loading="lazy">
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    endfor; 
                    ?>
                </div>
            </div>

            <!-- Row 2: Flowing Rightward -->
            <div class="marquee-row marquee-row-2">
                <div class="marquee-track-logos track-scroll-right" id="logoMarqueeTrack2">
                    <?php
                    // Repeat row 2 twice for seamless continuous loop
                    for ($rep = 0; $rep < 2; $rep++):
                        foreach ($row2 as $collab):
                    ?>
                        <div class="logo-item-wrapper" title="<?php echo htmlspecialchars($collab['name']); ?>">
                            <div class="logo-card-ambient"></div>
                            <div class="logo-card-inner">
                                <img src="<?php echo htmlspecialchars($collab['image_path']); ?>" alt="<?php echo htmlspecialchars($collab['name']); ?>" class="marquee-logo-img" loading="lazy">
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    endfor; 
                    ?>
                </div>
            </div>
        </div>

        <!-- Sleek Bottom Invite Action -->
        <div class="collab-footer-cta">
            <span class="collab-footer-tag">Want to build something iconic together?</span>
            <a href="#contact" class="collab-cta-btn magnetic-btn">
                <span>Start a Collaboration</span>
                <i data-lucide="arrow-right" class="collab-btn-icon"></i>
            </a>
        </div>
    </div>
</section>