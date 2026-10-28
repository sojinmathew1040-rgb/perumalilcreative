<?php include 'includes/header.php'; ?>

<!-- Hero Block -->
<?php include 'components/hero.php'; ?>

<!-- Editorial Intro -->
<?php include 'components/intro.php'; ?>

<!-- Services Pillars Grid -->
<?php include 'components/services.php'; ?>

<!-- Museum Portfolio Showcase -->
<?php include 'components/portfolio.php'; ?>

<!-- MD Leadership Quote Spotlight Section -->
<?php include 'components/testimonials.php'; ?>


<!-- About & Creative Philosophy -->
<?php include 'components/about.php'; ?>

<!-- Interactive FAQ Accordions -->
<?php include 'components/faq.php'; ?>

<!-- Interactive Office Headquarters Map Section -->
<?php include 'components/location.php'; ?>

<!-- Interactive Inquiries / Pricing Calculator -->
<?php include 'components/contact.php'; ?>

<!-- Architectural Editorial Footer -->
<?php include 'components/footer.php'; ?>

</div>

<!-- Luxury 80% Screen Launching Soon Display Modal -->
<?php include 'components/launching_soon_modal.php'; ?>

<!-- Floating Shortcuts: Scroll to Top Arrow & WhatsApp FAB -->
<?php include 'components/floating_actions.php'; ?>

<!-- Studio AI Assistant Animated Companion (Nova) -->
<?php include 'components/ai_assistant.php'; ?>

<!-- Core Script Pipelines -->
<script src="assets/js/webgl.js"></script>
<script src="assets/js/animations.js?v=<?php echo @filemtime(__DIR__ . '/assets/js/animations.js') ?: time(); ?>"></script>
<script src="assets/js/main.js?v=<?php echo @filemtime(__DIR__ . '/assets/js/main.js') ?: time(); ?>"></script>

</body>

</html>