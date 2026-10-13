import 'bootstrap/dist/js/bootstrap.bundle.min.js';

document.getElementById('product-search-modal')?.addEventListener('shown.bs.modal', () => {
    document.getElementById('header-product-search').focus();
});

if (document.querySelector('[data-login-back-guard]')) {
    const loginUrl = window.location.href;
    function keepLoginInHistory() {
        if (!window.history.state?.loginBackGuard) {
            window.history.pushState({ loginBackGuard: true }, '', loginUrl);
        }
    }
    keepLoginInHistory();
    window.addEventListener('popstate', () => {
        window.history.pushState({ loginBackGuard: true }, '', loginUrl);
    });
    window.addEventListener('pageshow', keepLoginInHistory);
}

window.addEventListener('pageshow', (event) => {
    if (event.persisted && document.body.dataset.authenticated === 'true') {
        window.location.reload();
    }
});

const themeToggle = document.querySelector('.theme-toggle');
function updateThemeButton() {
    const dark = document.documentElement.dataset.bsTheme === 'dark';
    themeToggle.textContent = dark ? '☀ Light mode' : '☾ Dark mode';
    themeToggle.setAttribute('aria-pressed', String(dark));
}
if (themeToggle) {
    updateThemeButton();
    themeToggle.addEventListener('click', () => {
        const theme = document.documentElement.dataset.bsTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.bsTheme = theme;
        try { localStorage.setItem('aj-vibe-theme', theme); } catch {}
        updateThemeButton();
    });
}
const notificationBadge = document.querySelector('[data-notification-count]');
if (notificationBadge) {
    let previousCount;
    let toastTimer;
    async function refreshNotifications() {
        if (document.hidden) return;
        try {
            const response = await fetch(notificationBadge.dataset.countUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const { count } = await response.json();
            notificationBadge.textContent = count;
            notificationBadge.hidden = count === 0;
            if (previousCount !== undefined && count > previousCount) {
                const toast = document.getElementById('activity-toast');
                toast.hidden = false;
                clearTimeout(toastTimer);
                toastTimer = setTimeout(() => { toast.hidden = true; }, 7000);
            }
            previousCount = count;
        } catch {}
    }
    refreshNotifications();
    setInterval(refreshNotifications, 15000);
    document.addEventListener('visibilitychange', refreshNotifications);
}

document.querySelectorAll('input[type="file"]').forEach((input) => {
    const preview = document.getElementById(`${input.id}-preview`);
    if (!preview) return;
    const original = preview.getAttribute('src');
    let objectUrl;
    input.addEventListener('change', () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        const file = input.files[0];
        if (file && ['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            objectUrl = URL.createObjectURL(file);
            preview.src = objectUrl;
            preview.hidden = false;
        } else {
            if (original) preview.src = original;
            else preview.removeAttribute('src');
            preview.hidden = !original;
        }
    });
});
document.addEventListener('submit', (event) => {
    const message = event.target.dataset.confirm;
    if (message && !window.confirm(message)) event.preventDefault();
});

// Reveal content progressively without hiding it when JavaScript is unavailable.
if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('vibe-revealed');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.08 });
    document.querySelectorAll('.fashion-section-heading, .catalog-product-card, .fashion-product, .fashion-editorial-card, .order-history-card, .account-section, .bag-item').forEach((element, index) => {
        element.style.setProperty('--reveal-delay', `${(index % 4) * 45}ms`);
        revealObserver.observe(element);
    });
}
