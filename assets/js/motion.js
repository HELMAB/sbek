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
    // the single-post reading-progress bar, and the sidebar TOC scroll-spy —
    // all in one passive listener.
    const backToTop = document.getElementById('back-to-top');
    const masthead = document.getElementById('masthead');
    const progress = document.getElementById('sbek-reading-progress');

    const tocTargets = Array.from(document.querySelectorAll('.sbek-toc a[href*="#"]'))
        .map((link) => {
            const id = decodeURIComponent((link.getAttribute('href') || '').split('#')[1] || '');
            const heading = id ? document.getElementById(id) : null;
            return heading ? { link, heading } : null;
        })
        .filter(Boolean);

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

        if (tocTargets.length) {
            // Active section = the last heading scrolled past the masthead line.
            let active = null;
            tocTargets.forEach((t) => {
                if (t.heading.getBoundingClientRect().top <= 120) {
                    active = t;
                }
            });
            tocTargets.forEach((t) => t.link.classList.toggle('is-active', t === active));
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

    // Copy-link share button. navigator.clipboard needs a secure context, so
    // fall back to a hidden-textarea execCommand copy on http/older WebViews.
    const copyText = (text) => {
        if (navigator.clipboard) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise((resolve, reject) => {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy') ? resolve() : reject(new Error('copy failed'));
            } catch (err) {
                reject(err);
            } finally {
                ta.remove();
            }
        });
    };

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.sbek-copy-link');
        if (!btn) {
            return;
        }

        const url = btn.dataset.url;
        if (!url) {
            return;
        }

        copyText(url).then(() => {
            const feedback = btn.querySelector('.sbek-copy-feedback');
            if (!feedback) {
                return;
            }
            // Filling the aria-live region announces the confirmation to AT;
            // emptying it after the fade lets the next copy re-announce.
            feedback.textContent = feedback.dataset.copiedText || 'Copied';
            feedback.classList.remove('opacity-0');
            feedback.classList.add('opacity-100');
            setTimeout(() => {
                feedback.classList.remove('opacity-100');
                feedback.classList.add('opacity-0');
                setTimeout(() => {
                    feedback.textContent = '';
                }, 300);
            }, 1500);
        }).catch(() => {});
    });

    // Native share sheet (mobile). The buttons ship hidden; only reveal them
    // where navigator.share actually exists.
    if (navigator.share) {
        document.querySelectorAll('.sbek-native-share').forEach((btn) => {
            btn.classList.remove('hidden');
        });
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.sbek-native-share');
            if (!btn) {
                return;
            }
            navigator.share({
                title: btn.dataset.title || document.title,
                url: btn.dataset.url || window.location.href
            }).catch(() => {}); // User dismissed the sheet — not an error.
        });
    }
});
