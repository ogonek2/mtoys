export function initScrollReveal() {
    const targets = document.querySelectorAll('[data-product-grid], [data-scroll-reveal], section .grid > a');
    if (!targets.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('scroll-reveal--visible');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -20px 0px' });

    targets.forEach((el, index) => {
        el.classList.add('scroll-reveal');
        el.style.transitionDelay = `${Math.min(index * 0.03, 0.15)}s`;
        observer.observe(el);
    });
}
