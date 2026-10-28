<?php
// Retrieve Dynamic Phone Number for Floating WhatsApp Action
$floatingRawPhone = $settings['contact_phone1'] ?? '919946020716';
$floatingWaPhone = preg_replace('/[^0-9]/', '', $floatingRawPhone);
if (strlen($floatingWaPhone) === 10) {
    $floatingWaPhone = '91' . $floatingWaPhone;
}
if (empty($floatingWaPhone)) {
    $floatingWaPhone = '919946020716';
}
?>
<!-- Floating Action Controls: Scroll To Top Arrow & WhatsApp Direct Action -->
<div class="floating-fab-container" id="floatingFabContainer" aria-label="Floating Shortcuts">
    <!-- Floating Scroll-To-Top Arrow Button -->
    <button type="button" class="floating-fab-btn fab-scroll-top" id="fabScrollTop" onclick="scrollToTop()" aria-label="Scroll to Top" title="Scroll to Top">
        <svg class="fab-arrow-icon" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="19" x2="12" y2="5"></line>
            <polyline points="5 12 12 5 19 12"></polyline>
        </svg>
    </button>

    <!-- Floating WhatsApp Action Button -->
    <a href="https://wa.me/<?php echo htmlspecialchars($floatingWaPhone); ?>" target="_blank" rel="noopener" class="floating-fab-btn fab-whatsapp" id="fabWhatsApp" aria-label="Chat on WhatsApp" title="Chat on WhatsApp">
        <span class="fab-pulse-ring"></span>
        <svg class="fab-wa-icon" viewBox="0 0 24 24" width="26" height="26" fill="currentColor">
            <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.04 14.69 2 12.04 2ZM12.05 3.67C14.25 3.67 16.31 4.53 17.87 6.09C19.42 7.65 20.28 9.72 20.28 11.92C20.28 16.46 16.58 20.16 12.05 20.16C10.64 20.16 9.25 19.8 8.03 19.11L7.68 18.9L4.57 19.72L5.4 16.69L5.17 16.32C4.42 15.02 4.02 13.52 4.02 11.91C4.02 7.37 7.72 3.67 12.05 3.67ZM8.53 7.33C8.37 7.33 8.1 7.39 7.87 7.64C7.65 7.89 7.02 8.48 7.02 9.7C7.02 10.92 7.91 12.09 8.03 12.25C8.16 12.41 9.77 14.9 12.24 15.96C12.83 16.21 13.28 16.36 13.64 16.48C14.23 16.67 14.77 16.64 15.2 16.58C15.67 16.51 16.66 15.98 16.87 15.4C17.07 14.81 17.07 14.31 17.01 14.21C16.95 14.11 16.79 14.05 16.48 13.9C16.17 13.75 14.65 13 14.37 12.9C14.09 12.8 13.89 12.75 13.69 13.05C13.48 13.36 12.91 14.05 12.73 14.25C12.56 14.45 12.38 14.48 12.07 14.32C11.77 14.17 10.79 13.85 9.62 12.81C8.71 12 8.1 11 7.92 10.7C7.74 10.39 7.9 10.23 8.06 10.07C8.2 9.93 8.37 9.71 8.53 9.53C8.68 9.35 8.73 9.22 8.83 9.02C8.93 8.81 8.88 8.64 8.8 8.48C8.73 8.33 8.12 6.84 7.87 6.23C7.62 5.64 7.37 5.72 7.18 5.71C7 5.7 6.79 5.7 6.58 5.7"/>
        </svg>
        <span class="fab-label-tooltip">Chat with Us</span>
    </a>
</div>
