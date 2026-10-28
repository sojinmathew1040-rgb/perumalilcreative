<!-- Frequently Asked Questions Accordion Grid -->
<section class="faq faq-section-wrapper" id="faq">
    <div class="faq-grid-responsive">
        <div>
            <div class="section-label">Faqs</div>
            <h2 class="faq-title-heading">Ask Your<br>Questions</h2>
            <p class="faq-subtitle-text">
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
                        <div class="faq-item">
                            <button class="faq-trigger">
                                <span class="faq-question-label"><?php echo htmlspecialchars($faq['question']); ?></span>
                                <span class="faq-icon"><i data-lucide="plus" size="18"></i></span>
                            </button>
                            <div class="faq-content">
                                <p class="faq-answer-paragraph">
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