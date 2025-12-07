(function() {
    'use strict';

    function initCarousel(carousel) {
        const container = carousel.querySelector('.ssb-carousel-container');
        const slides = carousel.querySelectorAll('.ssb-carousel-slide');
        const prevBtn = carousel.querySelector('.ssb-carousel-prev');
        const nextBtn = carousel.querySelector('.ssb-carousel-next');
        const dots = carousel.querySelectorAll('.ssb-carousel-dot');
        
        if (!container || slides.length === 0) return;

        let currentSlide = 0;
        let autoplayInterval = null;

        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.toggle('active', i === index);
            });
            
            if (dots.length > 0) {
                dots.forEach((dot, i) => {
                    dot.classList.toggle('active', i === index);
                });
            }
            
            currentSlide = index;
        }

        function nextSlide() {
            const next = (currentSlide + 1) % slides.length;
            showSlide(next);
        }

        function prevSlide() {
            const prev = (currentSlide - 1 + slides.length) % slides.length;
            showSlide(prev);
        }

        function goToSlide(index) {
            if (index >= 0 && index < slides.length) {
                showSlide(index);
            }
        }

        // Navigation buttons
        if (nextBtn) {
            nextBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                nextSlide();
                resetAutoplay();
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                prevSlide();
                resetAutoplay();
            });
        }

        // Dots navigation
        dots.forEach((dot, index) => {
            dot.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                goToSlide(index);
                resetAutoplay();
            });
        });

        // Keyboard navigation
        carousel.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') {
                prevSlide();
                resetAutoplay();
            } else if (e.key === 'ArrowRight') {
                nextSlide();
                resetAutoplay();
            }
        });

        // Touch/swipe support
        let touchStartX = 0;
        let touchEndX = 0;

        carousel.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        carousel.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });

        function handleSwipe() {
            const swipeThreshold = 50;
            const diff = touchStartX - touchEndX;
            
            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    nextSlide();
                } else {
                    prevSlide();
                }
                resetAutoplay();
            }
        }

        // Autoplay (optional - for listing page and single page if enabled)
        function startAutoplay() {
            if (slides.length <= 1) return;
            // Slower interval: 6000ms (6 seconds) for better viewing experience
            var interval = 6000;
            autoplayInterval = setInterval(nextSlide, interval);
        }

        function resetAutoplay() {
            if (autoplayInterval) {
                clearInterval(autoplayInterval);
                startAutoplay();
            }
        }

        // Pause on hover
        carousel.addEventListener('mouseenter', function() {
            if (autoplayInterval) {
                clearInterval(autoplayInterval);
            }
        });

        carousel.addEventListener('mouseleave', function() {
            if (slides.length > 1) {
                startAutoplay();
            }
        });

        // Start autoplay for listing carousels and single page carousels if enabled
        if (carousel.dataset.carouselId && 
            (carousel.dataset.carouselId.startsWith('shortlet-') || 
             (carousel.dataset.carouselId.startsWith('single-shortlet-') && carousel.dataset.autoplay === 'true'))) {
            startAutoplay();
        }

        // Make carousel focusable for keyboard navigation
        carousel.setAttribute('tabindex', '0');
    }

    // Initialize all carousels on page load
    function initAllCarousels() {
        const carousels = document.querySelectorAll('.ssb-carousel');
        carousels.forEach(initCarousel);
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllCarousels);
    } else {
        initAllCarousels();
    }

    // Re-initialize for dynamically loaded content
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('ssb-carousels-updated', initAllCarousels);
    }
})();