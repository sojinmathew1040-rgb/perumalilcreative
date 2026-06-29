<!-- Portfolio / Showcase Stacked Deck Section -->
<section class="portfolio" id="portfolio">
    <div class="portfolio-header">
        <div>
            <div class="section-label">Selected Works</div>
            <h2 class="services-title">Showcase</h2>
        </div>
        <p style="max-width: 380px; color: var(--text-secondary); line-height: 1.6;">
            A collection of visual identities, cinematic short-form content, and digital architectures engineered for
            impact.
        </p>
    </div>

    <!-- Luxury Glass Tabs Selector -->
    <div class="portfolio-tabs-container">
        <div class="portfolio-tabs">
            <button class="portfolio-tab-btn active" data-tab="video" onclick="switchPortfolioTab('video')">
                <span>Video Production</span>
            </button>
            <button class="portfolio-tab-btn" data-tab="web" onclick="switchPortfolioTab('web')">
                <span>Web Development</span>
            </button>
            <button class="portfolio-tab-btn" data-tab="design" onclick="switchPortfolioTab('design')">
                <span>Graphic Design</span>
            </button>
        </div>
    </div>

    <!-- Overlapping Sticky Card Stack Container -->
    <div class="portfolio-stack-container">
        <?php
        // Function to render portfolio items of a category dynamically
        if (!function_exists('renderPortfolioStack')) {
            function renderPortfolioStack($pdo, $category, $stackId, $isActive = false) {
                ?>
                <div class="portfolio-stack <?php echo $isActive ? 'active' : ''; ?>" id="<?php echo $stackId; ?>" <?php echo $isActive ? '' : 'style="display: none;"'; ?>>
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT * FROM portfolio_items WHERE category = ? ORDER BY order_index ASC");
                        $stmt->execute([$category]);
                        while ($item = $stmt->fetch()) {
                            $isYoutube = (strpos($item['link'], 'youtube.com') !== false || strpos($item['link'], 'youtu.be') !== false);
                            ?>
                            <a href="<?php echo htmlspecialchars($item['link']); ?>" target="_blank" rel="noopener" class="portfolio-stack-card">
                                <div class="card-glass-glow"
                                    style="background: radial-gradient(circle, rgba(0, <?php echo $category === 'web' ? '255, 170' : ($category === 'design' ? '170, 0' : '102, 255'); ?>, 0.08) 0%, transparent 70%);"></div>
                                <div class="portfolio-stack-details">
                                    <div class="portfolio-stack-tag">
                                        <span class="tag-dot" <?php echo $category === 'web' ? 'style="background-color: #00ffaa; box-shadow: 0 0 8px #00ffaa;"' : ($category === 'design' ? 'style="background-color: #ffaa00; box-shadow: 0 0 8px #ffaa00;"' : ''); ?>></span>
                                        <?php echo htmlspecialchars($item['tag']); ?>
                                    </div>
                                    <h3 class="portfolio-stack-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                                    <p class="portfolio-stack-desc"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <div class="portfolio-stack-action">
                                        <span><?php echo $category === 'web' ? 'Visit Site' : ($isYoutube ? 'Watch on YouTube' : 'View Project'); ?></span>
                                        <i data-lucide="<?php echo $category === 'web' ? 'globe' : ($isYoutube ? 'youtube' : 'arrow-up-right'); ?>" class="stack-action-icon"></i>
                                    </div>
                                </div>
                                <div class="portfolio-stack-img-wrap">
                                    <?php if ($category === 'web'): ?>
                                        <div class="web-browser-bar">
                                            <span class="browser-dot red"></span>
                                            <span class="browser-dot yellow"></span>
                                            <span class="browser-dot green"></span>
                                            <span class="browser-address"><?php echo htmlspecialchars(parse_url($item['link'], PHP_URL_HOST) ?: $item['link']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>"
                                        class="portfolio-stack-img">
                                    <?php if ($isYoutube): ?>
                                        <div class="video-play-btn-overlay">
                                            <i data-lucide="play" size="24"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <?php
                        }
                    } catch (Exception $e) {
                        // Fail silently
                    }
                    ?>
                </div>
                <?php
            }
        }

        // Render the three category decks dynamically
        renderPortfolioStack($pdo, 'video', 'portfolioStackVideo', true);
        renderPortfolioStack($pdo, 'web', 'portfolioStackWeb', false);
        renderPortfolioStack($pdo, 'design', 'portfolioStackDesign', false);
        ?>
    </div>
</section>