<!-- Services & Packages Section -->
<section class="services" id="services">
    <div class="services-header">
        <div class="section-label">What We Offer</div>
        <h2 class="services-title">Our Services <span class="text-glow">at Perumalil Creative</span></h2>
        <p class="services-desc">
            We provide a wide range of services including SEO, digital marketing, advertising, website design, and
            graphic design. Our goal is to build strong brand identity and increase your business visibility.
        </p>
    </div>

    <!-- 3-Column Services Grid -->
    <div class="services-grid-new">
        <?php
        try {
            $serviceStmt = $pdo->query("SELECT * FROM services ORDER BY order_index ASC");
            while ($service = $serviceStmt->fetch()) {
                ?>
                <div class="service-card-new intro-reveal-up" data-delay="<?php echo htmlspecialchars($service['delay']); ?>">
                    <div class="service-card-new-icon-wrap">
                        <i data-lucide="<?php echo htmlspecialchars($service['icon']); ?>" class="service-card-new-icon"></i>
                    </div>
                    <h3 class="service-card-new-title"><?php echo htmlspecialchars($service['title']); ?></h3>
                    <p class="service-card-new-desc">
                        <?php echo htmlspecialchars($service['description']); ?>
                    </p>
                </div>
                <?php
            }
        } catch (Exception $e) {
            // Fail silently
        }
        ?>
    </div>

    <!-- Pricing Section Header -->
    <div class="packages-header">
        <span class="intro-tagline services-pricing-tagline">Pricing Plans</span>
        <h2 class="packages-title">Simple, Transparent <span class="text-glow">Pricing</span></h2>
    </div>

    <!-- 4-Column Packages Grid -->
    <div class="packages-grid">
        <?php
        try {
            $packageStmt = $pdo->query("SELECT * FROM pricing_packages ORDER BY order_index ASC");
            while ($package = $packageStmt->fetch()) {
                $features = explode("\n", str_replace("\r", "", $package['features']));
                ?>
                <div class="pricing-card <?php echo $package['is_popular'] ? 'popular' : ''; ?> intro-reveal-up" data-delay="<?php echo htmlspecialchars($package['delay']); ?>">
                    <?php if ($package['is_popular']): ?>
                        <span class="popular-badge">Most Popular</span>
                    <?php endif; ?>
                    <div class="pricing-card-top">
                        <h3 class="pricing-card-name"><?php echo htmlspecialchars($package['name']); ?></h3>
                        <div class="pricing-card-price-label"><?php echo htmlspecialchars($package['price_label']); ?></div>
                        <?php
                        $cleanPrice = str_ireplace(['&#8377;', '₹', 'rs.', 'inr'], '', $package['price']);
                        $cleanPrice = trim($cleanPrice);
                        ?>
                        <div class="pricing-card-price">&#8377;<?php echo htmlspecialchars($cleanPrice); ?></div>
                        <ul class="pricing-card-features">
                            <?php foreach ($features as $feature): 
                                if (trim($feature) === '') continue; 
                                $renderedFeature = html_entity_decode($feature, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            ?>
                                <li><i data-lucide="check" class="feature-check-icon"></i><?php echo htmlspecialchars($renderedFeature, ENT_NOQUOTES, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="pricing-card-action">
                        <a href="#contact" class="pricing-card-btn <?php echo $package['is_popular'] ? 'solid' : 'outline'; ?>">Get Quote</a>
                    </div>
                </div>
                <?php
            }
        } catch (Exception $e) {
            // Fail silently
        }
        ?>
    </div>
</section>