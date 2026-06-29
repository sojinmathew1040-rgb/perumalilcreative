<!-- Interactive Inquiries Section -->
<section class="contact" id="contact">
    <div class="contact-wrapper">
        <div>
            <div class="section-label">Get In Touch</div>
                        <h2 class="contact-headline"><?php echo $settings['contact_headline'] ?? 'Let\'s build<br>something<br>enduring'; ?></h2>

            <p style="margin-top: 30px; max-width: 320px;">
                <?php echo htmlspecialchars($settings['contact_desc'] ?? 'Ready to elevate your digital footprint? Send us your blueprint or select an agency package below.'); ?>
            </p>

            <div class="contact-info-list">
                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i data-lucide="phone" size="18"></i>
                    </div>
                    <div class="contact-info-text">
                        <h4>Direct Helplines</h4>
                        <p>
                            <?php echo htmlspecialchars($settings['contact_phone1'] ?? '+91 99460 20716'); ?><br>
                            <?php echo htmlspecialchars($settings['contact_phone2'] ?? '+91 99460 20724'); ?>
                        </p>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i data-lucide="mail" size="18"></i>
                    </div>
                    <div class="contact-info-text">
                        <h4>Electronic Mail</h4>
                        <p><?php echo htmlspecialchars($settings['contact_email'] ?? 'hello@perumalilcreative.com'); ?></p>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i data-lucide="map-pin" size="18"></i>
                    </div>
                    <div class="contact-info-text">
                        <h4>Headquarters</h4>
                        <p><?php echo $settings['contact_address'] ?? 'First Floor, Kaimalayil Plaza<br>Opposite Viswajyothy College, Avoly P.O.<br>Vazhakulam, Kerala - 686670'; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <!-- Functional Inquiries Form -->
            <form id="inquiryForm" class="contact-form" onsubmit="handleFormSubmit(event)">
                <div class="form-row">
                    <div class="form-group">
                        <label for="clientName">Your Name</label>
                        <input type="text" id="clientName" class="form-control" placeholder="E.g. Jithin Joseph"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="clientEmail">Email Address</label>
                        <input type="email" id="clientEmail" class="form-control" placeholder="E.g. jithin@gmail.com"
                            required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Select a Specialized Agency Package</label>
                    <div class="package-selector">
                        <?php
                        $firstPkgName = 'Single Reel Launch Package';
                        $firstPkgDesc = 'Includes professional camera shoot in Vazhakulam, cinematic color correction, and sound design.';
                        $firstPkgPrice = '3999';
                        $firstPkgSlug = 'reel';
                        
                        try {
                            $pkgStmt = $pdo->query("SELECT * FROM pricing_packages ORDER BY order_index ASC");
                            $packagesList = $pkgStmt->fetchAll();
                            
                            if (!empty($packagesList)) {
                                $isFirst = true;
                                foreach ($packagesList as $pkg) {
                                    $pkgSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $pkg['name']));
                                    // Clean price to get numeric value
                                    $numericPrice = preg_replace('/[^0-9]/', '', $pkg['price']);
                                    
                                    $featuresArr = explode("\n", $pkg['features']);
                                    $firstFeature = !empty($featuresArr[0]) ? trim($featuresArr[0]) : 'Professional custom package solution.';
                                    
                                    if ($isFirst) {
                                        $firstPkgName = $pkg['name'];
                                        $firstPkgDesc = $firstFeature;
                                        $firstPkgPrice = $numericPrice;
                                        $firstPkgSlug = $pkgSlug;
                                    }
                                    ?>
                                    <div class="package-card <?php echo $isFirst ? 'active' : ''; ?>" 
                                         data-package="<?php echo htmlspecialchars($pkgSlug); ?>" 
                                         data-price="<?php echo htmlspecialchars($numericPrice); ?>"
                                         data-name="<?php echo htmlspecialchars($pkg['name']); ?>"
                                         data-desc="<?php echo htmlspecialchars($firstFeature); ?>"
                                         onclick="selectPackage(this)">
                                        <div class="package-name"><?php echo htmlspecialchars($pkg['name']); ?></div>
                                        <div class="package-price">&#8377;<?php echo htmlspecialchars($pkg['price']); ?></div>
                                    </div>
                                    <?php
                                    $isFirst = false;
                                }
                            } else {
                                ?>
                                <div class="package-card active" data-package="reel" data-price="3999" data-name="Single Reel Promo" data-desc="Includes professional camera shoot in Vazhakulam, cinematic color correction, and sound design." onclick="selectPackage(this)">
                                    <div class="package-name">Single Reel Promo</div>
                                    <div class="package-price">&#8377;3,999</div>
                                </div>
                                <?php
                            }
                        } catch (Exception $e) {
                            ?>
                            <div class="package-card active" data-package="reel" data-price="3999" data-name="Single Reel Promo" data-desc="Includes professional camera shoot in Vazhakulam, cinematic color correction, and sound design." onclick="selectPackage(this)">
                                <div class="package-name">Single Reel Promo</div>
                                <div class="package-price">&#8377;3,999</div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>

                <!-- Dynamic Interactive Calculator Preview -->
                <div class="dynamic-calculator-preview">
                    <div class="calc-details">
                        <h5 id="selectedPackageName"><?php echo htmlspecialchars($firstPkgName); ?></h5>
                        <p id="selectedPackageDesc"><?php echo htmlspecialchars($firstPkgDesc); ?></p>
                    </div>
                    <div class="calc-price">
                        <span id="calculatedPrice">&#8377;<?php echo number_format((float)$firstPkgPrice); ?></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="clientMessage">Describe Your Creative Goals</label>
                    <textarea id="clientMessage" class="form-control"
                        placeholder="Outline your vision, event context, or marketing challenges..."
                        required></textarea>
                </div>

                <!-- Submission Error Alert Banner -->
                <div id="formError" style="display: none; align-items: center; gap: 8px; color: #ef4444; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;">
                    <i data-lucide="alert-octagon" size="18"></i>
                    <span class="error-msg"></span>
                </div>

                <button type="submit" class="submit-btn magnetic-btn">
                    <span>Send Creative Blueprint</span>
                    <i data-lucide="arrow-right" size="18"></i>
                </button>
            </form>

            <!-- Success Confirmation Screen -->
            <div id="successOverlay" class="form-success-overlay">
                <div class="success-icon">
                    <i data-lucide="check" size="32"></i>
                </div>
                <h3 style="font-family: var(--font-heading); font-size: 2rem;">Blueprint Initiated</h3>
                <p style="max-width: 400px; color: var(--text-secondary);">
                    Thank you! Our creative director will analyze your package preferences and reach out within 12
                    hours.
                </p>
                <button onclick="resetForm()" class="magnetic-btn" style="margin-top: 20px;">
                    <span>Submit Another Inquiry</span>
                </button>
            </div>
        </div>
    </div>
</section>