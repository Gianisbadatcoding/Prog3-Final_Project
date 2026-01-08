// Global UI interactions and subtle animations
document.addEventListener('DOMContentLoaded', () => {
    // Fade-in main content / login / welcome containers
    const fadeTargets = document.querySelectorAll(
        '.main-content, .login-container, .welcome-container'
    );
    fadeTargets.forEach(el => {
        el.classList.add('fade-in-ready');
        requestAnimationFrame(() => {
            el.classList.add('fade-in-active');
        });
    });

    // Button press/ripple-like effect
    document.querySelectorAll('.btn, .btn-primary, .btn-secondary').forEach(btn => {
        btn.addEventListener('mousedown', () => {
            btn.classList.add('btn-pressed');
        });
        ['mouseup', 'mouseleave'].forEach(evt => {
            btn.addEventListener(evt, () => {
                btn.classList.remove('btn-pressed');
            });
        });
    });

    // Scroll reveal for cards and stat sections
    const revealTargets = document.querySelectorAll(
        '.stat-card, .action-card, .data-table-modern tbody tr, .form-container-modern'
    );

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(
            entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('reveal-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.1 }
        );

        revealTargets.forEach(el => {
            el.classList.add('reveal-init');
            observer.observe(el);
        });
    } else {
        // Fallback: just show them
        revealTargets.forEach(el => el.classList.add('reveal-visible'));
    }
});


