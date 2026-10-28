<!-- Leadership Spotlight Section -->
<section class="w3l-clients py-5 testimonials-section" id="testimonials">
    <div class="section-label">Leadership Statement</div>

    <div class="testimonials-grid">
        <div class="testimonials-quote-mark">&ldquo;</div>

        <div>
            <blockquote class="testimonials-quote-text">
                <?php echo htmlspecialchars($settings['testimonial_quote'] ?? ''); ?>
            </blockquote>

            <div class="testimonials-author-row">
                <div class="testimonials-dot"></div>
                <div>
                    <h3 class="testimonials-author-name">
                        <?php echo htmlspecialchars($settings['testimonial_author'] ?? ''); ?></h3>
                    <p class="testimonials-author-role">
                        <?php echo htmlspecialchars($settings['testimonial_role'] ?? ''); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>