<?php
// Retrieve portfolio categories dynamically
$portfolio_categories = [];
try {
    $portfolio_categories = $pdo->query("SELECT * FROM portfolio_categories ORDER BY order_index ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $portfolio_categories = [];
}
if (empty($portfolio_categories)) {
    $portfolio_categories = [
        ['slug' => 'web', 'name' => 'Web Development'],
        ['slug' => 'video', 'name' => 'Video Production'],
        ['slug' => 'design', 'name' => 'Graphic Design']
    ];
}
?>
<!-- Portfolio / Showcase Stacked Deck Section -->
<section class="portfolio" id="portfolio">
    <div class="portfolio-header">
        <div>
            <div class="section-label">Selected Works</div>
            <h2 class="services-title">Showcase</h2>
        </div>
        <p class="portfolio-header-desc">
            A collection of visual identities, cinematic short-form content, and digital architectures engineered for
            impact.
        </p>
    </div>

    <!-- Luxury Glass Tabs Selector -->
    <div class="portfolio-tabs-container">
        <div class="portfolio-tabs">
            <?php foreach ($portfolio_categories as $idx => $cat): ?>
                <button class="portfolio-tab-btn <?php echo $idx === 0 ? 'active' : ''; ?>" 
                        data-tab="<?php echo htmlspecialchars($cat['slug']); ?>" 
                        onclick="switchPortfolioTab('<?php echo htmlspecialchars($cat['slug']); ?>')">
                    <span><?php echo htmlspecialchars($cat['name']); ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Overlapping Sticky Card Stack Container -->
    <div class="portfolio-stack-container">
        <?php
        // Function to render portfolio items of a category dynamically
        if (!function_exists('renderPortfolioStack')) {
            function renderPortfolioStack($pdo, $category, $stackId, $isActive = false) {
                global $settings;
                $launchingSoonTpl = !empty($settings['launching_soon_template_image']) ? $settings['launching_soon_template_image'] : 'assets/images/portfolio_launching_soon.jpg';
                ?>
                <div class="portfolio-stack <?php echo $isActive ? 'active' : ''; ?>" id="<?php echo $stackId; ?>" data-category="<?php echo htmlspecialchars($category); ?>">
                    <?php
                    try {
                        $stmt = $pdo->prepare("SELECT * FROM portfolio_items WHERE category = ? ORDER BY order_index ASC, id ASC");
                        $stmt->execute([$category]);
                        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $total = count($items);
                        if ($total > 0) {
                            foreach ($items as $idx => $item) {
                                $isYoutube = (strpos($item['link'], 'youtube.com') !== false || strpos($item['link'], 'youtu.be') !== false);
                                
                                // Prioritize explicit database column is_launching_soon if present
                                $isLaunchingSoon = isset($item['is_launching_soon']) 
                                    ? !empty($item['is_launching_soon']) 
                                    : ((stripos($item['tag'], 'Launching Soon') !== false) || (stripos($item['title'], 'Launching Soon') !== false));
                                
                                // Clean displayed tag and title if not launching soon
                                $displayTag = $item['tag'];
                                $displayTitle = $item['title'];
                                if (!$isLaunchingSoon) {
                                    $displayTag = trim(preg_replace('/^Launching Soon\s*[\x{2022}\-\:\•\|]?\s*/iu', '', $displayTag));
                                    if (empty($displayTag)) {
                                        $displayTag = ($category === 'web') ? 'Web Architecture' : (($category === 'video') ? 'Cinematic Video' : 'Visual Design');
                                    }
                                    $displayTitle = trim(preg_replace('/^Launching Soon\s*[\x{2022}\-\:\•\|]?\s*/iu', '', $displayTitle));
                                }

                                // Clean and normalize link
                                $itemLink = trim($item['link'] ?? '');
                                if (!$isLaunchingSoon && !empty($itemLink) && !preg_match('#^https?://#i', $itemLink) && $itemLink !== '#' && strpos($itemLink, 'javascript:') !== 0) {
                                    $itemLink = 'https://' . ltrim($itemLink, '/');
                                }

                                // Visual image path - if live but still has launching soon placeholder, auto-fallback to dedicated screenshot
                                $displayImage = $item['image_path'];
                                if (!$isLaunchingSoon && (empty($displayImage) || strpos($displayImage, 'portfolio_launching_soon') !== false || strpos($displayImage, 'launching_soon_template') !== false)) {
                                    $cleanSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $displayTitle));
                                    $candidates = [
                                        'assets/images/portfolio_' . $cleanSlug . '.jpg',
                                        'assets/images/portfolio_' . $cleanSlug . '.png',
                                        'assets/images/portfolio_' . $cleanSlug . '_com.jpg',
                                        'assets/images/portfolio_' . $cleanSlug . '_in.jpg'
                                    ];
                                    foreach ($candidates as $cand) {
                                        if (file_exists(dirname(__DIR__) . '/' . $cand)) {
                                            $displayImage = $cand;
                                            break;
                                        }
                                    }
                                }
                                $posterModalImage = $isLaunchingSoon ? $launchingSoonTpl : $displayImage;
                                ?>
                                <a href="<?php echo $isLaunchingSoon ? 'javascript:void(0);' : htmlspecialchars($itemLink); ?>" 
                                   <?php echo $isLaunchingSoon ? 'role="button" aria-haspopup="dialog" style="cursor: pointer;"' : 'target="_blank" rel="noopener"'; ?> 
                                   class="portfolio-stack-card <?php echo $isLaunchingSoon ? 'is-launching-soon-card' : ''; ?>" 
                                   data-index="<?php echo $idx; ?>"
                                   data-is-launching-soon="<?php echo $isLaunchingSoon ? '1' : '0'; ?>"
                                   data-title="<?php echo htmlspecialchars($displayTitle); ?>"
                                   data-tag="<?php echo htmlspecialchars($displayTag); ?>"
                                   data-description="<?php echo htmlspecialchars($item['description']); ?>"
                                   data-link="<?php echo htmlspecialchars($itemLink); ?>"
                                   data-image="<?php echo htmlspecialchars($posterModalImage); ?>"
                                   data-template-image="<?php echo htmlspecialchars($launchingSoonTpl); ?>"
                                   <?php if ($isLaunchingSoon): ?>onclick="openLaunchingSoonModal(this, event); return false;"<?php endif; ?>>
                                    <div class="card-glass-glow"></div>
                                    <div class="portfolio-stack-details">
                                        <div class="portfolio-stack-meta">
                                            <div class="portfolio-stack-tag">
                                                <span class="tag-dot"></span>
                                                <?php echo htmlspecialchars($displayTag); ?>
                                            </div>
                                            <div class="portfolio-stack-counter">
                                                <span class="counter-curr"><?php echo sprintf('%02d', $idx + 1); ?></span>
                                                <span class="counter-sep">/</span>
                                                <span class="counter-total"><?php echo sprintf('%02d', $total); ?></span>
                                            </div>
                                        </div>
                                        <h3 class="portfolio-stack-title"><?php echo htmlspecialchars($displayTitle); ?></h3>
                                        <p class="portfolio-stack-desc"><?php echo htmlspecialchars($item['description']); ?></p>
                                        <div class="portfolio-stack-action">
                                            <?php if ($isLaunchingSoon): ?>
                                                <span class="portfolio-launch-badge">Preview Status &bull; Launching Soon</span>
                                                <i data-lucide="sparkles" class="stack-action-icon portfolio-launch-badge"></i>
                                            <?php else: ?>
                                                <span><?php echo $category === 'web' ? 'Visit Live Platform' : ($isYoutube ? 'Watch on YouTube' : 'View Project Showcase'); ?></span>
                                                <i data-lucide="<?php echo $category === 'web' ? 'globe' : ($isYoutube ? 'youtube' : 'arrow-up-right'); ?>" class="stack-action-icon"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="portfolio-stack-img-wrap">
                                        <?php if ($category === 'web'): ?>
                                            <div class="web-browser-bar">
                                                <div class="browser-dots-group">
                                                    <span class="browser-dot red"></span>
                                                    <span class="browser-dot yellow"></span>
                                                    <span class="browser-dot green"></span>
                                                </div>
                                                <div class="browser-address">
                                                    <svg class="browser-lock-icon" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                                    <span><?php 
                                                        $hostDisplay = parse_url($itemLink, PHP_URL_HOST);
                                                        echo htmlspecialchars($hostDisplay ?: preg_replace('#^https?://#i', '', $itemLink)); 
                                                    ?></span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <img src="<?php echo htmlspecialchars($displayImage); ?>" alt="<?php echo htmlspecialchars($displayTitle); ?>"
                                            class="portfolio-stack-img" loading="lazy">
                                        <?php if ($isLaunchingSoon): ?>
                                            <div class="launching-soon-badge-overlay">
                                                <span>LAUNCHING SOON</span>
                                            </div>
                                        <?php elseif ($isYoutube): ?>
                                            <div class="video-play-btn-overlay">
                                                <i data-lucide="play" size="24"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <?php
                            }
                        } else {
                            ?>
                            <div class="portfolio-empty-state">
                                <i data-lucide="folder" class="portfolio-empty-icon"></i>
                                <p>Projects in this category will be showcased here soon.</p>
                            </div>
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

        // Render each category deck dynamically
        foreach ($portfolio_categories as $idx => $cat) {
            $stackId = 'portfolioStack_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $cat['slug']);
            if ($cat['slug'] === 'video') $stackId = 'portfolioStackVideo';
            elseif ($cat['slug'] === 'web') $stackId = 'portfolioStackWeb';
            elseif ($cat['slug'] === 'design') $stackId = 'portfolioStackDesign';

            renderPortfolioStack($pdo, $cat['slug'], $stackId, $idx === 0);
        }
        ?>
    </div>
</section>