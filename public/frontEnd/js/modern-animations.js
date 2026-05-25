/* ===================================
   MODERN ANIMATIONS & INTERACTIONS
   GSAP-powered smooth animations
   =================================== */

document.addEventListener('DOMContentLoaded', function() {
    
    // ===================================
    // 1. SMOOTH SCROLL INITIALIZATION
    // ===================================
    if (typeof ScrollSmoother === 'undefined' && 'scrollBehavior' in document.documentElement.style) {
        // Smooth scroll is available
        console.log('Smooth scroll enabled');
    }

    // ===================================
    // 2. HEADER SCROLL EFFECT
    // ===================================
    const header = document.querySelector('.app-navbar') || document.querySelector('.main-header');
    if (header) {
        let lastScrollTop = 0;
        const scrollThreshold = 50;

        window.addEventListener('scroll', function() {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Add scrolled class after threshold
            if (scrollTop > scrollThreshold) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
            
            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        });
    }

    // ===================================
    // 3. PRODUCT CARD ANIMATIONS (DISABLED)
    // ===================================
    /*
    const productCards = document.querySelectorAll('.product_item');
    
    gsap.registerPlugin(ScrollTrigger);
    
    productCards.forEach((card, index) => {
        // Initial state
        gsap.set(card, {
            opacity: 0,
            y: 30
        });

        // Animate on scroll
        gsap.to(card, {
            scrollTrigger: {
                trigger: card,
                start: "top 80%",
                once: true
            },
            opacity: 1,
            y: 0,
            duration: 0.6,
            delay: index * 0.05,
            ease: "power2.out"
        });

        // Hover animation
        card.addEventListener('mouseenter', function() {
            gsap.to(card, {
                scale: 1.02,
                duration: 0.3,
                ease: "back.out"
            });
        });

        card.addEventListener('mouseleave', function() {
            gsap.to(card, {
                scale: 1,
                duration: 0.3,
                ease: "back.out"
            });
        });
    });
    */

    // ===================================
    // 4. FADE IN TEXT ON SCROLL
    // ===================================
    const textElements = document.querySelectorAll('h1, h2, h3, .section-title');
    
    textElements.forEach(element => {
        gsap.set(element, { opacity: 0, y: 20 });
        
        gsap.to(element, {
            scrollTrigger: {
                trigger: element,
                start: "top 80%",
                once: true
            },
            opacity: 1,
            y: 0,
            duration: 0.8,
            ease: "power2.out"
        });
    });

    // ===================================
    // 5. BUTTON RIPPLE EFFECT
    // ===================================
    function createRipple(event) {
        const button = event.currentTarget;
        const ripple = document.createElement('span');
        
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');
        
        button.appendChild(ripple);
        
        setTimeout(() => ripple.remove(), 600);
    }

    // Add ripple to buttons
    const buttons = document.querySelectorAll('.btn, .addcartbutton, .compare_store');
    buttons.forEach(button => {
        button.addEventListener('click', createRipple);
    });

    // ===================================
    // 6. OFFCANVAS PANEL TOGGLE
    // ===================================
    const offcanvasToggles = document.querySelectorAll('[data-toggle="offcanvas"]');
    const offcanvasPanels = document.querySelectorAll('.offcanvas-panel');
    const offcanvasOverlay = document.querySelector('.offcanvas-overlay');

    offcanvasToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const target = this.getAttribute('data-target');
            const panel = document.querySelector(target);
            
            if (panel) {
                openOffcanvas(panel);
            }
        });
    });

    function openOffcanvas(panel) {
        gsap.to(panel, {
            x: 0,
            opacity: 1,
            duration: 0.4,
            ease: "power2.out",
            onStart: () => {
                panel.classList.add('active');
                if (offcanvasOverlay) offcanvasOverlay.classList.add('active');
            }
        });

        // Disable body scroll
        document.body.style.overflow = 'hidden';
    }

    function closeOffcanvas(panel) {
        gsap.to(panel, {
            x: -panel.offsetWidth,
            opacity: 0,
            duration: 0.4,
            ease: "power2.in",
            onComplete: () => {
                panel.classList.remove('active');
                if (offcanvasOverlay) offcanvasOverlay.classList.remove('active');
            }
        });

        // Re-enable body scroll
        document.body.style.overflow = 'auto';
    }

    // Close button handlers
    document.querySelectorAll('.offcanvas-close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            const panel = this.closest('.offcanvas-panel');
            if (panel) closeOffcanvas(panel);
        });
    });

    // Overlay click to close
    if (offcanvasOverlay) {
        offcanvasOverlay.addEventListener('click', function() {
            offcanvasPanels.forEach(panel => {
                if (panel.classList.contains('active')) {
                    closeOffcanvas(panel);
                }
            });
        });
    }

    // ===================================
    // 7. MEGA MENU ANIMATION
    // ===================================
    const megaMenuTriggers = document.querySelectorAll('[data-toggle="mega-menu"]');
    
    megaMenuTriggers.forEach(trigger => {
        trigger.addEventListener('mouseenter', function() {
            const target = this.getAttribute('data-target');
            const megaMenu = document.querySelector(target);
            
            if (megaMenu) {
                megaMenu.classList.add('active');
                gsap.to(megaMenu, {
                    opacity: 1,
                    pointerEvents: 'auto',
                    duration: 0.3,
                    ease: "power2.out"
                });
            }
        });

        trigger.addEventListener('mouseleave', function() {
            const target = this.getAttribute('data-target');
            const megaMenu = document.querySelector(target);
            
            if (megaMenu) {
                gsap.to(megaMenu, {
                    opacity: 0,
                    pointerEvents: 'none',
                    duration: 0.2,
                    ease: "power2.in",
                    onComplete: () => {
                        megaMenu.classList.remove('active');
                    }
                });
            }
        });
    });

    // ===================================
    // 8. COUNTER ANIMATION
    // ===================================
    const counters = document.querySelectorAll('[data-count]');
    
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-count'));
        const speed = parseInt(counter.getAttribute('data-speed')) || 1000;

        gsap.to(counter, {
            innerHTML: target,
            duration: speed / 1000,
            snap: { innerHTML: 1 },
            scrollTrigger: {
                trigger: counter,
                start: "top 80%",
                once: true
            },
            ease: "power1.out"
        });
    });

    // ===================================
    // 9. SEARCH DROPDOWN ANIMATION
    // ===================================
    const searchInput = document.querySelector('.search_keyword, .msearch_keyword');
    const searchResult = document.querySelector('.search_result');

    if (searchInput && searchResult) {
        searchInput.addEventListener('focus', function() {
            gsap.to(searchResult, {
                opacity: 1,
                pointerEvents: 'auto',
                duration: 0.3
            });
        });

        searchInput.addEventListener('blur', function() {
            setTimeout(() => {
                gsap.to(searchResult, {
                    opacity: 0,
                    pointerEvents: 'none',
                    duration: 0.3
                });
            }, 200);
        });
    }

    // ===================================
    // 10. MODAL ANIMATIONS
    // ===================================
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        const modalInstance = new (window.bootstrap?.Modal || window.Modal)(modal);
        
        modal.addEventListener('show.bs.modal', function() {
            gsap.fromTo(modal,
                { opacity: 0, scale: 0.9 },
                { opacity: 1, scale: 1, duration: 0.3, ease: "back.out" }
            );
        });
    });

    // ===================================
    // 11. PARALLAX EFFECT
    // ===================================
    const parallaxElements = document.querySelectorAll('[data-parallax]');
    
    parallaxElements.forEach(element => {
        gsap.to(element, {
            scrollTrigger: {
                trigger: element,
                onUpdate: (self) => {
                    const speed = element.getAttribute('data-parallax') || 0.5;
                    gsap.set(element, {
                        y: self.getVelocity() * speed * 0.001
                    });
                }
            }
        });
    });

    // ===================================
    // 12. LAZY LOAD IMAGES
    // ===================================
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.getAttribute('data-src');
                    img.removeAttribute('data-src');
                    
                    gsap.to(img, {
                        opacity: 1,
                        duration: 0.5
                    });
                    
                    observer.unobserve(img);
                }
            });
        });

        lazyImages.forEach(img => {
            gsap.set(img, { opacity: 0 });
            imageObserver.observe(img);
        });
    }

    // ===================================
    // 13. SCROLL TO TOP BUTTON
    // ===================================
    const scrollTopBtn = document.querySelector('[data-scroll-top]');
    
    if (scrollTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                gsap.to(scrollTopBtn, {
                    opacity: 1,
                    pointerEvents: 'auto',
                    duration: 0.3
                });
            } else {
                gsap.to(scrollTopBtn, {
                    opacity: 0,
                    pointerEvents: 'none',
                    duration: 0.3
                });
            }
        });

        scrollTopBtn.addEventListener('click', function(e) {
            e.preventDefault();
            gsap.to(window, {
                scrollTo: 0,
                duration: 0.8,
                ease: "power2.inOut"
            });
        });
    }

    // ===================================
    // 14. FORM INPUT FOCUS EFFECT
    // ===================================
    const formInputs = document.querySelectorAll('input, textarea, select');
    
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            const parent = this.parentElement;
            gsap.to(parent, {
                scale: 1.02,
                duration: 0.2,
                ease: "power2.out"
            });
        });

        input.addEventListener('blur', function() {
            const parent = this.parentElement;
            gsap.to(parent, {
                scale: 1,
                duration: 0.2,
                ease: "power2.out"
            });
        });
    });

});

// ===================================
// CSS FOR RIPPLE EFFECT
// ===================================
const style = document.createElement('style');
style.textContent = `
    .btn, .addcartbutton, .compare_store {
        position: relative;
        overflow: hidden;
    }
    
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.6);
        pointer-events: none;
        animation: rippleEffect 0.6s ease-out;
    }
    
    @keyframes rippleEffect {
        0% {
            transform: scale(0);
            opacity: 1;
        }
        100% {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
