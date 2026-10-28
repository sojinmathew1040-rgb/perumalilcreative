/* ==========================================================================
   PERUMALIL CREATIVE - Core Interactive UI Functions
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
    // Initialize Lucide SVG Icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // 1. Header/Navbar Visual Scroll Pipeline
    const navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // 2. High-Precision ScrollSpy & Active Header Indicator System
    const sections = Array.from(document.querySelectorAll('section[id]'));
    const desktopLinks = document.querySelectorAll('.nav-link');
    const mobileLinks = document.querySelectorAll('.mobile-nav-link');

    // Section ID to Navigation Target ID mapping for smooth coverage
    const sectionNavMap = {
        'hero': 'hero',
        'intro': 'hero',         // Editorial intro falls under Home umbrella
        'services': 'services',
        'portfolio': 'portfolio',
        'testimonials': 'about', // Testimonials spotlight falls under About umbrella
        'about': 'about',
        'faq': 'faq',
        'location': 'contact',   // Location map falls under Contact umbrella
        'contact': 'contact'
    };

    function updateActiveNavState() {
        const scrollPosition = window.scrollY || window.pageYOffset;
        const viewportHeight = window.innerHeight;
        const totalHeight = document.documentElement.scrollHeight;
        let activeTargetId = 'hero';

        // Check if near bottom of page -> activate contact link
        if (viewportHeight + scrollPosition >= totalHeight - 60) {
            activeTargetId = 'contact';
        } else if (scrollPosition < 80) {
            activeTargetId = 'hero';
        } else {
            // Find section closest to reading focus line (30% from viewport top)
            const focusLine = scrollPosition + (viewportHeight * 0.3);

            for (let i = 0; i < sections.length; i++) {
                const section = sections[i];
                const sectionTop = section.offsetTop;
                const sectionBottom = sectionTop + section.offsetHeight;

                if (focusLine >= sectionTop && focusLine < sectionBottom) {
                    const secId = section.getAttribute('id');
                    activeTargetId = sectionNavMap[secId] || secId;
                    break;
                }
            }
        }

        // Apply active class to desktop links
        desktopLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === `#${activeTargetId}`) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        // Apply active class to mobile links
        mobileLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === `#${activeTargetId}`) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }

    // 2a. Floating Scroll-To-Top Button Visibility Toggle
    function updateFabScrollTopVisibility() {
        const fabScrollTop = document.getElementById('fabScrollTop');
        if (!fabScrollTop) return;
        const scrollY = window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0;
        if (scrollY > 280) {
            fabScrollTop.classList.add('visible');
        } else {
            fabScrollTop.classList.remove('visible');
        }
    }

    function onUnifiedScroll() {
        updateActiveNavState();
        updateFabScrollTopVisibility();
    }

    // Attach listener to window scroll and Lenis scroll ticker
    window.addEventListener('scroll', onUnifiedScroll, { passive: true });
    if (window.lenis) {
        window.lenis.on('scroll', onUnifiedScroll);
    } else {
        const checkLenisTimer = setInterval(() => {
            if (window.lenis) {
                window.lenis.on('scroll', onUnifiedScroll);
                clearInterval(checkLenisTimer);
            }
        }, 300);
        setTimeout(() => clearInterval(checkLenisTimer), 5000);
    }

    // Initial state check
    onUnifiedScroll();

    // 2b. Smooth Anchor Click Handler using Lenis Engine
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (!targetId || targetId === '#') return;
            const targetEl = document.querySelector(targetId);
            if (targetEl) {
                e.preventDefault();
                closeMenu();
                const navHeight = window.innerWidth <= 991 ? 68 : 80;
                if (window.lenis) {
                    window.lenis.scrollTo(targetEl, { duration: 1.2, offset: -navHeight });
                } else {
                    const topPos = targetEl.getBoundingClientRect().top + window.pageYOffset - navHeight;
                    window.scrollTo({ top: topPos, behavior: 'smooth' });
                }
                const cleanId = targetId.replace('#', '');
                const mappedId = sectionNavMap[cleanId] || cleanId;
                desktopLinks.forEach(l => l.classList.toggle('active', l.getAttribute('href') === `#${mappedId}`));
                mobileLinks.forEach(l => l.classList.toggle('active', l.getAttribute('href') === `#${mappedId}`));
            }
        });
    });

    // 3. Mobile Leadership Team In-Place Spotlight Disappearing & Appearing Loop
    const teamGrid = document.getElementById('teamGrid');
    if (teamGrid) {
        const cards = Array.from(teamGrid.querySelectorAll('.team-card'));
        const counterEl = document.getElementById('teamCounter');
        const prevBtn = document.getElementById('teamPrevBtn');
        const nextBtn = document.getElementById('teamNextBtn');
        const dots = Array.from(document.querySelectorAll('.team-indicator-dot'));
        const totalCards = cards.length;

        if (totalCards > 1) {
            let currentIndex = 0;
            let isUserInteracting = false;
            let resumeTimeout = null;
            const SLIDE_DURATION = 3200; // Switch member every 3.2 seconds
            const PAUSE_DURATION = 4000; // Pause 4s on user touch/click then resume

            function updateCounter(index) {
                if (!counterEl) return;
                const currentStr = String(index + 1).padStart(2, '0');
                const totalStr = String(totalCards).padStart(2, '0');
                counterEl.textContent = `${currentStr} / ${totalStr}`;
            }

            function updateDots(index) {
                dots.forEach((dot, dIdx) => {
                    dot.classList.toggle('is-active', dIdx === index);
                });
            }

            function goToMember(targetIndex) {
                if (targetIndex < 0) {
                    targetIndex = totalCards - 1;
                } else if (targetIndex >= totalCards) {
                    targetIndex = 0;
                }

                currentIndex = targetIndex;

                cards.forEach((card, cIdx) => {
                    if (cIdx === currentIndex) {
                        card.classList.add('is-active');
                    } else {
                        card.classList.remove('is-active');
                    }
                });

                updateCounter(currentIndex);
                updateDots(currentIndex);
            }

            function nextMember() {
                goToMember(currentIndex + 1);
            }

            function prevMember() {
                goToMember(currentIndex - 1);
            }

            function pauseAutoLoop() {
                isUserInteracting = true;
                if (resumeTimeout) clearTimeout(resumeTimeout);
                resumeTimeout = setTimeout(() => {
                    isUserInteracting = false;
                }, PAUSE_DURATION);
            }

            function isTeamSectionInView() {
                const rect = teamGrid.getBoundingClientRect();
                const vh = window.innerHeight || document.documentElement.clientHeight;
                return rect.top < vh && rect.bottom > 0;
            }

            // Continuous In-Place Disappear & Appear Auto-Loop
            const autoLoopTimer = setInterval(() => {
                // Only run on mobile/tablet screen widths
                if (window.innerWidth <= 991 && !isUserInteracting) {
                    if (isTeamSectionInView()) {
                        nextMember();
                    }
                }
            }, SLIDE_DURATION);

            // Manual Arrow Controls
            if (nextBtn) {
                nextBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    pauseAutoLoop();
                    nextMember();
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    pauseAutoLoop();
                    prevMember();
                });
            }

            // Dot Indicator Controls
            dots.forEach((dot, dotIdx) => {
                dot.addEventListener('click', (e) => {
                    e.preventDefault();
                    pauseAutoLoop();
                    goToMember(dotIdx);
                });
            });

            // Touch Swipe Support (Left = Next, Right = Prev)
            let touchStartX = 0;
            let touchStartY = 0;

            teamGrid.addEventListener('touchstart', (e) => {
                if (e.touches && e.touches.length > 0) {
                    touchStartX = e.touches[0].clientX;
                    touchStartY = e.touches[0].clientY;
                    pauseAutoLoop();
                }
            }, { passive: true });

            teamGrid.addEventListener('touchend', (e) => {
                if (e.changedTouches && e.changedTouches.length > 0) {
                    const diffX = e.changedTouches[0].clientX - touchStartX;
                    const diffY = e.changedTouches[0].clientY - touchStartY;
                    // Detect deliberate horizontal swipe of 30px or more
                    if (Math.abs(diffX) > 30 && Math.abs(diffX) > Math.abs(diffY)) {
                        if (diffX < 0) {
                            nextMember();
                        } else {
                            prevMember();
                        }
                        pauseAutoLoop();
                    }
                }
            }, { passive: true });

            // Initialize slide 0 state
            goToMember(0);
        }
    }
});

// 3. Mobile Responsive Slide-out Navigation Drawer Controller
function toggleMenu() {
    const mobileNav = document.getElementById('mobileNav');
    const menuBtn = document.getElementById('menuBtn');
    if (!mobileNav) return;

    const willOpen = !mobileNav.classList.contains('active');
    if (willOpen) {
        mobileNav.classList.add('active');
        document.body.classList.add('nav-open');
        if (menuBtn) {
            menuBtn.classList.add('active');
            menuBtn.setAttribute('aria-expanded', 'true');
        }
    } else {
        closeMenu();
    }
}

function closeMenu() {
    const mobileNav = document.getElementById('mobileNav');
    const menuBtn = document.getElementById('menuBtn');
    if (mobileNav) {
        mobileNav.classList.remove('active');
    }
    document.body.classList.remove('nav-open');
    if (menuBtn) {
        menuBtn.classList.remove('active');
        menuBtn.setAttribute('aria-expanded', 'false');
    }
}

// Close drawer on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeMenu();
    }
});

// 4. Dynamic Package Selection Engine (Pricing Calculator Module)
function selectPackage(cardElement) {
    if (!cardElement) return;

    const allCards = document.querySelectorAll('.package-card');
    allCards.forEach(card => card.classList.remove('active'));

    cardElement.classList.add('active');

    const packageName = cardElement.getAttribute('data-name') || cardElement.querySelector('.package-name').textContent;
    const packagePrice = cardElement.getAttribute('data-price');
    const packageDesc = cardElement.getAttribute('data-desc') || '';

    const selectedTitle = document.getElementById('selectedPackageName');
    const selectedDesc = document.getElementById('selectedPackageDesc');
    const calculatedDisplay = document.getElementById('calculatedPrice');

    if (selectedTitle) selectedTitle.textContent = packageName;
    if (selectedDesc) selectedDesc.textContent = packageDesc;
    if (calculatedDisplay && packagePrice) {
        const formattedPrice = parseInt(packagePrice).toLocaleString('en-IN');
        calculatedDisplay.innerHTML = `&#8377;${formattedPrice}`;
    }
}

// 5. Contact Client Blueprint Submission Flow Control
function handleFormSubmit(event) {
    event.preventDefault();

    const form = document.getElementById('inquiryForm');
    const successOverlay = document.getElementById('successOverlay');
    const formError = document.getElementById('formError');
    const submitBtn = form.querySelector('.submit-btn');

    if (!form || !successOverlay) return;

    // Clear previous error
    if (formError) {
        formError.style.display = 'none';
    }

    const nameVal = document.getElementById('clientName').value.trim();
    const emailVal = document.getElementById('clientEmail').value.trim();
    const messageVal = document.getElementById('clientMessage').value.trim();

    // Get active package card details
    const activeCard = form.querySelector('.package-card.active');
    if (!activeCard) {
        if (formError) {
            formError.style.display = 'flex';
            formError.querySelector('.error-msg').textContent = 'Please select an agency package.';
        }
        return;
    }

    const packageSlug = activeCard.getAttribute('data-package');
    const packageName = activeCard.getAttribute('data-name') || activeCard.querySelector('.package-name').textContent.trim();
    const price = parseFloat(activeCard.getAttribute('data-price')) || 0.0;

    submitBtn.disabled = true;
    const origBtnText = submitBtn.querySelector('span').textContent;
    submitBtn.querySelector('span').textContent = "Initiating Blueprint...";

    // Send AJAX request
    fetch('submit_inquiry.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            name: nameVal,
            email: emailVal,
            package_slug: packageSlug,
            package_name: packageName,
            price: price,
            message: messageVal
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => { throw err; });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            gsap.to(form, {
                opacity: 0,
                y: -20,
                duration: 0.5,
                onComplete: () => {
                    form.style.display = 'none';
                    successOverlay.style.display = 'flex';
                    gsap.fromTo(successOverlay, {
                        opacity: 0,
                        y: 20
                    }, {
                        opacity: 1,
                        y: 0,
                        duration: 0.5
                    });
                }
            });
        } else {
            throw new Error(data.error || 'Submission failed.');
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.querySelector('span').textContent = origBtnText;
        if (formError) {
            formError.style.display = 'flex';
            formError.querySelector('.error-msg').textContent = error.message || error.error || 'Server error. Please try again.';
            gsap.fromTo(formError, { x: -10 }, { x: 0, duration: 0.4, ease: "power2.out" });
        }
    });
}

function resetForm() {
    const form = document.getElementById('inquiryForm');
    const successOverlay = document.getElementById('successOverlay');
    const formError = document.getElementById('formError');
    const submitBtn = form.querySelector('.submit-btn');

    if (!form || !successOverlay) return;

    form.reset();
    if (formError) {
        formError.style.display = 'none';
    }
    submitBtn.disabled = false;
    submitBtn.querySelector('span').textContent = "Send Creative Blueprint";

    gsap.to(successOverlay, {
        opacity: 0,
        y: 20,
        duration: 0.5,
        onComplete: () => {
            successOverlay.style.display = 'none';
            form.style.display = 'flex';
            gsap.fromTo(form, {
                opacity: 0,
                y: -20
            }, {
                opacity: 1,
                y: 0,
                duration: 0.5
            });
        }
    });
}

// 6. Back-to-Top Navigation Smooth Scroll
function scrollToTop() {
    if (window.lenis) {
        window.lenis.scrollTo(0, { duration: 1.5 });
    } else {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// 7. Scroll from Hero to Intro Section
function scrollToNextSection() {
    const nextSection = document.getElementById('services');
    if (nextSection) {
        if (window.lenis) {
            window.lenis.scrollTo(nextSection, { duration: 1.2 });
        } else {
            nextSection.scrollIntoView({ behavior: 'smooth' });
        }
    }
}

// 8. Ticking Real-Time Indian Standard Time (IST) Clock
function initIstClock() {
    const clockEl = document.getElementById('liveIstClock');
    if (!clockEl) return;

    const updateClock = () => {
        const dateObj = new Date();
        const options = {
            timeZone: 'Asia/Kolkata',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        };
        const formatter = new Intl.DateTimeFormat('en-US', options);
        clockEl.textContent = formatter.format(dateObj) + ' IST';
    };

    setInterval(updateClock, 1000);
    updateClock();
}

// Run clock init on DOM load
document.addEventListener('DOMContentLoaded', initIstClock);

// 9. Interactive Video Lightbox Controls
function openLightbox(videoUrl) {
    const lightbox = document.getElementById('videoLightbox');
    const iframe = document.getElementById('lightboxIframe');
    const video = document.getElementById('lightboxVideo');
    if (lightbox) {
        const isMp4 = videoUrl.toLowerCase().endsWith('.mp4');
        if (isMp4 && video) {
            if (iframe) {
                iframe.style.display = 'none';
                iframe.src = '';
            }
            video.src = videoUrl;
            video.style.display = 'block';
            video.load();
            video.play().catch(e => console.log('Video play failed or blocked:', e));
        } else if (iframe) {
            if (video) {
                video.style.display = 'none';
                video.pause();
                video.src = '';
            }
            iframe.src = videoUrl;
            iframe.style.display = 'block';
        }

        lightbox.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        if (typeof gsap !== 'undefined') {
            gsap.fromTo(lightbox, { opacity: 0 }, { opacity: 1, duration: 0.4, ease: "power2.out" });
            gsap.fromTo(lightbox.querySelector('.lightbox-container'), 
                { scale: 0.92, y: 30 }, 
                { scale: 1, y: 0, duration: 0.5, ease: "power3.out" }
            );
        }
    }
}

function closeLightbox() {
    const lightbox = document.getElementById('videoLightbox');
    const iframe = document.getElementById('lightboxIframe');
    const video = document.getElementById('lightboxVideo');
    if (lightbox) {
        if (typeof gsap !== 'undefined') {
            gsap.to(lightbox.querySelector('.lightbox-container'), {
                scale: 0.95,
                y: 20,
                opacity: 0,
                duration: 0.3,
                ease: "power2.in"
            });
            gsap.to(lightbox, { 
                opacity: 0, 
                duration: 0.3,
                onComplete: () => {
                    if (iframe) iframe.src = '';
                    if (video) {
                        video.pause();
                        video.src = '';
                    }
                    lightbox.style.display = 'none';
                    document.body.style.overflow = '';
                }
            });
        } else {
            if (iframe) iframe.src = '';
            if (video) {
                video.pause();
                video.src = '';
            }
            lightbox.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
}

/* ==========================================================================
   PERUMALIL CREATIVE - ANIMATED AI ASSISTANT (NOVA) LOGIC PIPELINE
   ========================================================================== */
