import '../css/app.css';

// Small progressive enhancements. Everything still works without JS.

// Mobile navigation.
document.querySelectorAll('[data-nav-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const target = document.getElementById(button.dataset.navToggle);
        if (!target) return;
        const open = target.classList.toggle('hidden') === false;
        button.setAttribute('aria-expanded', String(open));
    });
});

// Mobile admin drawer.
const adminSidebar = document.getElementById('admin-sidebar');

if (adminSidebar) {
    const overlay = document.getElementById('sidebar-overlay');
    const openers = document.querySelectorAll('[data-drawer-open]');

    const setDrawer = (open) => {
        adminSidebar.classList.toggle('is-open', open);
        overlay?.classList.toggle('hidden', !open);
        document.body.classList.toggle('drawer-open', open);
        openers.forEach((button) => button.setAttribute('aria-expanded', String(open)));
    };

    openers.forEach((button) => button.addEventListener('click', () => setDrawer(true)));
    document.querySelectorAll('[data-drawer-close]').forEach((button) => button.addEventListener('click', () => setDrawer(false)));
    overlay?.addEventListener('click', () => setDrawer(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setDrawer(false);
    });
}

// Copy-to-clipboard buttons (public event link).
document.querySelectorAll('[data-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
        const value = button.dataset.copy;
        try {
            await navigator.clipboard.writeText(value);
        } catch {
            const input = document.createElement('input');
            input.value = value;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            input.remove();
        }
        const label = button.querySelector('[data-copy-label]');
        const original = label ? label.textContent : null;
        if (label) label.textContent = 'Copied';
        button.classList.add('text-moss-700');
        window.setTimeout(() => {
            if (label && original !== null) label.textContent = original;
            button.classList.remove('text-moss-700');
        }, 1600);
    });
});

// Confirm destructive actions.
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
});

// Auto-focus search on the check-in screen.
const autofocus = document.querySelector('[data-autofocus]');
if (autofocus) autofocus.focus();
