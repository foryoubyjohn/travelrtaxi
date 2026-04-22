/**
 * Travelr Taxi & Tours Services
 * Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {

    // === MOBILE NAV TOGGLE ===
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('open');
            this.classList.toggle('active');
        });
        // Close on link click
        navMenu.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                navMenu.classList.remove('open');
                navToggle.classList.remove('active');
            });
        });
    }

    // === STICKY NAV SHADOW ===
    const mainNav = document.getElementById('mainNav');
    if (mainNav) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                mainNav.classList.add('scrolled');
            } else {
                mainNav.classList.remove('scrolled');
            }
        });
    }

    // === BACK TO TOP ===
    const backToTop = document.getElementById('backToTop');
    if (backToTop) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 400) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });
        backToTop.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // === SCROLL ANIMATIONS (Intersection Observer) ===
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe elements with data-aos attribute
    document.querySelectorAll('[data-aos]').forEach(function(el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });

    // Observe service cards, fleet cards, etc.
    document.querySelectorAll('.service-card, .fleet-cat-card, .vehicle-card, .feature-item, .testimonial-card, .pricing-card, .contact-card').forEach(function(el, index) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease ' + (index % 4) * 0.1 + 's';
        observer.observe(el);
    });

    // Add visible class styles
    const style = document.createElement('style');
    style.textContent = '.animate-visible { opacity: 1 !important; transform: translateY(0) !important; }';
    document.head.appendChild(style);

    // === COUNTER ANIMATION ===
    const counters = document.querySelectorAll('.stat-number[data-count]');
    if (counters.length > 0) {
        const counterObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const target = parseInt(entry.target.dataset.count);
                    let current = 0;
                    const increment = target / 60;
                    const timer = setInterval(function() {
                        current += increment;
                        if (current >= target) {
                            entry.target.textContent = target.toLocaleString() + '+';
                            clearInterval(timer);
                        } else {
                            entry.target.textContent = Math.floor(current).toLocaleString();
                        }
                    }, 30);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(function(counter) {
            counterObserver.observe(counter);
        });
    }

    // === FAQ ACCORDION ===
    document.querySelectorAll('.faq-question').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const answer = this.nextElementSibling;
            const isOpen = answer.classList.contains('open');

            // Close all
            document.querySelectorAll('.faq-answer').forEach(function(a) { a.classList.remove('open'); });
            document.querySelectorAll('.faq-question').forEach(function(q) { q.classList.remove('active'); });

            // Toggle current
            if (!isOpen) {
                answer.classList.add('open');
                this.classList.add('active');
            }
        });
    });

    // === HERO PARTICLES ===
    const heroParticles = document.getElementById('heroParticles');
    if (heroParticles) {
        for (let i = 0; i < 30; i++) {
            const particle = document.createElement('div');
            particle.style.cssText = `
                position: absolute;
                width: ${Math.random() * 6 + 2}px;
                height: ${Math.random() * 6 + 2}px;
                background: rgba(255, 212, 0, ${Math.random() * 0.3 + 0.1});
                border-radius: 50%;
                top: ${Math.random() * 100}%;
                left: ${Math.random() * 100}%;
                animation: particleFloat ${Math.random() * 10 + 10}s linear infinite;
            `;
            heroParticles.appendChild(particle);
        }

        const particleStyle = document.createElement('style');
        particleStyle.textContent = `
            @keyframes particleFloat {
                0% { transform: translateY(0) translateX(0); opacity: 0; }
                10% { opacity: 1; }
                90% { opacity: 1; }
                100% { transform: translateY(-100vh) translateX(${Math.random() * 200 - 100}px); opacity: 0; }
            }
        `;
        document.head.appendChild(particleStyle);
    }

    // === SMOOTH SCROLL FOR ANCHOR LINKS ===
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // === AUTO-DISMISS FLASH MESSAGES ===
    const flashMsg = document.getElementById('flashMessage');
    if (flashMsg) {
        setTimeout(function() {
            flashMsg.style.transition = 'opacity 0.5s';
            flashMsg.style.opacity = '0';
            setTimeout(function() { flashMsg.remove(); }, 500);
        }, 5000);
    }

    // === HOVER TILT EFFECT ON CARDS ===
    document.querySelectorAll('.service-card, .fleet-cat-card').forEach(function(card) {
        card.addEventListener('mousemove', function(e) {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const rotateX = (y - centerY) / 20;
            const rotateY = (centerX - x) / 20;
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-10px)`;
        });
        card.addEventListener('mouseleave', function() {
            card.style.transform = '';
        });
    });

    // === TYPING EFFECT FOR HERO (optional enhancement) ===
    const heroSub = document.querySelector('.hero-sub');
    if (heroSub) {
        const text = heroSub.textContent;
        heroSub.textContent = '';
        heroSub.style.opacity = '1';
        let i = 0;
        function typeWriter() {
            if (i < text.length) {
                heroSub.textContent += text.charAt(i);
                i++;
                setTimeout(typeWriter, 30);
            }
        }
        setTimeout(typeWriter, 1500);
    }

    console.log('Travelr Taxi & Tours - Ready to ride! 🚕');
});
