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

    // Attach listener to window scroll and Lenis scroll ticker
    window.addEventListener('scroll', updateActiveNavState, { passive: true });
    if (window.lenis) {
        window.lenis.on('scroll', updateActiveNavState);
    } else {
        const checkLenisTimer = setInterval(() => {
            if (window.lenis) {
                window.lenis.on('scroll', updateActiveNavState);
                clearInterval(checkLenisTimer);
            }
        }, 300);
        setTimeout(() => clearInterval(checkLenisTimer), 5000);
    }

    // Initial state check
    updateActiveNavState();

    // 2b. Smooth Anchor Click Handler using Lenis Engine
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (!targetId || targetId === '#') return;
            const targetEl = document.querySelector(targetId);
            if (targetEl) {
                e.preventDefault();
                if (window.lenis) {
                    window.lenis.scrollTo(targetEl, { duration: 1.2 });
                } else {
                    targetEl.scrollIntoView({ behavior: 'smooth' });
                }
                const cleanId = targetId.replace('#', '');
                const mappedId = sectionNavMap[cleanId] || cleanId;
                desktopLinks.forEach(l => l.classList.toggle('active', l.getAttribute('href') === `#${mappedId}`));
                mobileLinks.forEach(l => l.classList.toggle('active', l.getAttribute('href') === `#${mappedId}`));
            }
        });
    });
});

// 3. Mobile Responsive Slide-out Navigation Drawer Toggle
function toggleMenu() {
    const mobileNav = document.getElementById('mobileNav');
    if (mobileNav) {
        mobileNav.classList.toggle('active');
        document.body.classList.toggle('nav-open');
    }
}

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