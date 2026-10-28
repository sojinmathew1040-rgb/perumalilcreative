<?php
// Default Launching Soon Template Image
$defaultLaunchingSoonTpl = !empty($settings['launching_soon_template_image']) ? $settings['launching_soon_template_image'] : 'assets/images/portfolio_launching_soon.jpg';
?>
<!-- Luxury 80% Screen Launching Soon Template Display Modal (Outside Scroll Container) -->
<div id="launchingSoonModal" class="launching-soon-modal-backdrop" aria-hidden="true" role="dialog" aria-modal="true" style="display: none;">
    <div class="launching-soon-modal-card" id="launchingSoonCard">
        <!-- Ambient Blurred Glow Layer behind image -->
        <div id="lsModalAmbient" class="ls-modal-ambient" style="background-image: url('<?php echo htmlspecialchars($defaultLaunchingSoonTpl); ?>');"></div>
        <div class="ls-modal-ambient-overlay"></div>

        <!-- Top Header Bar with Project Title, Launch Badge and Close Button -->
        <div class="ls-modal-top-bar">
            <div class="ls-modal-brand-badge">
                <span class="ls-pulse-dot"></span>
                <span id="lsModalTitle" class="ls-header-title">PERUMALIL CREATIVE</span>
                <span class="ls-header-sep">&bull;</span>
                <span class="ls-header-status">LAUNCHING SOON</span>
            </div>

            <!-- Prominent Close Button on Top Right -->
            <button type="button" class="modal-close-btn" id="lsCloseBtn" aria-label="Close Display" title="Close Preview (Esc)" onclick="closeLaunchingSoonModal()">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- 80% Viewport Center Image Display (Full Template Photo) -->
        <div class="ls-modal-poster-wrap">
            <img id="lsModalImage" 
                 src="<?php echo htmlspecialchars($defaultLaunchingSoonTpl); ?>" 
                 alt="Launching Soon Official Template" 
                 class="ls-modal-poster-full" 
                 loading="eager">
        </div>

    </div>
</div>
