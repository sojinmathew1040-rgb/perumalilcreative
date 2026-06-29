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
    <div class="about-grid about-us-section"
        style="margin-top: 10vh; border-top: 1px solid var(--border-color); padding-top: 10vh;">
        <div>
            <div class="section-label"><?php echo htmlspecialchars($settings['about_us_tagline'] ?? ''); ?></div>
            <h2 class="about-title">
                <?php echo $settings['about_us_title'] ?? ''; ?>
            </h2>
            <p class="about-statement" style="font-size: 1.4rem; line-height: 1.5; margin-top: 20px; font-weight: 300;">
                <?php echo htmlspecialchars($settings['about_us_subtitle'] ?? ''); ?>
            </p>
            <p class="about-desc"
                style="color: var(--text-secondary); line-height: 1.6; margin-top: 20px; font-size: 1.05rem;">
                <?php echo htmlspecialchars($settings['about_us_desc1'] ?? ''); ?>
            </p>
            <p class="about-desc"
                style="color: var(--text-secondary); line-height: 1.6; margin-top: 15px; font-size: 1.05rem;">
                <?php echo htmlspecialchars($settings['about_us_desc2'] ?? ''); ?>
            </p>
        </div>

        <div>
            <div class="section-label">Our Leadership Team</div>
            <div class="team-grid">
                <?php
                try {
                    $teamStmt = $pdo->query("SELECT * FROM team_members ORDER BY order_index ASC");
                    while ($member = $teamStmt->fetch()) {
                        ?>
                        <div class="team-card">
                            <div class="team-img-wrap">
                                <img src="<?php echo htmlspecialchars($member['image_path']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>" class="team-img">
                            </div>
                            <div class="team-details">
                                <h3 class="team-name"><?php echo htmlspecialchars($member['name']); ?></h3>
                                <p class="team-role"><?php echo htmlspecialchars($member['role']); ?></p>
                            </div>
                        </div>
                        <?php
                    }
                } catch (Exception $e) {
                    // Fail silently
                }
                ?>
            </div>
        </div>
    </div>
</section>