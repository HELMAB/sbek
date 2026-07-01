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

    // Scroll-driven chrome: back-to-top visibility, sticky-masthead elevation,
    // and the single-post reading-progress bar — all in one passive listener.
    const backToTop = document.getElementById('back-to-top');
    const masthead = document.getElementById('masthead');
    const progress = document.getElementById('sbek-reading-progress');

    const onScroll = () => {
        const y = window.scrollY;

        if (backToTop) {
            const show = y > 500;
            backToTop.classList.toggle('opacity-0', !show);
            backToTop.classList.toggle('translate-y-10', !show);
            backToTop.classList.toggle('pointer-events-none', !show);
            backToTop.classList.toggle('opacity-100', show);
            backToTop.classList.toggle('translate-y-0', show);
        }

        if (masthead) {
            masthead.classList.toggle('is-stuck', y > 32);
        }

        if (progress) {
            const h = document.documentElement.scrollHeight - window.innerHeight;
            const ratio = h > 0 ? Math.min(1, Math.max(0, y / h)) : 0;
            progress.style.transform = 'scaleX(' + ratio + ')';
        }
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
    onScroll();

    if (backToTop) {
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
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
