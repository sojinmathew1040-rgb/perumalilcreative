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

    <!-- About Us & Team Grid -->
    <div class="about-grid about-us-section about-section-divider">
        <div>
            <div class="section-label"><?php echo htmlspecialchars($settings['about_us_tagline'] ?? ''); ?></div>
            <h2 class="about-title">
                <?php echo $settings['about_us_title'] ?? ''; ?>
            </h2>
            <p class="about-statement about-statement-text">
                <?php echo htmlspecialchars($settings['about_us_subtitle'] ?? ''); ?>
            </p>
            <p class="about-desc about-desc-p1">
                <?php echo htmlspecialchars($settings['about_us_desc1'] ?? ''); ?>
            </p>
            <p class="about-desc about-desc-p2">
                <?php echo htmlspecialchars($settings['about_us_desc2'] ?? ''); ?>
            </p>
        </div>

        <div class="team-leadership-col">
            <div class="team-header-row">
                <div class="section-label">Our Leadership Team</div>
                <div class="team-carousel-controls">
                    <span class="team-carousel-counter" id="teamCounter">01 / 04</span>
                    <button class="team-nav-arrow" id="teamPrevBtn" aria-label="Previous Team Member" type="button">
                        <i data-lucide="chevron-left" size="14"></i>
                    </button>
                    <button class="team-nav-arrow" id="teamNextBtn" aria-label="Next Team Member" type="button">
                        <i data-lucide="chevron-right" size="14"></i>
                    </button>
                </div>
            </div>
            <div class="team-grid" id="teamGrid">
                <?php
                try {
                    $teamStmt = $pdo->query("SELECT * FROM team_members ORDER BY order_index ASC");
                    $allMembers = $teamStmt->fetchAll();
                    $totalMembers = count($allMembers);
                    foreach ($allMembers as $mIdx => $member) {
                        $activeClass = ($mIdx === 0) ? ' is-active' : '';
                        ?>
                        <div class="team-card<?php echo $activeClass; ?>" data-index="<?php echo $mIdx; ?>">
                            <div class="team-img-wrap">
                                <img src="<?php echo htmlspecialchars($member['image_path']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>" class="team-img" loading="lazy">
                            </div>
                            <div class="team-details">
                                <h3 class="team-name"><?php echo htmlspecialchars($member['name']); ?></h3>
                                <p class="team-role"><?php echo htmlspecialchars($member['role']); ?></p>
                            </div>
                        </div>
                        <?php
                    }
                } catch (Exception $e) {
                    $allMembers = [];
                    $totalMembers = 0;
                }
                ?>
            </div>
            <div class="team-carousel-indicators" id="teamIndicators">
                <?php if ($totalMembers > 1): ?>
                    <?php for ($i = 0; $i < $totalMembers; $i++): ?>
                        <button class="team-indicator-dot <?php echo ($i === 0) ? 'is-active' : ''; ?>" data-slide="<?php echo $i; ?>" aria-label="Slide <?php echo $i + 1; ?>" type="button"></button>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>