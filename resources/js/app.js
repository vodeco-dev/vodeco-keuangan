import './bootstrap';

import Alpine from 'alpinejs';

// Initialize theme based on saved preference or system setting
const defaultTheme = document.querySelector('meta[name="default-theme"]')?.getAttribute('content');
if (
    localStorage.theme === 'dark' ||
    (!('theme' in localStorage) && defaultTheme === 'dark') ||
    (!('theme' in localStorage) && !defaultTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)
) {
    document.documentElement.classList.add('dark');
} else {
    document.documentElement.classList.remove('dark');
}

// Toggle theme and persist preference
window.addEventListener('DOMContentLoaded', () => {
    document.getElementById('theme-toggle')?.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.theme = isDark ? 'dark' : 'light';
    });
});

window.Alpine = Alpine;

Alpine.start();
