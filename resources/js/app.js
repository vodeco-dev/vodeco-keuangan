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

document.addEventListener('DOMContentLoaded', function () {
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');

    if (togglePassword) {
        togglePassword.addEventListener('click', function (e) {
            // toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // toggle the eye icon
            this.querySelectorAll('.eye-open').forEach(icon => icon.classList.toggle('hidden'));
            this.querySelectorAll('.eye-closed').forEach(icon => icon.classList.toggle('hidden'));
        });
    }
});