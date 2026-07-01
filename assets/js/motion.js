document.addEventListener('DOMContentLoaded', function() {
    // Intersection Observer options
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    // Callback function
    const observerCallback = (entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.remove('opacity-0', 'translate-y-8');
                entry.target.classList.add('opacity-100', 'translate-y-0');
                observer.unobserve(entry.target); // Only animate once
            }
        });
    };

    const observer = new IntersectionObserver(observerCallback, observerOptions);

    // Select all elements to reveal
    const revealElements = document.querySelectorAll('.scroll-reveal');
    revealElements.forEach((el, index) => {
        // Add base transition classes
        el.classList.add('transition-all', 'duration-700', 'ease-out', 'opacity-0', 'translate-y-8');
        
        // Stagger delay for grid items
        if (el.dataset.delay) {
            el.style.transitionDelay = `${el.dataset.delay}ms`;
        }
        
        observer.observe(el);
    });

    // Back to Top Logic
    const backToTop = document.getElementById('back-to-top');
    if (backToTop) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 500) {
                backToTop.classList.remove('opacity-0', 'translate-y-10', 'pointer-events-none');
                backToTop.classList.add('opacity-100', 'translate-y-0');
            } else {
                backToTop.classList.add('opacity-0', 'translate-y-10', 'pointer-events-none');
                backToTop.classList.remove('opacity-100', 'translate-y-0');
            }
        });

        backToTop.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // Copy-link share button
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.sbek-copy-link');
        if (!btn || !navigator.clipboard) {
            return;
        }

        const url = btn.dataset.url;
        if (!url) {
            return;
        }

        navigator.clipboard.writeText(url).then(() => {
            const feedback = btn.querySelector('.sbek-copy-feedback');
            if (!feedback) {
                return;
            }
            feedback.classList.remove('opacity-0');
            feedback.classList.add('opacity-100');
            setTimeout(() => {
                feedback.classList.remove('opacity-100');
                feedback.classList.add('opacity-0');
            }, 1500);
        });
    });
});
