<!-- About & Creative Philosophy -->
<section class="about" id="about">
    <div class="about-grid">
        <div>
            <div class="section-label"><?php echo htmlspecialchars($settings['about_philosophy_tagline'] ?? ''); ?></div>
            <h2 class="about-title">
                <?php echo $settings['about_philosophy_title'] ?? ''; ?>
            </h2>
        </div>

        <div class="about-content">
            <p class="about-statement">
                <?php echo htmlspecialchars($settings['about_philosophy_desc'] ?? ''); ?>
            </p>

            <div class="about-philosophy-grid">
                <div>
                    <h3 class="about-block-title"><?php echo htmlspecialchars($settings['about_detail1_title'] ?? ''); ?></h3>
                    <p class="about-block-desc">
                        <?php echo htmlspecialchars($settings['about_detail1_desc'] ?? ''); ?>
                    </p>
                </div>
                <div>
                    <h3 class="about-block-title"><?php echo htmlspecialchars($settings['about_detail2_title'] ?? ''); ?></h3>
                    <p class="about-block-desc">
                        <?php echo htmlspecialchars($settings['about_detail2_desc'] ?? ''); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- About Us Narrative Section -->
    <div class="about-grid about-us-section about-section-divider">
        <div>
            <div class="section-label"><?php echo htmlspecialchars($settings['about_us_tagline'] ?? 'About Us'); ?></div>
            <h2 class="about-title">
                <?php echo $settings['about_us_title'] ?? 'Your Partner in<br>Digital Growth'; ?>
            </h2>
            <p class="about-statement about-statement-text">
                <?php echo htmlspecialchars($settings['about_us_subtitle'] ?? ''); ?>
            </p>
        </div>

        <div class="about-us-narrative-col">
            <p class="about-desc about-desc-p1">
                <?php echo htmlspecialchars($settings['about_us_desc1'] ?? ''); ?>
            </p>
            <p class="about-desc about-desc-p2">
                <?php echo htmlspecialchars($settings['about_us_desc2'] ?? ''); ?>
            </p>
            
            <div class="about-capability-pills">
                <div class="about-cap-pill">
                    <span class="cap-dot"></span>
                    <span>Brand Architecture</span>
                </div>
                <div class="about-cap-pill">
                    <span class="cap-dot"></span>
                    <span>Cinematic 4K Visuals</span>
                </div>
                <div class="about-cap-pill">
                    <span class="cap-dot"></span>
                    <span>Full-Stack Development</span>
                </div>
                <div class="about-cap-pill">
                    <span class="cap-dot"></span>
                    <span>Performance Marketing</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Dedicated Full-Width Leadership & Team Showcase -->
    <?php
    try {
        $teamStmt = $pdo->query("SELECT * FROM team_members ORDER BY order_index ASC");
        $allMembers = $teamStmt->fetchAll();
        $totalMembers = count($allMembers);
    } catch (Exception $e) {
        $allMembers = [];
        $totalMembers = 0;
    }
    ?>
    <?php if ($totalMembers > 0): ?>
    <?php
    // Prepare circular loop members (clone set so scroll wraps infinitely without jump)
    $repeatCount = ($totalMembers <= 4) ? 3 : 2;
    $loopMembers = [];
    for ($r = 0; $r < $repeatCount; $r++) {
        foreach ($allMembers as $mIdx => $m) {
            $m['_original_index'] = $mIdx;
            $m['_is_clone'] = ($r > 0);
            $loopMembers[] = $m;
        }
    }
    ?>
    <div class="team-showcase-section">
        <div class="team-header-row">
            <div class="team-header-left">
                <div class="section-label">Our Leadership Team</div>
                <h3 class="team-showcase-title">The Creative Minds &amp; Architects</h3>
            </div>
            <div class="team-carousel-controls" id="teamCarouselControls">
                <span class="team-carousel-counter" id="teamCounter">01 / <?php echo str_pad($totalMembers, 2, '0', STR_PAD_LEFT); ?></span>
                <button class="team-nav-arrow" id="teamPrevBtn" aria-label="Previous Team Member" type="button">
                    <i data-lucide="chevron-left" size="18"></i>
                </button>
                <button class="team-nav-arrow" id="teamNextBtn" aria-label="Next Team Member" type="button">
                    <i data-lucide="chevron-right" size="18"></i>
                </button>
            </div>
        </div>

        <div class="team-slider-viewport-wrap">
            <div class="team-slider-fade-left"></div>
            <div class="team-slider-fade-right"></div>
            <div class="team-slider-viewport" id="teamSliderViewport">
                <div class="team-grid" id="teamGrid" data-total-unique="<?php echo $totalMembers; ?>" data-repeat-count="<?php echo $repeatCount; ?>">
                    <?php foreach ($loopMembers as $loopIdx => $member): ?>
                        <div class="team-card<?php echo !empty($member['_is_clone']) ? ' is-clone' : ''; ?>" data-unique-index="<?php echo $member['_original_index']; ?>">
                            <div class="team-img-wrap">
                                <span class="team-index-tag"><?php echo str_pad($member['_original_index'] + 1, 2, '0', STR_PAD_LEFT); ?></span>
                                <img src="<?php echo htmlspecialchars($member['image_path']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>" class="team-img" loading="lazy">
                                <div class="team-img-glow"></div>
                            </div>
                            <div class="team-details">
                                <h3 class="team-name"><?php echo htmlspecialchars($member['name']); ?></h3>
                                <p class="team-role"><?php echo htmlspecialchars($member['role']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Slider Progress Bar & Dots -->
        <div class="team-slider-footer">
            <div class="team-progress-track">
                <div class="team-progress-bar" id="teamProgressBar"></div>
            </div>
            <?php if ($totalMembers > 1): ?>
            <div class="team-carousel-indicators" id="teamIndicators">
                <?php for ($i = 0; $i < $totalMembers; $i++): ?>
                    <button class="team-indicator-dot <?php echo ($i === 0) ? 'is-active' : ''; ?>" data-slide="<?php echo $i; ?>" aria-label="Slide <?php echo $i + 1; ?>" type="button"></button>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</section>