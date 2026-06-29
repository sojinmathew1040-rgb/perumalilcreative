<!-- Office Headquarters & Interactive Map Section -->
<section class="location-section" id="location">
    <!-- Sci-fi grid decorative background for the section -->
    <div class="location-sec-grid-deco"></div>

    <div class="location-grid">
        <div class="location-info-col">
            <div class="section-label">Space</div>
            <h2 class="location-title">
                Physical <span>Headquarters</span>
            </h2>

            <!-- Floating Tech Style Switcher Panel -->
            <div class="map-hud-style-switcher">
                <span class="switcher-title">LAYER FILTERS:</span>
                <div class="map-style-toggles">
                    <button class="map-style-btn active" data-style="tactile">TACTILE SCAN</button>
                    <button class="map-style-btn" data-style="chromatic">CHROMATIC MATRIX</button>
                    <button class="map-style-btn" data-style="infrared">INFRARED SENSORS</button>
                </div>
            </div>

            <div class="location-map-frame">
                <div class="location-map-border-deco top-left"></div>
                <div class="location-map-border-deco top-right"></div>
                <div class="location-map-border-deco bottom-left"></div>
                <div class="location-map-border-deco bottom-right"></div>

                <!-- HUD Scanner Animation Line -->
                <div class="map-scanner-beam"></div>

                <!-- Premium Floating Glass HUD Info Card -->
                <div class="map-hud-card">
                    <div class="map-hud-header">
                        <span class="map-hud-tag">LOCATOR ACTIVE</span>
                        <div class="map-hud-status-dot"></div>
                    </div>
                    <h4 class="map-hud-title">Perumalil Creative</h4>
                    <p class="map-hud-address"><?php echo $settings['contact_address'] ?? 'First Floor, Kaimalayil Plaza<br>Opposite Viswajyothy College, Avoly P.O.<br>Vazhakulam, Kerala - 686670'; ?></p>
                    
                    <div class="map-hud-telemetry" style="margin-bottom: 15px;">
                        <span>GPS: <?php echo htmlspecialchars($settings['hero_coords'] ?? '9.9487° N, 76.6294° E'); ?></span>
                    </div>

                    <a href="<?php echo htmlspecialchars($settings['location_maps_link'] ?? 'https://maps.app.goo.gl/sVnxVtQPHHKY5nWz8'); ?>" target="_blank" rel="noopener"
                        class="directions-btn magnetic-btn" style="width: 100%; display: flex; justify-content: center; gap: 8px; font-size: 0.8rem; padding: 10px 16px;">
                        <span>Get Directions</span>
                        <i data-lucide="navigation" size="14"></i>
                    </a>
                </div>

                <div id="officeMap" class="office-map-container" data-lat="<?php echo htmlspecialchars($settings['map_latitude'] ?? '9.9487'); ?>" data-lng="<?php echo htmlspecialchars($settings['map_longitude'] ?? '76.6294'); ?>">
                    <!-- Leaflet map mounts here -->
                </div>
            </div>
        </div>
    </div>
</section>