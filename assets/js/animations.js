/* ==========================================================================
   PERUMALIL CREATIVE - GSAP & Motion Design Pipeline
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
    // Register GSAP ScrollTrigger plugin
    gsap.registerPlugin(ScrollTrigger);

    // 1. Initialise Smooth Scrolling with Lenis
    const lenis = new Lenis({
        duration: 1.2,
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)), // Premium exponential inertia
        direction: 'vertical',
        gestureDirection: 'vertical',
        smooth: true,
        mouseMultiplier: 1.0,
        touchMultiplier: 1.5,
        infinite: false,
    });

    // Connect Lenis to GSAP ScrollTrigger
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((time) => {
        lenis.raf(time * 1000);
    });
    gsap.ticker.lagSmoothing(0);
    window.lenis = lenis; // Expose globally

    // 2. Trailing Custom Cursor Pipeline Removed - Fallback to Default Browser Mouse

    // 3. Robust Split-Text Kinetic Typography Engine
    // Automatically parses text into overflow-masked lines and words for flawless animation performance.
    function splitTextLinesAndWords(element) {
        if (!element) return;
        const text = element.textContent.trim();
        const words = text.split(/\s+/);
        element.innerHTML = '';

        words.forEach(word => {
            const lineSpan = document.createElement('span');
            lineSpan.className = 'split-line';

            const wordSpan = document.createElement('span');
            wordSpan.className = 'split-word';
            wordSpan.textContent = word + ' ';

            lineSpan.appendChild(wordSpan);
            element.appendChild(lineSpan);
        });
    }

    function splitTextLines(element) {
        if (!element) return;
        const html = element.innerHTML;
        const lines = html.split(/<br\s*\/?>/i);
        element.innerHTML = '';

        lines.forEach(lineText => {
            const wrapper = document.createElement('span');
            wrapper.className = 'hero-title-line-wrapper';

            const lineSpan = document.createElement('span');
            lineSpan.className = 'hero-title-line';
            lineSpan.innerHTML = lineText.trim();

            wrapper.appendChild(lineSpan);
            element.appendChild(wrapper);
        });
    }

    function makeResponsiveSplitText(element) {
        if (!element) return;
        const text = element.innerText.trim();
        const words = text.split(/\s+/);
        element.innerHTML = '';

        words.forEach(word => {
            const wrapper = document.createElement('span');
            wrapper.className = 'split-word-wrapper';
            wrapper.style.display = 'inline-block';
            wrapper.style.overflow = 'hidden';
            wrapper.style.verticalAlign = 'top';

            const inner = document.createElement('span');
            inner.className = 'split-word-inner';
            inner.style.display = 'inline-block';
            inner.innerText = word;

            wrapper.appendChild(inner);
            element.appendChild(wrapper);
            element.appendChild(document.createTextNode(' ')); // Space between words
        });
    }

    // Prepare typography elements before loading completion
    const heroTitle = document.getElementById('heroTitle');

    splitTextLines(heroTitle);

    // 4. Cinematic Loading Ticker & Introduction Sequence
    const percentElement = document.getElementById('loaderPercent');
    const loaderObj = { val: 0 };

    const preloaderTimeline = gsap.timeline({
        onComplete: () => {
            // Remove preloader layer
            gsap.to('#preloader', {
                yPercent: -100,
                duration: 1.2,
                ease: 'power4.inOut',
                onComplete: () => {
                    document.body.classList.remove('loading');
                    document.getElementById('preloader').style.display = 'none';
                    // Initialize layout-wide scroll triggers after loading sequence completes
                    initScrollTriggers();
                }
            });
        }
    });

    // Percentage counter animation (0% to 100%)
    preloaderTimeline.to(loaderObj, {
        val: 100,
        duration: 2.2,
        ease: 'power2.out',
        onUpdate: () => {
            let formatted = Math.floor(loaderObj.val).toString().padStart(3, '0');
            percentElement.textContent = formatted;
        }
    });

    // Kinetic fade out loaders brand element
    preloaderTimeline.to('#loaderBrand', {
        opacity: 0,
        y: -30,
        duration: 0.5,
        ease: 'power3.in'
    }, '-=0.5');

    // 5. Section ScrollTrigger Reveal System
    function initScrollTriggers() {
        // A. Hero Elements Reveal Sequence - Immersive Grid-Breaking Canvas Concept
        const heroEl = document.getElementById('hero');
        if (heroEl) {
            heroEl.className = 'hero hero-immersive-canvas';
        }

        function runImmersiveCanvas() {
            const tl = gsap.timeline();

            // Set up initial state of elements
            gsap.set('.typo-line', { opacity: 0, y: 100, scale: 0.95 });
            gsap.set('.floating-media-card', { opacity: 0, scale: 0.85, y: 50 });
            gsap.set('.hud-location-badge', { opacity: 0, scale: 0.9 });
            gsap.set('.hero-brand-headline', { opacity: 0, y: 25 });
            gsap.set('.studio-mission-text', { opacity: 0, y: 20 });
            gsap.set('.telemetry-coordinates', { opacity: 0, y: 15 });
            gsap.set('.navbar', { y: -30, opacity: 0 });

            // Reveal Timeline
            tl.to('.navbar', { y: 0, opacity: 1, duration: 0.8, ease: 'power3.out' })
                .to('.typo-line', {
                    opacity: 1,
                    y: 0,
                    scale: 1,
                    duration: 1.2,
                    stagger: 0.18,
                    ease: 'power4.out',
                    onComplete: () => {
                        // Keep marquee typography permanently bright and crisp at 100% opacity
                        gsap.set('.typo-line', { opacity: 1 });
                    }
                }, '-=0.4')
                .to('.floating-media-card', { opacity: 1, scale: 1, y: 0, duration: 1.5, stagger: 0.15, ease: 'power3.out' }, '-=1.2')
                .to('.hud-location-badge', { opacity: 1, scale: 1, duration: 0.8, ease: 'power3.out' }, '-=1.0')
                .to('.hero-brand-headline', { opacity: 1, y: 0, duration: 0.9, ease: 'power3.out' }, '-=0.9')
                .to('.studio-mission-text', { opacity: 1, y: 0, duration: 0.8, ease: 'power3.out' }, '-=0.8')
                .to('.telemetry-coordinates', { opacity: 1, y: 0, duration: 0.8, ease: 'power3.out' }, '-=0.6');

            // 1. Cursor Spotlight Tracking Logic
            const spotlight = document.getElementById('canvasSpotlight');
            if (spotlight && heroEl) {
                const setSpotlightX = gsap.quickTo(spotlight, "left", { duration: 0.6, ease: "power3.out" });
                const setSpotlightY = gsap.quickTo(spotlight, "top", { duration: 0.6, ease: "power3.out" });

                heroEl.addEventListener('mouseenter', () => {
                    gsap.to(spotlight, { opacity: 1, duration: 0.5 });
                });

                heroEl.addEventListener('mousemove', (e) => {
                    const rect = heroEl.getBoundingClientRect();
                    const mouseX = e.clientX - rect.left;
                    const mouseY = e.clientY - rect.top;
                    setSpotlightX(mouseX);
                    setSpotlightY(mouseY);
                });

                heroEl.addEventListener('mouseleave', () => {
                    gsap.to(spotlight, { opacity: 0, duration: 0.5 });
                });
            }

            // 2. Parallax Depth Card Movements on Mousemove & Soft Float Physics
            const cards = document.querySelectorAll('.floating-media-card');

            // Soft Float Idle Animation
            cards.forEach((card, index) => {
                gsap.fromTo(card, {
                    yPercent: -4
                }, {
                    yPercent: 4,
                    duration: 4 + index * 1.5,
                    repeat: -1,
                    yoyo: true,
                    ease: 'sine.inOut'
                });
            });

            if (heroEl && cards.length > 0) {
                window.addEventListener('mousemove', (e) => {
                    const width = window.innerWidth;
                    const height = window.innerHeight;
                    const deltaX = e.clientX - width / 2;
                    const deltaY = e.clientY - height / 2;

                    cards.forEach(card => {
                        const depth = parseFloat(card.getAttribute('data-depth')) || 0.2;
                        const moveX = deltaX * depth;
                        const moveY = deltaY * depth;

                        // Subtle 3D Card Rotation based on coordinate quadrant
                        const rotX = (deltaY / height) * -10 * depth;
                        const rotY = (deltaX / width) * 10 * depth;

                        gsap.to(card, {
                            x: moveX,
                            y: moveY,
                            rotateX: rotX,
                            rotateY: rotY,
                            duration: 0.8,
                            ease: 'power2.out',
                            transformPerspective: 800
                        });
                    });
                });
            }

            // 3. Card Focus & Ripple Highlights
            cards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    cards.forEach(c => {
                        if (c !== card) {
                            c.classList.add('dimmed');
                            c.classList.remove('highlighted');
                        } else {
                            c.classList.add('highlighted');
                            c.classList.remove('dimmed');
                        }
                    });
                });

                card.addEventListener('mouseleave', () => {
                    cards.forEach(c => {
                        c.classList.remove('dimmed');
                        c.classList.remove('highlighted');
                    });
                });
            });

            // 4. Live IST Clock Telemetry Clock ticker
            const clockEl = document.getElementById('liveIstClock');
            if (clockEl) {
                function updateClock() {
                    const now = new Date();
                    // Offset IST +5:30
                    const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
                    const ist = new Date(utc + (3600000 * 5.5));

                    let hours = ist.getHours();
                    let minutes = ist.getMinutes();
                    let seconds = ist.getSeconds();

                    hours = hours.toString().padStart(2, '0');
                    minutes = minutes.toString().padStart(2, '0');
                    seconds = seconds.toString().padStart(2, '0');

                    clockEl.textContent = `${hours}:${minutes}:${seconds} IST`;
                }
                updateClock();
                setInterval(updateClock, 1000);
            }
        }

        // Initialize active luxury canvas concept
        runImmersiveCanvas();

        // B. New Intro Columns Reveal
        gsap.from('.intro-reveal-left', {
            scrollTrigger: {
                trigger: '#intro',
                start: 'top 75%'
            },
            opacity: 0,
            x: -50,
            y: 20,
            duration: 1.2,
            ease: 'power3.out'
        });

        gsap.from('.intro-reveal-right', {
            scrollTrigger: {
                trigger: '#intro',
                start: 'top 75%'
            },
            opacity: 0,
            x: 50,
            y: 20,
            duration: 1.2,
            ease: 'power3.out'
        });

        gsap.from('.intro-separator', {
            scrollTrigger: {
                trigger: '#intro',
                start: 'top 75%'
            },
            scaleY: 0,
            transformOrigin: 'top center',
            duration: 1.5,
            ease: 'power3.out'
        });

        // C. Intro Pillars Staggered Reveal
        gsap.from('.pillar-card.intro-reveal-up', {
            scrollTrigger: {
                trigger: '.intro-pillars-grid',
                start: 'top 80%'
            },
            opacity: 0,
            y: 70,
            rotationX: 10,
            scale: 0.95,
            duration: 1.2,
            stagger: 0.15,
            ease: 'power3.out',
            clearProps: 'transform'
        });

        // D. Vision Banner Expand/Reveal
        gsap.from('.vision-banner.intro-reveal-scale', {
            scrollTrigger: {
                trigger: '.vision-banner',
                start: 'top 85%'
            },
            opacity: 0,
            scale: 0.95,
            y: 40,
            duration: 1.2,
            ease: 'power3.out'
        });

        gsap.from('.vision-heading, .vision-desc, .vision-cta-wrap', {
            scrollTrigger: {
                trigger: '.vision-banner',
                start: 'top 80%'
            },
            opacity: 0,
            y: 30,
            duration: 1.0,
            stagger: 0.12,
            ease: 'power3.out',
            delay: 0.2
        });

        // E. Brand Logo Marquee scroll velocity engine & dual-track hover-pause pipeline
        const logoTrack1 = document.getElementById('logoMarqueeTrack1');
        const logoTrack2 = document.getElementById('logoMarqueeTrack2');
        const legacyLogoTrack = document.getElementById('logoMarqueeTrack');

        const activeMarqueeAnims = [];
        let isMarqueeHovered = false;

        if (logoTrack1) {
            let anim1 = gsap.to(logoTrack1, {
                xPercent: -50,
                ease: 'none',
                duration: 32, // Smooth, elegant speed
                repeat: -1
            });
            activeMarqueeAnims.push(anim1);

            logoTrack1.addEventListener('mouseenter', () => pauseAllMarquees());
            logoTrack1.addEventListener('mouseleave', () => resumeAllMarquees());
        }

        if (logoTrack2) {
            // Animate row 2 from -50% to 0% (moving rightwards)
            gsap.set(logoTrack2, { xPercent: -50 });
            let anim2 = gsap.to(logoTrack2, {
                xPercent: 0,
                ease: 'none',
                duration: 38, // Slightly different speed for visual harmony
                repeat: -1
            });
            activeMarqueeAnims.push(anim2);

            logoTrack2.addEventListener('mouseenter', () => pauseAllMarquees());
            logoTrack2.addEventListener('mouseleave', () => resumeAllMarquees());
        }

        if (legacyLogoTrack && !logoTrack1) {
            let legacyAnim = gsap.to(legacyLogoTrack, {
                xPercent: -50,
                ease: 'none',
                duration: 35,
                repeat: -1
            });
            activeMarqueeAnims.push(legacyAnim);
            legacyLogoTrack.addEventListener('mouseenter', () => pauseAllMarquees());
            legacyLogoTrack.addEventListener('mouseleave', () => resumeAllMarquees());
        }

        function pauseAllMarquees() {
            isMarqueeHovered = true;
            activeMarqueeAnims.forEach(anim => {
                gsap.to(anim, { timeScale: 0, duration: 0.7, ease: 'power2.out' });
            });
        }

        function resumeAllMarquees() {
            isMarqueeHovered = false;
            activeMarqueeAnims.forEach(anim => {
                gsap.to(anim, { timeScale: 1, duration: 0.7, ease: 'power2.out' });
            });
        }

        if (activeMarqueeAnims.length > 0) {
            // Adjust speed dynamically using ScrollTrigger based on scroll velocity (only if not hovered)
            ScrollTrigger.create({
                trigger: '#intro',
                start: 'top bottom',
                end: 'bottom top',
                onUpdate: (self) => {
                    if (isMarqueeHovered) return; // Prevent scroll overrides during active hovers
                    let velocity = Math.abs(self.getVelocity() / 150); // Normalize velocity
                    let targetScale = 1 + Math.min(velocity, 3.0); // Speed multiplier limit

                    activeMarqueeAnims.forEach(anim => {
                        gsap.to(anim, {
                            timeScale: targetScale,
                            duration: 0.4,
                            ease: 'power2.out'
                        });
                    });
                }
            });
        }

        // F1. Responsive Word-Split Reveal for Section Headings
        const headingsToSplit = document.querySelectorAll('section:not(#hero) h2, section:not(#hero) h3.intro-col-title');
        headingsToSplit.forEach(h => makeResponsiveSplitText(h));

        headingsToSplit.forEach(heading => {
            const words = heading.querySelectorAll('.split-word-inner');
            if (words.length > 0) {
                gsap.fromTo(words, {
                    yPercent: 105
                }, {
                    yPercent: 0,
                    duration: 0.8,
                    stagger: 0.04,
                    ease: 'power3.out',
                    scrollTrigger: {
                        trigger: heading,
                        start: 'top 88%',
                        toggleActions: 'play none none none',
                        once: true
                    }
                });
            }
        });

        // F2. Curtain Masking for Paragraphs, Labels, and Standalone text
        const curtainTexts = document.querySelectorAll('section:not(#hero) p, section:not(#hero) blockquote, section:not(#hero) .section-label, section:not(#hero) li');

        curtainTexts.forEach(el => {
            // Exclude elements inside animated card containers to avoid conflicts
            if (el.closest('.pillar-card') ||
                el.closest('.service-card-new') ||
                el.closest('.pricing-card') ||
                el.closest('.portfolio-stack-card') ||
                el.closest('.team-card') ||
                el.closest('.location-card') ||
                el.closest('.location-map-frame')) {
                return;
            }

            gsap.fromTo(el, {
                y: 18,
                opacity: 0
            }, {
                y: 0,
                opacity: 1,
                duration: 0.8,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: el,
                    start: 'top 88%',
                    toggleActions: 'play none none none',
                    once: true
                }
            });
        });

        // C. Services Stagger Grid ScrollTrigger
        gsap.from('.service-card-new', {
            scrollTrigger: {
                trigger: '#services',
                start: 'top 75%'
            },
            opacity: 0,
            y: 70,
            rotationX: 10,
            scale: 0.95,
            duration: 1.2,
            stagger: 0.12,
            ease: 'power3.out',
            clearProps: 'transform'
        });

        // C2. Pricing Packages Stagger ScrollTrigger
        gsap.from('.pricing-card', {
            scrollTrigger: {
                trigger: '.packages-grid',
                start: 'top 85%'
            },
            opacity: 0,
            y: 80,
            rotationX: 12,
            scale: 0.93,
            duration: 1.4,
            stagger: 0.18,
            ease: 'power4.out',
            clearProps: 'transform'
        });

        // C3. Centralized 3D Interactive Tilt & Hover Parallax Engine for Cards
        function bindLuxuryTiltEffect(selector, maxTilt = 8, parallaxFactor = 0.05) {
            const cards = document.querySelectorAll(selector);
            cards.forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    const xc = rect.width / 2;
                    const yc = rect.height / 2;
                    const dx = x - xc;
                    const dy = y - yc;

                    const tiltX = -(dy / yc) * maxTilt;
                    const tiltY = (dx / xc) * maxTilt;

                    gsap.to(card, {
                        rotationX: tiltX,
                        rotationY: tiltY,
                        transformPerspective: 1000,
                        x: dx * parallaxFactor,
                        y: dy * parallaxFactor,
                        duration: 0.35,
                        ease: 'power2.out'
                    });
                });

                card.addEventListener('mouseleave', () => {
                    gsap.to(card, {
                        rotationX: 0,
                        rotationY: 0,
                        x: 0,
                        y: 0,
                        duration: 0.8,
                        ease: 'elastic.out(1, 0.6)'
                    });
                });
            });
        }

        // D. Awwwards Stacking Cards Scrolling Effect (Clean Solitary Pinning & Fade-Out)
        let activePortfolioTriggers = [];

        function clearPortfolioTriggers() {
            activePortfolioTriggers.forEach(trigger => {
                if (trigger) {
                    if (typeof trigger.kill === 'function') {
                        trigger.kill();
                    } else if (typeof trigger.revert === 'function') {
                        trigger.revert();
                    }
                }
            });
            activePortfolioTriggers = [];
        }

        function initStackingCardsEffect(stackSelector) {
            const stack = document.querySelector(stackSelector);
            if (!stack) return;

            const cards = stack.querySelectorAll('.portfolio-stack-card');
            if (cards.length === 0) return;

            // Clear previous triggers
            clearPortfolioTriggers();

            const mm = gsap.matchMedia();

            // Desktop & Tablet (viewport >= 992px)
            mm.add("(min-width: 992px)", () => {
                const stickyTopPx = 100;

                cards.forEach((card, index) => {
                    // Position all cards to pin at the clean, consistent sticky top
                    card.style.position = 'sticky';
                    card.style.top = `${stickyTopPx}px`;
                    card.style.transformOrigin = 'center center';
                    card.style.zIndex = index + 1;

                    // Ensure crisp, solid initial state
                    gsap.set(card, {
                        scale: 1,
                        opacity: 1,
                        filter: 'blur(0px)',
                        y: 0,
                        visibility: 'visible',
                        pointerEvents: 'auto'
                    });

                    // For every card except the last: when the NEXT card scrolls up into view,
                    // smoothly fade out and scale down THIS card so it NEVER shows behind the next card!
                    if (index < cards.length - 1) {
                        const nextCard = cards[index + 1];

                        const shrinkTween = gsap.fromTo(card, 
                            {
                                scale: 1.0,
                                opacity: 1.0,
                                filter: 'blur(0px)'
                            },
                            {
                                scale: 0.94,
                                opacity: 0.0,
                                filter: 'blur(6px)',
                                ease: 'power1.out',
                                scrollTrigger: {
                                    trigger: nextCard,
                                    start: "top 80%", // As next card approaches from below
                                    end: `top ${stickyTopPx}px`, // Exactly when next card reaches sticky top
                                    scrub: 0.5,
                                    invalidateOnRefresh: true,
                                    onUpdate: (self) => {
                                        // Once covered, ensure complete cutoff so behind card is never visible
                                        if (self.progress >= 0.96) {
                                            card.style.visibility = 'hidden';
                                            card.style.pointerEvents = 'none';
                                        } else {
                                            card.style.visibility = 'visible';
                                            card.style.pointerEvents = 'auto';
                                        }
                                    }
                                }
                            }
                        );

                        if (shrinkTween.scrollTrigger) {
                            activePortfolioTriggers.push(shrinkTween.scrollTrigger);
                        }
                    }
                });
            });

            // Mobile (viewport < 992px)
            mm.add("(max-width: 991px)", () => {
                cards.forEach(card => {
                    gsap.set(card, { clearProps: "all" });
                    card.style.position = 'relative';
                    card.style.top = '0';
                    card.style.transformOrigin = 'center center';
                    card.style.opacity = '1';
                    card.style.visibility = 'visible';
                    card.style.filter = 'none';
                    card.style.pointerEvents = 'auto';
                });
            });

            // Retain the media query context for cleanup
            activePortfolioTriggers.push(mm);
        }

        // Initialize stacking card effects for initially active stack
        const initialActiveStack = document.querySelector('.portfolio-stack.active') || document.querySelector('.portfolio-stack');
        if (initialActiveStack && initialActiveStack.id) {
            initStackingCardsEffect('#' + initialActiveStack.id);
        } else {
            initStackingCardsEffect('#portfolioStackWeb');
        }

        // E. Portfolio Tab Switching Architecture (Global window handle)
        window.switchPortfolioTab = function (tabKey) {
            const activeTabBtn = document.querySelector(`.portfolio-tab-btn[data-tab="${tabKey}"]`);
            if (!activeTabBtn || activeTabBtn.classList.contains('active')) return;

            // 1. Update tab button highlights
            const tabButtons = document.querySelectorAll('.portfolio-tab-btn');
            tabButtons.forEach(btn => btn.classList.remove('active'));
            activeTabBtn.classList.add('active');

            // If user has scrolled deep into the portfolio deck, smoothly scroll up to showcase header
            const portfolioSec = document.getElementById('portfolio');
            if (portfolioSec) {
                const rect = portfolioSec.getBoundingClientRect();
                if (rect.top < 30) {
                    window.scrollTo({
                        top: portfolioSec.offsetTop - 70,
                        behavior: 'smooth'
                    });
                }
            }

            // 2. Identify stacks
            const currentStack = document.querySelector('.portfolio-stack.active');
            let targetStackId = '';
            if (tabKey === 'video') {
                targetStackId = 'portfolioStackVideo';
            } else if (tabKey === 'web') {
                targetStackId = 'portfolioStackWeb';
            } else if (tabKey === 'design') {
                targetStackId = 'portfolioStackDesign';
            } else {
                targetStackId = 'portfolioStack_' + tabKey.replace(/[^a-zA-Z0-9_]/g, '_');
            }
            const targetStack = document.getElementById(targetStackId) || document.querySelector(`.portfolio-stack[data-category="${tabKey}"]`);

            if (currentStack && targetStack) {
                // Kill and clear existing stacking triggers before transition
                clearPortfolioTriggers();

                // Fade out current stack
                gsap.to(currentStack, {
                    opacity: 0,
                    y: 20,
                    duration: 0.35,
                    ease: 'power2.in',
                    onComplete: () => {
                        currentStack.classList.remove('active');
                        currentStack.style.display = 'none';

                        // Reset all cards in targetStack to initial state
                        const targetCards = targetStack.querySelectorAll('.portfolio-stack-card');
                        targetCards.forEach(c => {
                            gsap.set(c, { clearProps: 'opacity,transform,filter,visibility' });
                            c.style.visibility = 'visible';
                            c.style.pointerEvents = 'auto';
                        });

                        // Position and activate the target stack in document flow before fading in
                        targetStack.style.display = 'block';
                        targetStack.classList.add('active');

                        // Initialize new stack triggers immediately
                        if (targetStack.id) {
                            initStackingCardsEffect('#' + targetStack.id);
                        }
                        ScrollTrigger.refresh();

                        // Re-render Lucide icons if loaded
                        if (typeof lucide !== 'undefined' && lucide.createIcons) {
                            lucide.createIcons();
                        }

                        // Fade in target stack cleanly
                        gsap.fromTo(targetStack, {
                            opacity: 0,
                            y: 20
                        }, {
                            opacity: 1,
                            y: 0,
                            duration: 0.5,
                            ease: 'power2.out'
                        });
                    }
                });
            }
        };

        // F. Luxury 80% Screen "Launching Soon" Template Display Modal Controller
        window.openLaunchingSoonModal = function (cardElem, e) {
            if (e) {
                if (typeof e.preventDefault === 'function') e.preventDefault();
                if (typeof e.stopPropagation === 'function') e.stopPropagation();
            }
            const modal = document.getElementById('launchingSoonModal');
            if (!modal) return;

            const title = cardElem ? (cardElem.getAttribute('data-title') || 'Exclusive Project') : 'Exclusive Project';
            const tag = cardElem ? (cardElem.getAttribute('data-tag') || 'Web Development') : 'Web Development';
            const desc = cardElem ? (cardElem.getAttribute('data-description') || '') : '';
            const templateImg = cardElem ? (cardElem.getAttribute('data-template-image') || cardElem.getAttribute('data-image') || 'assets/images/portfolio_launching_soon.jpg') : 'assets/images/portfolio_launching_soon.jpg';

            // Populate dynamic attributes
            const titleEl = document.getElementById('lsModalTitle');
            if (titleEl) titleEl.textContent = title;

            const tagEl = document.getElementById('lsModalTag');
            if (tagEl) tagEl.textContent = tag;

            const descEl = document.getElementById('lsModalDesc');
            if (descEl) {
                descEl.textContent = desc.trim() || 'This digital platform is currently in active engineering. Revealing the future soon.';
            }

            const imageEl = document.getElementById('lsModalImage');
            if (imageEl) {
                imageEl.src = templateImg;
                imageEl.alt = title + ' - Launching Soon';
            }

            const ambientEl = document.getElementById('lsModalAmbient');
            if (ambientEl) {
                ambientEl.style.backgroundImage = 'url("' + templateImg + '")';
            }

            // Halt smooth scroll momentum so modal remains pinned
            if (window.lenis && typeof window.lenis.stop === 'function') {
                window.lenis.stop();
            }
            document.body.style.overflow = 'hidden';

            // Reveal modal
            modal.classList.add('active');
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');

            const modalCard = modal.querySelector('.launching-soon-modal-card');

            if (typeof gsap !== 'undefined') {
                gsap.fromTo(modal, 
                    { opacity: 0 }, 
                    { opacity: 1, duration: 0.3, ease: 'power2.out' }
                );

                if (modalCard) {
                    gsap.fromTo(modalCard, 
                        { scale: 0.88, y: 30, opacity: 0 }, 
                        { scale: 1, y: 0, opacity: 1, duration: 0.4, ease: 'back.out(1.15)' }
                    );
                }
            } else {
                modal.style.opacity = '1';
                if (modalCard) {
                    modalCard.style.transform = 'scale(1) translateY(0)';
                    modalCard.style.opacity = '1';
                }
            }
        };

        window.handlePortfolioCardClick = function (cardElem, e) {
            window.openLaunchingSoonModal(cardElem, e);
        };

        window.closeLaunchingSoonModal = function () {
            const modal = document.getElementById('launchingSoonModal');
            if (!modal) return;

            // Immediately restore page scroll
            document.body.style.overflow = '';
            if (window.lenis && typeof window.lenis.start === 'function') {
                window.lenis.start();
            }

            const modalCard = modal.querySelector('.launching-soon-modal-card');
            if (modalCard && typeof gsap !== 'undefined') {
                gsap.to(modalCard, { scale: 0.94, y: 15, opacity: 0, duration: 0.22, ease: 'power2.in' });
            }

            if (typeof gsap !== 'undefined') {
                gsap.to(modal, {
                    opacity: 0,
                    duration: 0.22,
                    ease: 'power2.in',
                    onComplete: () => {
                        modal.classList.remove('active');
                        modal.style.display = 'none';
                        modal.setAttribute('aria-hidden', 'true');
                    }
                });
                setTimeout(() => {
                    modal.classList.remove('active');
                    modal.style.display = 'none';
                    modal.setAttribute('aria-hidden', 'true');
                }, 250);
            } else {
                modal.classList.remove('active');
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
            }
        };

        // Guaranteed Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                window.closeLaunchingSoonModal();
            }
        });

        // Guaranteed Backdrop & Close Button Direct Click Dispatcher
        document.addEventListener('click', (e) => {
            const modal = document.getElementById('launchingSoonModal');
            if (!modal || modal.style.display === 'none' || !modal.classList.contains('active')) return;

            if (e.target === modal || e.target.closest('#lsCloseBtn') || e.target.closest('.modal-close-btn') || e.target.closest('.ls-btn-close-text')) {
                e.preventDefault();
                e.stopPropagation();
                window.closeLaunchingSoonModal();
            }
        });

        // Delegate click handler strictly for cards with data-is-launching-soon="1"
        document.addEventListener('click', (e) => {
            const launchingCard = e.target.closest('.portfolio-stack-card');
            if (launchingCard && launchingCard.getAttribute('data-is-launching-soon') === '1' && !e.target.closest('.launching-soon-modal-card')) {
                e.preventDefault();
                e.stopPropagation();
                window.openLaunchingSoonModal(launchingCard, e);
            }
        });

        // Bind interactive 3D Tilts to intro pillars, services, portfolio stack, pricing plan cards, and brand logos
        bindLuxuryTiltEffect('.pricing-card', 8, 0.05);
        bindLuxuryTiltEffect('.pillar-card', 8, 0.05);
        bindLuxuryTiltEffect('.service-card-new', 8, 0.05);
        bindLuxuryTiltEffect('.portfolio-stack-card', 6, 0.03);
        bindLuxuryTiltEffect('.package-card', 10, 0.06);
        bindLuxuryTiltEffect('.logo-item-wrapper', 12, 0.08);
        bindLuxuryTiltEffect('.team-card', 6, 0.04);
        bindLuxuryTiltEffect('.location-card', 6, 0.03);

        // E. Team Cards Staggered Reveal
        gsap.from('.team-card', {
            scrollTrigger: {
                trigger: '.team-grid',
                start: 'top 85%'
            },
            opacity: 0,
            y: 50,
            scale: 0.95,
            stagger: 0.1,
            duration: 1.0,
            ease: 'power3.out',
            clearProps: 'transform'
        });

        // F. Location Card and Map Reveal Animations
        if (document.querySelector('.location-section')) {
            const isMobile = window.innerWidth <= 768;
            gsap.fromTo('.location-card', {
                y: isMobile ? 30 : 0,
                x: isMobile ? 0 : -50,
                opacity: 0
            }, {
                y: 0,
                x: 0,
                opacity: 1,
                duration: 1.0,
                ease: 'power3.out',
                scrollTrigger: {
                    trigger: '.location-section',
                    start: 'top 85%',
                    toggleActions: 'play none none none',
                    once: true
                }
            });

            gsap.fromTo('.location-map-frame', {
                y: isMobile ? 30 : 0,
                x: isMobile ? 0 : 50,
                opacity: 0
            }, {
                y: 0,
                x: 0,
                opacity: 1,
                duration: 1.0,
                ease: 'power3.out',
                scrollTrigger: {
                    trigger: '.location-section',
                    start: 'top 85%',
                    toggleActions: 'play none none none',
                    once: true
                }
            });
        }

        // G. Leaflet Office Map Initialization
        const mapContainer = document.getElementById('officeMap');
        if (mapContainer && typeof L !== 'undefined') {
            const lat = parseFloat(mapContainer.getAttribute('data-lat')) || 9.9487;
            const lng = parseFloat(mapContainer.getAttribute('data-lng')) || 76.6294;
            const isMobile = window.innerWidth <= 768;
            const officeCoords = [lat, lng];
            // On mobile, offset center slightly south so the marker displays cleanly above the bottom HUD card
            const displayCenter = isMobile ? [lat - 0.0016, lng] : officeCoords;

            // Initialize the map centered on Vazhakulam, Kerala
            const map = L.map('officeMap', {
                center: displayCenter,
                zoom: 15,
                scrollWheelZoom: false,
                zoomControl: false,
                dragging: true,
                tap: true
            });

            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            // Custom marker icon with divIcon for full CSS styling
            const markerIcon = L.divIcon({
                className: 'office-map-custom-marker',
                html: '<div class="office-map-marker-pulse"></div><div class="office-map-marker-pin"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            });

            // Place marker
            const marker = L.marker(officeCoords, { icon: markerIcon }).addTo(map);

            // Default filter style
            mapContainer.classList.add('filter-tactile');

            // Style toggling logic for the interactive HUD
            const styleBtns = document.querySelectorAll('.map-style-btn');
            styleBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    styleBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');

                    const style = btn.getAttribute('data-style');
                    mapContainer.classList.remove('filter-tactile', 'filter-chromatic', 'filter-infrared');

                    if (style === 'tactile') {
                        mapContainer.classList.add('filter-tactile');
                    } else if (style === 'chromatic') {
                        mapContainer.classList.add('filter-chromatic');
                    } else if (style === 'infrared') {
                        mapContainer.classList.add('filter-infrared');
                    }
                });
            });

            // Invalidate size to guarantee perfect layout rendering when section enters viewport
            ScrollTrigger.create({
                trigger: '.location-section',
                start: 'top bottom',
                onEnter: () => {
                    setTimeout(() => {
                        map.invalidateSize(true);
                    }, 250);
                }
            });
        }
    }

    // 6. Magnetic Button Physics Engine (Desktop fine pointer only)
    const canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    if (canHover) {
        const magneticBtns = document.querySelectorAll('.magnetic-btn');
        magneticBtns.forEach(btn => {
            btn.addEventListener('mousemove', (e) => {
                const bound = btn.getBoundingClientRect();

                // Mouse distance relative to button center
                const x = e.clientX - (bound.left + bound.width / 2);
                const y = e.clientY - (bound.top + bound.height / 2);

                // Premium smooth magnetic drag
                gsap.to(btn, {
                    x: x * 0.35,
                    y: y * 0.35,
                    duration: 0.3,
                    ease: 'power2.out'
                });

                // Move inside text slightly less to create 3D depth overlay
                const inner = btn.querySelector('span');
                if (inner) {
                    gsap.to(inner, {
                        x: x * 0.18,
                        y: y * 0.18,
                        duration: 0.3,
                        ease: 'power2.out'
                    });
                }
            });

            btn.addEventListener('mouseleave', () => {
                // Restore to normal coordinates smoothly
                gsap.to(btn, {
                    x: 0,
                    y: 0,
                    duration: 0.5,
                    ease: 'elastic.out(1, 0.4)'
                });

                const inner = btn.querySelector('span');
                if (inner) {
                    gsap.to(inner, {
                        x: 0,
                        y: 0,
                        duration: 0.5,
                        ease: 'elastic.out(1, 0.4)'
                    });
                }
            });
        });
    }

    // 7. Interactive Accordions (GSAP Heights Toggle)
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        const trigger = item.querySelector('.faq-trigger');
        const content = item.querySelector('.faq-content');
        const icon = item.querySelector('.faq-icon');
        let isOpen = false;

        trigger.addEventListener('click', () => {
            isOpen = !isOpen;

            // Close other open FAQ items for a pristine luxury accordion experience
            faqItems.forEach(otherItem => {
                if (otherItem !== item) {
                    const otherContent = otherItem.querySelector('.faq-content');
                    const otherIcon = otherItem.querySelector('.faq-icon');
                    gsap.to(otherContent, { maxHeight: 0, opacity: 0, duration: 0.4, ease: 'power3.out' });
                    gsap.to(otherIcon, { rotation: 0, duration: 0.4, ease: 'power3.out' });
                    otherItem.classList.remove('active');
                }
            });

            if (isOpen) {
                item.classList.add('active');
                gsap.to(content, { maxHeight: 300, opacity: 1, duration: 0.5, ease: 'power3.out' });
                gsap.to(icon, { rotation: 45, duration: 0.4, ease: 'power3.out' });
            } else {
                item.classList.remove('active');
                gsap.to(content, { maxHeight: 0, opacity: 0, duration: 0.4, ease: 'power3.out' });
                gsap.to(icon, { rotation: 0, duration: 0.4, ease: 'power3.out' });
            }
        });
    });
});
