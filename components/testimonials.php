<!-- Leadership Spotlight Section -->
<section class="w3l-clients py-5" id="testimonials"
    style="border-top: 1px solid var(--border-color); padding: 12vh var(--padding-side); position: relative;">
    <div class="section-label">Leadership Statement</div>

    <div
        style="display: grid; grid-template-columns: 0.3fr 1.7fr; gap: 40px; align-items: flex-start; margin-top: 50px;">
        <div
            style="font-size: 6rem; line-height: 1; font-family: var(--font-heading); color: var(--accent); opacity: 0.8; font-weight: 800;">
            &ldquo;</div>

        <div>
            <blockquote
                style="font-family: var(--font-editorial); font-size: 2.2rem; line-height: 1.4; color: var(--text-primary); font-weight: 300; text-align: justify; border: none; padding: 0; margin: 0;">
                <?php echo htmlspecialchars($settings['testimonial_quote'] ?? ''); ?>
            </blockquote>

            <div
                style="margin-top: 40px; padding-top: 30px; border-top: 1px solid var(--border-color); display: flex; align-items: center; gap: 20px;">
                <div style="width: 12px; height: 12px; background-color: var(--accent); border-radius: 50%;"></div>
                <div>
                    <h3
                        style="font-family: var(--font-heading); font-size: 1.4rem; letter-spacing: -0.5px; text-transform: uppercase; margin: 0;">
                        <?php echo htmlspecialchars($settings['testimonial_author'] ?? ''); ?></h3>
                    <p
                        style="font-family: var(--font-editorial); font-size: 0.95rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-top: 4px;">
                        <?php echo htmlspecialchars($settings['testimonial_role'] ?? ''); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>