(function() {
    // Core Elements
    const widget = document.getElementById('aiAssistantWidget');
    if (!widget) return;

    const charBtn = document.getElementById('aiCharacterBtn');
    const speechBubble = document.getElementById('aiSpeechBubble');
    const bubbleCloseBtn = document.getElementById('aiBubbleCloseBtn');
    const voiceTriggerBtn = document.getElementById('aiVoiceTriggerBtn');
    const voiceLabel = document.getElementById('aiVoiceLabel');
    const chatDrawer = document.getElementById('aiChatDrawer');
    const drawerCloseBtn = document.getElementById('aiDrawerCloseBtn');
    const voiceToggleBtn = document.getElementById('aiVoiceToggleBtn');
    const chatBody = document.getElementById('aiChatBody');
    const chatForm = document.getElementById('aiChatForm');
    const chatInput = document.getElementById('aiChatInput');
    const eyeLeft = document.getElementById('aiEyeLeft');
    const eyeRight = document.getElementById('aiEyeRight');
    const glintLeft = document.querySelector('.glint-left');
    const glintRight = document.querySelector('.glint-right');

    const waPhone = widget.getAttribute('data-wa-phone') || '919946020716';

    let isVoiceMuted = localStorage.getItem('nova_voice_muted') === 'true';
    let isSpeaking = false;
    let bubbleDismissed = false;
    let synth = window.speechSynthesis;

    // Update voice button UI
    function updateVoiceToggleUI() {
        if (!voiceToggleBtn) return;
        if (isVoiceMuted) {
            voiceToggleBtn.classList.add('voice-muted');
            voiceToggleBtn.title = 'Voice Speech: Muted (Click to Unmute)';
        } else {
            voiceToggleBtn.classList.remove('voice-muted');
            voiceToggleBtn.title = 'Voice Speech: Active (Click to Mute)';
        }
    }
    updateVoiceToggleUI();

    if (voiceToggleBtn) {
        voiceToggleBtn.addEventListener('click', () => {
            isVoiceMuted = !isVoiceMuted;
            localStorage.setItem('nova_voice_muted', isVoiceMuted);
            updateVoiceToggleUI();
            if (isVoiceMuted && synth) {
                synth.cancel();
                stopSpeakingVisuals();
            }
        });
    }

    // Speech Synthesis Engine
    function speakText(text, onComplete) {
        if (!synth || isVoiceMuted) {
            if (onComplete) onComplete();
            return;
        }

        try {
            synth.cancel(); // Stop any active utterance

            // Clean text from HTML tags for speech
            const plainText = text.replace(/<[^>]*>/g, '').replace(/[\u{1F300}-\u{1FAFF}]/gu, '');
            const utterance = new SpeechSynthesisUtterance(plainText);
            
            utterance.rate = 1.05;
            utterance.pitch = 1.1;

            // Pick a crisp modern natural voice if available
            const voices = synth.getVoices();
            const preferredVoice = voices.find(v => 
                (v.lang.startsWith('en') && (v.name.includes('Google') || v.name.includes('Natural') || v.name.includes('Samantha') || v.name.includes('Daniel') || v.name.includes('Karen')))
            ) || voices.find(v => v.lang.startsWith('en'));

            if (preferredVoice) {
                utterance.voice = preferredVoice;
            }

            utterance.onstart = () => {
                isSpeaking = true;
                widget.classList.add('is-speaking');
                widget.classList.add('happy-face');
                if (voiceLabel) voiceLabel.textContent = 'Speaking...';
            };

            const finishSpeaking = () => {
                isSpeaking = false;
                widget.classList.remove('is-speaking');
                widget.classList.remove('happy-face');
                if (voiceLabel) voiceLabel.textContent = 'Listen';
                if (onComplete) onComplete();
            };

            utterance.onend = finishSpeaking;
            utterance.onerror = finishSpeaking;

            synth.speak(utterance);
        } catch (err) {
            stopSpeakingVisuals();
            if (onComplete) onComplete();
        }
    }

    function stopSpeakingVisuals() {
        isSpeaking = false;
        widget.classList.remove('is-speaking');
        widget.classList.remove('happy-face');
        if (voiceLabel) voiceLabel.textContent = 'Listen';
    }

    // Auto-Display Welcome Bubble on Load
    setTimeout(() => {
        if (!bubbleDismissed && speechBubble && !chatDrawer.classList.contains('is-open')) {
            speechBubble.classList.add('is-visible');
        }
    }, 1600);

    // Auto-dismiss bubble after 14s if user doesn't interact
    let autoDismissTimer = setTimeout(() => {
        if (speechBubble && speechBubble.classList.contains('is-visible') && !isSpeaking) {
            speechBubble.classList.remove('is-visible');
        }
    }, 14000);

    // Bubble Dismiss Action
    if (bubbleCloseBtn) {
        bubbleCloseBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            bubbleDismissed = true;
            clearTimeout(autoDismissTimer);
            if (speechBubble) {
                speechBubble.classList.remove('is-visible');
                speechBubble.classList.add('is-dismissed');
            }
            if (synth) synth.cancel();
            stopSpeakingVisuals();
        });
    }

    // Bubble Voice Trigger: Hear Nova Speak Aloud
    if (voiceTriggerBtn) {
        voiceTriggerBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            clearTimeout(autoDismissTimer);
            if (isSpeaking && synth) {
                synth.cancel();
                stopSpeakingVisuals();
                return;
            }
            const introSpeech = "Hello! I am Nova, your AI assistant at Perumalil Creative. I'm here to help you explore our design services, check out our portfolio, or get an instant project estimate. How can I help you today?";
            speakText(introSpeech);
        });
    }

    // Toggle Chat Drawer Functions
    window.openNovaAiAssistant = function() {
        clearTimeout(autoDismissTimer);
        if (speechBubble) {
            speechBubble.classList.remove('is-visible');
        }
        if (chatDrawer) {
            chatDrawer.classList.add('is-open');
            document.body.classList.add('ai-drawer-active');
        }
        setTimeout(() => {
            if (chatInput) chatInput.focus();
            scrollChatToBottom(false);
        }, 120);
    };

    window.closeNovaAiAssistant = function() {
        if (chatDrawer) {
            chatDrawer.classList.remove('is-open');
            document.body.classList.remove('ai-drawer-active');
        }
        if (synth) synth.cancel();
        stopSpeakingVisuals();
    };

    window.toggleNovaAiAssistant = function(forceOpen) {
        if (forceOpen === true) {
            window.openNovaAiAssistant();
            return;
        }
        if (chatDrawer && chatDrawer.classList.contains('is-open')) {
            window.closeNovaAiAssistant();
        } else {
            window.openNovaAiAssistant();
        }
    };

    if (charBtn) {
        charBtn.addEventListener('click', () => {
            window.toggleNovaAiAssistant();
        });
    }

    if (drawerCloseBtn) {
        drawerCloseBtn.addEventListener('click', () => {
            window.closeNovaAiAssistant();
        });
    }

    // Smooth Scroll Helper
    function smoothScrollTo(targetSelector) {
        if (!targetSelector || typeof targetSelector !== 'string' || !targetSelector.startsWith('#')) return;
        try {
            const el = document.querySelector(targetSelector);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                window.closeNovaAiAssistant();
            }
        } catch (e) {}
    }

    // Intelligent Knowledge Base & Q&A Engine for Nova (ChatGPT / Gemini style exact precision)
    function getNovaResponse(userText) {
        const q = userText.toLowerCase().trim();

        // 0. Database Study & Audit
        if (q.includes('database') || (q.includes('study') && q.includes('all')) || (q.includes('learn') && q.includes('database')) || q.includes('audit')) {
            return {
                text: "✅ <strong>I have studied and memorized all 11 tables of our MySQL database!</strong><br>• 👥 <strong>team_members</strong> (4 rows): Managing Director Dijo J Perumaly, Technical Director Manoj, Creative Developer Sojin, Creative Designer Sam.<br>• 🎨 <strong>services</strong> (6 rows): SEO, Digital Marketing, Advertising, Web Design, ID Card Design, Graphic Design.<br>• 💰 <strong>pricing_packages</strong> (4 rows): Video Production (₹5,999), Web Development (₹3,999), Graphic Design (₹200), Meta & Google Ads (₹2,000/mo).<br>• 🎬 <strong>portfolio_items</strong> (19 rows): 13 Web, 3 Video, 3 Design projects.<br>• 🤝 <strong>collaborators</strong> (22 rows): 22+ brand partners.<br>• ⚙️ <strong>site_settings</strong> (54 rows): Vazhakulam studio headquarters.",
                links: [
                    { label: "👥 Who is the Director?", action: () => window.askNovaQuestion('Who is the director of the company?') },
                    { label: "🎨 6 Services", action: () => smoothScrollTo('#services') },
                    { label: "💰 4 Pricing Packages", action: () => smoothScrollTo('#contact') }
                ]
            };
        }

        // =========================================================================
        // 1. Exact Role / Person Queries (Exact ChatGPT / Gemini style precision)
        // =========================================================================

        // A. Creative Developer (e.g. "hi the creative devolper", "creative developer", "sojin", "web developer", "developer")
        if (
            (
                q.includes('sojin') ||
                q.includes('creative developer') ||
                q.includes('creative devolper') ||
                q.includes('creative devloper') ||
                q.includes('creative developr') ||
                q.includes('web developer') ||
                q.includes('web devolper') ||
                q.includes('web devloper') ||
                q.includes('developer') ||
                q.includes('devloper') ||
                q.includes('devolper') ||
                q.includes('developr') ||
                q.includes('programmer') ||
                q.includes('coder')
            ) &&
            !q.includes('package') && !q.includes('price') && !q.includes('cost') && !q.includes('rate') && 
            !q.includes('pricing') && !q.includes('how much') && !q.includes('kaashu') && !q.includes('ethraya') &&
            !q.includes('all') && !q.includes('members') && !q.includes('everyone')
        ) {
            return {
                text: "The <strong>Creative Developer</strong> at Perumalil Creative is <strong>Sojin</strong>.<br><br>Sojin specializes in engineering responsive web architectures, bespoke user experiences, and dynamic digital platforms.",
                links: [
                    { label: "🌐 View Web Architectures", action: () => smoothScrollTo('#portfolio') },
                    { label: "👥 Meet All Team Members", action: () => window.askNovaQuestion('Who are all the team members?') },
                    { label: "💬 Discuss on WhatsApp", action: () => window.open(`https://wa.me/${waPhone}?text=Hi%20Sojin,%20I%20want%20to%20discuss%20a%20web%20project!`, '_blank') }
                ]
            };
        }

        // B. Creative Designer (e.g. "who is the creative designer", "creative designer", "sam", "graphic designer")
        if (
            (
                q.includes('sam') ||
                q.includes('creative designer') ||
                q.includes('creative desiner') ||
                q.includes('graphic designer') ||
                q.includes('graphic desiner') ||
                (
                    (q.includes('designer') || q.includes('desiner')) &&
                    (!q.includes('graphic design') || q.includes('graphic designer')) &&
                    !q.includes('service') && !q.includes('services') && !q.includes('id card') && !q.includes('website design')
                )
            ) &&
            (!q.includes('graphic design') || q.includes('graphic designer')) &&
            !q.includes('service') && !q.includes('services') && !q.includes('id card') && !q.includes('website design') &&
            !q.includes('package') && !q.includes('price') && !q.includes('cost') && !q.includes('rate') && 
            !q.includes('pricing') && !q.includes('how much') && !q.includes('kaashu') && !q.includes('ethraya') &&
            !q.includes('all') && !q.includes('members') && !q.includes('everyone')
        ) {
            return {
                text: "The <strong>Creative Designer</strong> at Perumalil Creative is <strong>Sam</strong>.<br><br>Sam crafts our luxury visual branding, graphic design solutions, editorial layouts, and brand identity systems.",
                links: [
                    { label: "🎨 View Graphic Works", action: () => smoothScrollTo('#portfolio') },
                    { label: "👥 Meet All Team Members", action: () => window.askNovaQuestion('Who are all the team members?') },
                    { label: "💬 Chat on WhatsApp", action: () => window.open(`https://wa.me/${waPhone}?text=Hi%20Sam,%20I%20want%20to%20discuss%20a%20design%20project!`, '_blank') }
                ]
            };
        }

        // C. Technical Director (e.g. "who is the technical director", "tech director", "manoj")
        if (
            (q.includes('technical director') || q.includes('tech director') || q.includes('manoj')) &&
            !q.includes('all') && !q.includes('members')
        ) {
            return {
                text: "The <strong>Technical Director</strong> at Perumalil Creative is <strong>Manoj</strong>.",
                links: [
                    { label: "🌐 View Web Projects", action: () => smoothScrollTo('#portfolio') },
                    { label: "👥 Meet All Team Members", action: () => window.askNovaQuestion('Who are all the team members?') },
                    { label: "💬 Connect on WhatsApp", action: () => window.open(`https://wa.me/${waPhone}?text=Hi%20Manoj,%20I%20want%20to%20discuss%20a%20project!`, '_blank') }
                ]
            };
        }

        // D. Managing Director / Founder / CEO / Owner / Dijo (Exact single answer)
        if (
            (q.includes('managing director') || q.includes('director') || q.includes('founder') || q.includes('owner') || q.includes('ceo') || q.includes('dijo') || q.includes('perumaly') || q.includes('who runs') || q.includes('who leads') || q.includes('who owns') || q.includes('who started')) &&
            !q.includes('all') && !q.includes('members') && !q.includes('staff') && !q.includes('technical') && !q.includes('creative')
        ) {
            return {
                text: "The <strong>Managing Director</strong> of Perumalil Creative is <strong>Dijo J Perumaly</strong>.",
                links: [
                    { label: "💬 Connect with Director", action: () => window.open(`https://wa.me/${waPhone}?text=Hi%20Dijo,%20I%20want%20to%20discuss%20a%20project!`, '_blank') },
                    { label: "👥 Meet All Team Members", action: () => window.askNovaQuestion('Who are all the team members?') },
                    { label: "📞 Call Studio", action: () => window.open(`tel:${waPhone}`) }
                ]
            };
        }

        // E. Full Team Roster (ONLY when explicitly asking for all members or team list)
        if (
            q.includes('team members') || q.includes('all the team') || q.includes('all team') || q.includes('who works here') ||
            q.includes('full team') || q.includes('meet the team') || (q.includes('team') && (q.includes('who') || q.includes('all') || q.includes('list') || q.includes('members')))
        ) {
            return {
                text: "Our core studio leadership team includes:<br>• 👤 <strong>Dijo J Perumaly</strong> — Managing Director<br>• 💻 <strong>Manoj</strong> — Technical Director<br>• ⚡ <strong>Sojin</strong> — Creative Developer<br>• 🎨 <strong>Sam</strong> — Creative Designer",
                links: [
                    { label: "💬 Chat with Director", action: () => window.open(`https://wa.me/${waPhone}?text=Hi%20Dijo,%20I%20want%20to%20discuss%20a%20project!`, '_blank') },
                    { label: "🎨 Explore Services", action: () => smoothScrollTo('#services') },
                    { label: "🎬 View Portfolio", action: () => smoothScrollTo('#portfolio') }
                ]
            };
        }

        // =========================================================================
        // 2. Exact Single Contact & Studio Details
        // =========================================================================

        // Email
        if (q.includes('email') && (q.includes('what') || q.includes('address') || q.includes('your') || q.includes('studio') || q === 'email')) {
            return {
                text: "Our official email address is <strong>hello@perumalilcreative.com</strong>.",
                links: [
                    { label: "💬 Chat on WhatsApp", action: () => window.open(`https://wa.me/${waPhone}?text=Hi%20Perumalil%20Creative,%20I'd%20like%20to%20connect!`, '_blank') },
                    { label: "📞 Call Studio", action: () => window.open(`tel:${waPhone}`) }
                ]
            };
        }

        // Phone
        if ((q.includes('phone') || q.includes('number') || q.includes('call') || q.includes('mobile')) && (q.includes('what') || q.includes('contact') || q.includes('your') || q === 'phone' || q === 'number')) {
            return {
                text: "Our studio contact numbers are:<br>• Primary: <strong>+91 99460 20716</strong><br>• Secondary: <strong>+91 99460 20724</strong>",
                links: [
                    { label: "📞 Call Primary", action: () => window.open(`tel:${waPhone}`) },
                    { label: "💬 WhatsApp", action: () => window.open(`https://wa.me/${waPhone}?text=Hi%20Perumalil%20Creative,%20I%20would%20like%20to%20discuss%20a%20project!`, '_blank') }
                ]
            };
        }

        // Studio Address / Location
        if (q.includes('address') || q.includes('where is your office') || q.includes('where is your studio') || q.includes('office location') || q.includes('studio location') || q.includes('where are you located')) {
            return {
                text: "Our physical creative studio is located at:<br>📍 <strong>Opp. Viswajyothy College, Vazhakulam, Muvattupuzha, Kerala - 686670, India</strong><br>(Coordinates: 9.9816° N, 76.6534° E)",
                links: [
                    { label: "📍 View Studio Map", action: () => smoothScrollTo('#location') },
                    { label: "📞 Call Studio Directly", action: () => window.open(`tel:${waPhone}`) }
                ]
            };
        }

        // Tagline / Motto
        if (q.includes('tagline') || q.includes('motto') || q.includes('slogan')) {
            return {
                text: "Our motto is <em>\"Design Your Every Occasion.\"</em>",
                links: [
                    { label: "🎨 Explore Services", action: () => smoothScrollTo('#services') },
                    { label: "🎬 View Portfolio", action: () => smoothScrollTo('#portfolio') }
                ]
            };
        }

        // =========================================================================
        // 3. Exact Single Package Pricing
        // =========================================================================

        // Video Price
        if ((q.includes('video') || q.includes('cinematic') || q.includes('shoot') || q.includes('film') || q.includes('reel')) && (q.includes('price') || q.includes('cost') || q.includes('rate') || q.includes('how much') || q.includes('package')) && !q.includes('all')) {
            return {
                text: "Our <strong>Video Production</strong> package starts from <strong>₹5,999</strong>.<br><br><strong>Includes:</strong><br>• Professional Cinematic Shoot<br>• Advanced Editing Included<br>• 4K High Quality Output<br>• Fast 2–3 Day Delivery",
                links: [
                    { label: "💰 Open Cost Calculator", action: () => smoothScrollTo('#contact') },
                    { label: "🎬 View Video Showcase", action: () => smoothScrollTo('#portfolio') },
                    { label: "💬 Discuss on WhatsApp", action: () => window.open(`https://wa.me/${waPhone}?text=Hi,%20I'm%20interested%20in%20a%20Video%20Production%20package!`, '_blank') }
                ]
            };
        }

        // Web Price
        if ((q.includes('web') || q.includes('website') || q.includes('static') || q.includes('dynamic')) && (q.includes('price') || q.includes('cost') || q.includes('rate') || q.includes('how much') || q.includes('package')) && !q.includes('all')) {
            return {
                text: "Our <strong>Web Development</strong> package starts from <strong>₹3,999</strong>:<br>• Single Page Website: ₹3,999<br>• Multi Page Website: ₹6,999<br>• Dynamic Website: ₹6,999+<br>• High-Speed 3GB Hosting: ₹2,999",
                links: [
                    { label: "💰 Open Cost Calculator", action: () => smoothScrollTo('#contact') },
                    { label: "🌐 View Web Portfolios", action: () => smoothScrollTo('#portfolio') },
                    { label: "💬 Discuss on WhatsApp", action: () => window.open(`https://wa.me/${waPhone}?text=Hi,%20I'm%20interested%20in%20a%20Web%20Development%20package!`, '_blank') }
                ]
            };
        }

        // Graphic Design Price
        if ((q.includes('graphic') || q.includes('logo') || q.includes('poster') || q.includes('brochure')) && (q.includes('price') || q.includes('cost') || q.includes('rate') || q.includes('how much') || q.includes('package')) && !q.includes('all')) {
            return {
                text: "Our <strong>Graphic Design</strong> package starts from <strong>₹200</strong> (includes Logo Design, Brochure Design, Poster Design, and Custom Graphics).",
                links: [
                    { label: "🎨 View Graphic Works", action: () => smoothScrollTo('#portfolio') },
                    { label: "💰 Cost Calculator", action: () => smoothScrollTo('#contact') },
                    { label: "💬 Inquire on WhatsApp", action: () => window.open(`https://wa.me/${waPhone}?text=Hi,%20I'm%20interested%20in%20a%20Graphic%20Design%20package!`, '_blank') }
                ]
            };
        }

        // Ads Price
        if ((q.includes('ads') || q.includes('meta') || q.includes('google ad') || q.includes('campaign')) && (q.includes('price') || q.includes('cost') || q.includes('rate') || q.includes('how much') || q.includes('package')) && !q.includes('all')) {
            return {
                text: "Our <strong>Meta & Google Ads</strong> campaign management starts from <strong>₹2,000/month</strong> (includes strategic campaign planning, audience targeting, budget management, and lead generation).",
                links: [
                    { label: "💰 Cost Calculator", action: () => smoothScrollTo('#contact') },
                    { label: "💬 Start Ads on WhatsApp", action: () => window.open(`https://wa.me/${waPhone}?text=Hi,%20I%20want%20to%20launch%20Meta%20and%20Google%20Ads!`, '_blank') }
                ]
            };
        }

        // =========================================================================
        // 4. Counts / Numbers
        // =========================================================================

        if (q.includes('how many') && q.includes('service')) {
            return {
                text: "We offer <strong>6 core services</strong>: SEO, Digital Marketing, Advertising, Website Design, ID Card Design, and Graphic Design.",
                links: [
                    { label: "🎨 View Services Grid", action: () => smoothScrollTo('#services') },
                    { label: "💰 Cost Calculator", action: () => smoothScrollTo('#contact') }
                ]
            };
        }

        if (q.includes('how many') && (q.includes('team') || q.includes('member') || q.includes('people') || q.includes('staff'))) {
            return {
                text: "Our core team consists of <strong>4 members</strong>: Dijo J Perumaly (Managing Director), Manoj (Technical Director), Sojin (Creative Developer), and Sam (Creative Designer).",
                links: [
                    { label: "👥 Meet All Team Members", action: () => window.askNovaQuestion('Who are all the team members?') },
                    { label: "💬 Chat on WhatsApp", action: () => window.open(`https://wa.me/${waPhone}?text=Hi%20Perumalil%20Creative%20team!`, '_blank') }
                ]
            };
        }

        if (q.includes('how many') && (q.includes('project') || q.includes('portfolio') || q.includes('works'))) {
            return {
                text: "We have <strong>19 featured projects</strong> in our portfolio across Web Development (13), Video Production (3), and Graphic Design (3).",
                links: [
                    { label: "🎬 Explore Portfolio Grid", action: () => smoothScrollTo('#portfolio') },
                    { label: "💰 Cost Calculator", action: () => smoothScrollTo('#contact') }
                ]
            };
        }

        // =========================================================================
        // 5. Broad Categories (Services, Portfolio, Pricing, Location, Contact)
        // =========================================================================

        // Identity & Role
        if (q.includes('who are you') || q.includes('what are you') || q.includes('your name') || (q.includes('ai') && (q.includes('what') || q.includes('who') || q.includes('assistant')))) {
            return {
                text: "<strong>I am Nova, your Perumalil AI Creative Assistant!</strong><br>I'm an interactive AI agent who has studied the entire MySQL database of Perumalil Creative. Ask me about our team, services, pricing packages, portfolio projects, or studio headquarters in Vazhakulam, Kerala.",
                links: [
                    { label: "📊 Database Audit", action: () => window.askNovaQuestion('Study all the database') },
                    { label: "🎨 What We Offer", action: () => smoothScrollTo('#services') },
                    { label: "🎬 Showcase Works", action: () => smoothScrollTo('#portfolio') },
                    { label: "💰 Cost Calculator", action: () => smoothScrollTo('#contact') }
                ]
            };
        }

        // Services / Capabilities
        if (q.includes('service') || q.includes('offer') || q.includes('what do you do') || q.includes('solution') || q.includes('capabilities')) {
            return {
                text: "At <strong>Perumalil Creative</strong>, we engineer high-impact experiences across 6 services:<br>• 🌐 <strong>Website Design:</strong> Custom high-performance websites & WebGL interactive digital platforms.<br>• 🎬 <strong>Advertising & Marketing:</strong> 4K cinematic commercials, brand films & social ads.<br>• 🎨 <strong>Graphic Design & ID Cards:</strong> Logo systems, typography & corporate branding.<br>• 📈 <strong>SEO & Digital Growth:</strong> Search engine dominance & lead generation.",
                links: [
                    { label: "View Services Grid", action: () => smoothScrollTo('#services') },
                    { label: "Estimate a Project", action: () => smoothScrollTo('#contact') }
                ]
            };
        }

        // Latest / Last Added Portfolio Work
        if ((q.includes('latest') || q.includes('last') || q.includes('recent') || q.includes('puthiya') || q.includes('add cheytha')) && (q.includes('work') || q.includes('project') || q.includes('portfolio') || q.includes('site') || q.includes('item') || q.includes('added'))) {
            return {
                text: "The latest project added to our portfolio is <strong>Principle 1 Professional Services</strong> (Web Architecture • US Mortgage Processing Portal).<br>Executive professional services website with corporate telemetry and booking pipelines.<br><br>🔗 <strong>Live Platform:</strong> <a href=\"https://principle1pro.com/\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"color: #38bdf8; text-decoration: underline; font-weight: 600;\">https://principle1pro.com/ ↗</a>",
                links: [
                    { label: "🌐 Open Live Site", action: () => window.open('https://principle1pro.com/', '_blank') },
                    { label: "🎬 View in Portfolio", action: () => smoothScrollTo('#portfolio') },
                    { label: "💬 Discuss on WhatsApp", action: () => window.open(`https://wa.me/${waPhone}?text=Hi%20Perumalil%20Creative,%20I%20want%20to%20discuss%20a%20project!`, '_blank') }
                ]
            };
        }

        // Portfolio & Past Work (Broad)
        if (q.includes('portfolio') || q.includes('work') || q.includes('project') || q.includes('sample') || q.includes('showcase')) {
            return {
                text: "Our portfolio features <strong>19 projects</strong> across Web Architecture, 4K Video Production, and Graphic Branding.<br>Explore our showcase gallery below, or ask me for our latest works!",
                links: [
                    { label: "🎬 Explore Portfolio Gallery", action: () => smoothScrollTo('#portfolio') },
                    { label: "✨ Latest Work Added", action: () => window.askNovaQuestion('What is the latest work added in portfolio?') },
                    { label: "💬 Discuss on WhatsApp", action: () => window.open(`https://wa.me/${waPhone}?text=Hi%20Perumalil%20Creative,%20I%20would%20like%20to%20see%20more%20portfolio%20samples!`, '_blank') }
                ]
            };
        }

        // Pricing / Cost / Rates (All / Broad)
        if (q.includes('price') || q.includes('pricing') || q.includes('cost') || q.includes('how much') || q.includes('rate') || q.includes('package') || q.includes('budget') || q.includes('quote') || q.includes('estimate') || q.includes('calculator')) {
            return {
                text: "Here is our transparent database pricing card:<br>• 🎬 <strong>Video Production:</strong> from ₹5,999<br>• 🌐 <strong>Web Development:</strong> from ₹3,999 (Dynamic: ₹6,999)<br>• 🎨 <strong>Graphic Design:</strong> from ₹200<br>• 📈 <strong>Meta & Google Ads:</strong> ₹2,000/mo<br><br>You can also use our interactive cost calculator on this page!",
                links: [
                    { label: "💰 Open Pricing Calculator", action: () => smoothScrollTo('#contact') },
                    { label: "📦 Compare Pricing Packages", action: () => smoothScrollTo('#services') }
                ]
            };
        }

        // Studio Location & Headquarters
        if (q.includes('location') || q.includes('where') || q.includes('office') || q.includes('studio') || q.includes('kerala') || q.includes('vazhakulam') || q.includes('map')) {
            return {
                text: "📍 <strong>Studio Address:</strong> Opposite Viswajyothy College, Main Road, Vazhakulam, Kerala (Coordinates: 9.9816° N, 76.6534° E).<br>We execute projects locally and with partners worldwide!",
                links: [
                    { label: "📍 View Studio Map", action: () => smoothScrollTo('#location') },
                    { label: "📞 Call Studio Directly", action: () => window.open(`tel:${waPhone}`) }
                ]
            };
        }

        // Contact / WhatsApp / Booking
        if (q.includes('contact') || q.includes('whatsapp') || q.includes('talk to human') || q.includes('meet') || q.includes('hire') || q.includes('book')) {
            return {
                text: "📞 <strong>Primary Phone:</strong> +91 99460 20716<br>• ✉️ <strong>Email:</strong> hello@perumalilcreative.com<br>• 👤 <strong>Director:</strong> Dijo J Perumaly",
                links: [
                    { label: "💬 Chat on WhatsApp", action: () => window.open(`https://wa.me/${waPhone}?text=Hi%20Perumalil%20Creative%20team,%20I%20want%20to%20discuss%20a%20project!`, '_blank') },
                    { label: "📝 Submit Online Inquiry", action: () => smoothScrollTo('#contact') }
                ]
            };
        }

        // General / Fallback
        return {
            text: "I couldn't find an exact match for that, but I can help you with our <strong>services</strong>, <strong>latest portfolio works</strong>, <strong>package pricing</strong>, <strong>team</strong>, or <strong>studio location</strong> in Vazhakulam. You can also chat directly on WhatsApp!",
            links: [
                { label: "💬 Chat on WhatsApp", action: () => window.open(`https://wa.me/${waPhone}?text=Hi%20Perumalil%20Creative,%20I'd%20like%20more%20information!`, '_blank') },
                { label: "✨ Latest Portfolio Work", action: () => window.askNovaQuestion('What is the latest work added in portfolio?') },
                { label: "🎨 Explore Services", action: () => smoothScrollTo('#services') },
                { label: "💰 Pricing Calculator", action: () => smoothScrollTo('#contact') }
            ]
        };
    }

    // Append Message to Chat Body
    function appendMessage(sender, text, links = []) {
        if (!chatBody) return;

        const msgDiv = document.createElement('div');
        msgDiv.className = `ai-message ai-message-${sender}`;

        const avatarDiv = document.createElement('div');
        avatarDiv.className = 'ai-msg-avatar';
        avatarDiv.innerHTML = sender === 'bot' ? '✦' : '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';

        const bubbleDiv = document.createElement('div');
        bubbleDiv.className = 'ai-msg-bubble';
        
        const p = document.createElement('p');
        p.className = 'ai-msg-p';
        p.innerHTML = text;
        bubbleDiv.appendChild(p);

        if (links && links.length > 0) {
            const linksWrap = document.createElement('div');
            linksWrap.className = 'ai-msg-quick-links';
            links.forEach(item => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ai-reply-chip';
                btn.textContent = item.label;
                btn.addEventListener('click', item.action);
                linksWrap.appendChild(btn);
            });
            bubbleDiv.appendChild(linksWrap);
        }

        msgDiv.appendChild(avatarDiv);
        msgDiv.appendChild(bubbleDiv);
        chatBody.appendChild(msgDiv);
        scrollChatToBottom(true);
    }

    // Show Typing Indicator
    function showTypingIndicator() {
        const ind = document.createElement('div');
        ind.className = 'ai-message ai-message-bot ai-typing-indicator-msg';
        ind.id = 'aiTypingIndicator';
        ind.innerHTML = `
            <div class="ai-msg-avatar">✦</div>
            <div class="ai-msg-bubble ai-typing-indicator">
                <span class="ai-typing-dot"></span>
                <span class="ai-typing-dot"></span>
                <span class="ai-typing-dot"></span>
            </div>
        `;
        chatBody.appendChild(ind);
        scrollChatToBottom(true);
    }

    function removeTypingIndicator() {
        const ind = document.getElementById('aiTypingIndicator');
        if (ind) ind.remove();
    }

    // Asynchronous backend communicator for Nova
    let novaConversationHistory = [];

    async function fetchNovaChatResponse(userText) {
        try {
            const res = await fetch('api/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: userText,
                    history: novaConversationHistory.slice(-6)
                })
            });

            if (res.ok) {
                const data = await res.json();
                if (data && data.success && data.reply) {
                    const links = (data.chips || []).map(chip => {
                        return {
                            label: chip.label,
                            action: () => {
                                if (chip.action === 'whatsapp') {
                                    const waClean = chip.target.replace(/[^0-9]/g, '') || waPhone;
                                    window.open(`https://wa.me/${waClean}?text=${encodeURIComponent('Hi Perumalil Creative, I want to discuss a project!')}`, '_blank');
                                } else if (chip.action === 'call') {
                                    window.open(`tel:${chip.target}`);
                                } else if (chip.action === 'url' || chip.action === 'link' || (typeof chip.target === 'string' && chip.target.startsWith('http'))) {
                                    window.open(chip.target, '_blank');
                                } else if (chip.action === 'ask' || chip.action === 'chat') {
                                    if (typeof window.askNovaQuestion === 'function') {
                                        window.askNovaQuestion(chip.target);
                                    }
                                } else if (chip.action === 'scroll') {
                                    smoothScrollTo(chip.target);
                                } else {
                                    if (typeof chip.target === 'string' && chip.target.startsWith('#')) {
                                        smoothScrollTo(chip.target);
                                    } else if (typeof chip.target === 'string' && chip.target.startsWith('http')) {
                                        window.open(chip.target, '_blank');
                                    } else {
                                        smoothScrollTo(chip.target);
                                    }
                                }
                            }
                        };
                    });

                    novaConversationHistory.push({ sender: 'user', text: userText });
                    novaConversationHistory.push({ sender: 'bot', text: data.reply });

                    return { text: data.reply, links: links, leadCaptured: data.lead_captured };
                }
            }
        } catch (err) {
            // Fallback gracefully
        }

        const local = getNovaResponse(userText);
        novaConversationHistory.push({ sender: 'user', text: userText });
        novaConversationHistory.push({ sender: 'bot', text: local.text });
        return local;
    }

    // Ask Question Handler
    window.askNovaQuestion = async function(questionText) {
        window.openNovaAiAssistant();
        appendMessage('user', questionText);
        showTypingIndicator();

        const resp = await fetchNovaChatResponse(questionText);
        removeTypingIndicator();
        appendMessage('bot', resp.text, resp.links);
        if (resp.leadCaptured) {
            appendMessage('bot', '<span style="color: #10b981; font-weight: 700;">✅ Thank you! Your contact details have been noted. Our creative directors will contact you shortly.</span>');
        }
        speakText(resp.text);
    };

    // Chat Form Submit Handler
    window.handleNovaSubmit = async function(e) {
        if (e) e.preventDefault();
        if (!chatInput) return;
        const val = chatInput.value.trim();
        if (!val) return;

        chatInput.value = '';
        appendMessage('user', val);
        showTypingIndicator();

        const resp = await fetchNovaChatResponse(val);
        removeTypingIndicator();
        appendMessage('bot', resp.text, resp.links);
        if (resp.leadCaptured) {
            appendMessage('bot', '<span style="color: #10b981; font-weight: 700;">✅ Thank you! Your contact details have been noted. Our creative directors will contact you shortly.</span>');
        }
        speakText(resp.text);
    };

    // Ultra-Reliable Chat Scroll Engine
    function scrollChatToBottom(smooth = true) {
        if (!chatBody) return;
        requestAnimationFrame(() => {
            chatBody.scrollTo({
                top: chatBody.scrollHeight,
                behavior: smooth ? 'smooth' : 'auto'
            });
            setTimeout(() => {
                if (chatBody) {
                    chatBody.scrollTop = chatBody.scrollHeight;
                    updateScrollButtonState();
                }
            }, 60);
            setTimeout(() => {
                if (chatBody) {
                    chatBody.scrollTop = chatBody.scrollHeight;
                    updateScrollButtonState();
                }
            }, 260);
        });
    }

    // Update Floating Scroll Button State based on scroll position
    function updateScrollButtonState() {
        const scrollBtn = document.getElementById('aiChatScrollBtn');
        if (!scrollBtn || !chatBody) return;
        const distanceFromBottom = chatBody.scrollHeight - chatBody.scrollTop - chatBody.clientHeight;
        if (distanceFromBottom > 45) {
            scrollBtn.classList.add('is-visible');
        } else {
            scrollBtn.classList.remove('is-visible');
        }
    }

    if (chatBody) {
        chatBody.addEventListener('scroll', updateScrollButtonState, { passive: true });
        
        // Prevent scroll chaining to parent body when scrolling inside chat
        chatBody.addEventListener('wheel', (e) => {
            const isAtTop = chatBody.scrollTop === 0 && e.deltaY < 0;
            const isAtBottom = (chatBody.scrollHeight - chatBody.scrollTop - chatBody.clientHeight <= 1) && e.deltaY > 0;
            if (!isAtTop && !isAtBottom) {
                e.stopPropagation();
            }
        }, { passive: true });
    }

    // Scroll Chat to Bottom on Demand
    window.scrollNovaChatToBottom = function() {
        scrollChatToBottom(true);
    };

    const floatingScrollBtn = document.getElementById('aiChatScrollBtn');
    if (floatingScrollBtn) {
        floatingScrollBtn.addEventListener('click', (e) => {
            e.preventDefault();
            scrollChatToBottom(true);
        });
    }

    // Clear Chat History & Reset Stream
    window.clearNovaChat = function() {
        if (!chatBody) return;

        // Stop any ongoing speech
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
        }
        if (widget) {
            widget.classList.remove('is-speaking');
        }

        // Reset conversation memory
        novaConversationHistory = [];

        // Clear DOM and inject fresh welcome card
        chatBody.innerHTML = `
            <div class="ai-message ai-message-bot intro-bot-msg">
                <div class="ai-msg-avatar">
                    <span class="avatar-icon">✦</span>
                </div>
                <div class="ai-msg-bubble">
                    <p class="ai-msg-p">
                        <strong>Hi, I'm Nova!</strong> 👋 How can I help you today?
                    </p>
                </div>
            </div>
        `;

        // Hide scroll button
        const scrollBtn = document.getElementById('aiChatScrollBtn');
        if (scrollBtn) scrollBtn.classList.remove('is-visible');

        // Trigger brief success toast
        const toast = document.getElementById('aiChatToast');
        if (toast) {
            toast.classList.add('is-show');
            setTimeout(() => {
                toast.classList.remove('is-show');
            }, 2200);
        }

        chatBody.scrollTop = 0;
    };

    // Interactive Eye Tracking (Nova watches cursor subtly)
    if (eyeLeft && eyeRight) {
        let ticking = false;
        window.addEventListener('mousemove', (e) => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    if (widget) {
                        const rect = widget.getBoundingClientRect();
                        const robotCenterX = rect.left + rect.width / 2;
                        const robotCenterY = rect.top + rect.height / 2;

                        const dx = e.clientX - robotCenterX;
                        const dy = e.clientY - robotCenterY;
                        const dist = Math.hypot(dx, dy) || 1;

                        // Maximum eye displacement of 2.5px
                        const maxMove = 2.5;
                        const moveX = (dx / dist) * Math.min(dist / 80, maxMove);
                        const moveY = (dy / dist) * Math.min(dist / 80, maxMove);

                        eyeLeft.style.transform = `translate(${moveX}px, ${moveY}px)`;
                        eyeRight.style.transform = `translate(${moveX}px, ${moveY}px)`;
                        if (glintLeft) glintLeft.style.transform = `translate(${moveX * 0.8}px, ${moveY * 0.8}px)`;
                        if (glintRight) glintRight.style.transform = `translate(${moveX * 0.8}px, ${moveY * 0.8}px)`;
                    }
                    ticking = false;
                });
                ticking = true;
            }
        });
    }

})();