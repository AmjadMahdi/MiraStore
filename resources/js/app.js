import './bootstrap';

/**
 * Animates a small bag icon flying from the given source element to the
 * nav cart icon, then removes itself. Purely cosmetic — never blocks
 * the actual add-to-cart request.
 */
window.flyToCart = function (sourceEl) {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const target = document.getElementById('nav-cart-icon');
    if (!sourceEl || !target) {
        return;
    }

    const from = sourceEl.getBoundingClientRect();
    const to = target.getBoundingClientRect();

    const flyer = document.createElement('div');
    flyer.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;color:#000000">
            <path d="M2.25 2.75a.75.75 0 000 1.5h1.106c.07 0 .13.05.148.118l1.62 6.482a2.75 2.75 0 002.667 2.15h5.318a2.75 2.75 0 002.667-2.15l1.093-4.372a.75.75 0 00-.728-.928H5.51l-.28-1.122a1.75 1.75 0 00-1.698-1.35H2.25z" />
            <path d="M6.5 17a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM13.5 17a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
        </svg>
    `;
    flyer.style.position = 'fixed';
    flyer.style.left = `${from.left + from.width / 2 - 10}px`;
    flyer.style.top = `${from.top + from.height / 2 - 10}px`;
    flyer.style.width = '20px';
    flyer.style.height = '20px';
    flyer.style.display = 'flex';
    flyer.style.alignItems = 'center';
    flyer.style.justifyContent = 'center';
    flyer.style.background = '#ffffff';
    flyer.style.borderRadius = '9999px';
    flyer.style.boxShadow = '0 2px 8px rgba(0,0,0,0.25)';
    flyer.style.zIndex = '9999';
    flyer.style.pointerEvents = 'none';
    flyer.style.setProperty('--fly-x', `${(to.left + to.width / 2) - (from.left + from.width / 2)}px`);
    flyer.style.setProperty('--fly-y', `${(to.top + to.height / 2) - (from.top + from.height / 2)}px`);
    flyer.className = 'fly-to-cart';

    document.body.appendChild(flyer);
    flyer.addEventListener('animationend', () => flyer.remove());
};
