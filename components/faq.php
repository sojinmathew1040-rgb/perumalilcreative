<!-- Frequently Asked Questions Accordion Grid -->
<section class="faq" id="faq"
    style="border-top: 1px solid var(--border-color); padding: 15vh var(--padding-side); position: relative;">
    <div style="display: grid; grid-template-columns: 0.8fr 1.2fr; gap: 80px;" class="faq-grid-responsive">
        <div>
            <div class="section-label">Faqs</div>
            <h2 style="font-size: 4rem; text-transform: uppercase; line-height: 1.0; margin-top: 20px;">Ask
                Your<br>Questions</h2>
            <p style="margin-top: 30px; max-width: 320px; color: var(--text-secondary);">
                Have a specific question about local marketing, video production, or static layout prices in Vazhakulam?
                Let's connect.
            </p>
        </div>

        <div>
            <div class="faq-accordion">
                <?php
                try {
                    $faqStmt = $pdo->query("SELECT * FROM faq_items ORDER BY order_index ASC");
                    while ($faq = $faqStmt->fetch()) {
                        ?>
                        <div class="faq-item" style="border-bottom: 1px solid var(--border-color); padding: 25px 0;">
                            <button class="faq-trigger"
                                style="width: 100%; display: flex; justify-content: space-between; align-items: center; background: none; border: none; padding: 0; color: var(--text-primary); text-align: left; cursor: pointer; gap: 20px;">
                                <span style="font-family: var(--font-heading); font-size: 1.25rem; font-weight: 700;"><?php echo htmlspecialchars($faq['question']); ?></span>
                                <span class="faq-icon" style="transition: transform 0.4s ease;"><i data-lucide="plus"
                                        size="18"></i></span>
                            </button>
                            <div class="faq-content"
                                style="max-height: 0; overflow: hidden; transition: max-height 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease; opacity: 0;">
                                <p
                                    style="padding-top: 20px; font-size: 1rem; color: var(--text-secondary); line-height: 1.6; text-align: justify; margin: 0;">
                                    <?php echo htmlspecialchars($faq['answer']); ?>
                                </p>
                            </div>
                        </div>
                        <?php
                    }
                } catch (Exception $e) {
                    // Fail silently
                }
                ?>
            </div>
        </div>
    </div>
</section>

<!-- Additional FAQ Responsive CSS styling helper -->
<style>
    @media (max-width: 991px) {
        .faq-grid-responsive {
            grid-template-columns: 1fr !important;
            gap: 50px !important;
        }
    }
</style>