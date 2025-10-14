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

window.passThroughForm = function passThroughForm(config = {}) {
    console.log('passThroughForm initialized with config:', config);
    const packages = Array.isArray(config.packages) ? config.packages : [];
    const defaults = config.defaults || {};

    const defaultQuantity = Number.parseInt(defaults.quantity, 10);
    const initialQuantity = Number.isFinite(defaultQuantity) && defaultQuantity > 0
        ? String(Math.floor(defaultQuantity))
        : '1';

    return {
        passThroughPackages: packages,
        passThroughPackageId: defaults.packageId || (packages[0]?.id ?? null),
        passThroughQuantityInput: initialQuantity,
        init() {
            if (!this.passThroughPackageId && this.passThroughPackages.length > 0) {
                this.passThroughPackageId = this.passThroughPackages[0].id;
            }
            this.normalizePassThroughQuantity();
        },
        formatPackageOption(pkg) {
            if (!pkg || typeof pkg !== 'object') {
                return '';
            }
            const label = pkg.customer_label || '';
            const name = pkg.name || '';
            return label ? `${name} — ${label}` : name;
        },
        updatePassThroughQuantity(value) {
            this.passThroughQuantityInput = String(value ?? '').replace(/[^0-9]/g, '');
        },
        normalizePassThroughQuantity() {
            this.passThroughQuantityInput = String(this.passThroughQuantity());
        },
        passThroughQuantity() {
            const digits = (this.passThroughQuantityInput || '').replace(/[^0-9]/g, '');
            const numeric = Number(digits);
            if (!Number.isFinite(numeric) || numeric < 1) {
                return 1;
            }
            return Math.max(Math.floor(numeric), 1);
        },
        selectedPackage() {
            if (!this.passThroughPackageId) {
                return null;
            }
            return this.passThroughPackages.find((pkg) => String(pkg.id) === String(this.passThroughPackageId)) || null;
        },
        hasPassThroughPackageSelected() {
            return !!this.selectedPackage();
        },
        showsAccountCreationFee() {
            const pkg = this.selectedPackage();
            return pkg ? pkg.customer_type === 'new' : false;
        },
        passThroughDailyBalanceUnit() {
            const pkg = this.selectedPackage();
            return pkg ? Number(pkg.daily_balance) || 0 : 0;
        },
        passThroughDailyBalanceTotal() {
            return this.passThroughDailyBalanceUnit() * this.passThroughQuantity();
        },
        passThroughDurationDays() {
            const pkg = this.selectedPackage();
            return pkg ? Number(pkg.duration_days) || 0 : 0;
        },
        passThroughAdBudgetUnit() {
            return this.passThroughDailyBalanceUnit() * this.passThroughDurationDays();
        },
        passThroughAdBudgetTotal() {
            return this.passThroughAdBudgetUnit() * this.passThroughQuantity();
        },
        passThroughMaintenanceUnit() {
            const pkg = this.selectedPackage();
            return pkg ? Number(pkg.maintenance_fee) || 0 : 0;
        },
        passThroughMaintenanceTotal() {
            return this.passThroughMaintenanceUnit() * this.passThroughQuantity();
        },
        passThroughAccountCreationUnit() {
            const pkg = this.selectedPackage();
            if (!pkg || pkg.customer_type !== 'new') {
                return 0;
            }
            return Number(pkg.account_creation_fee) || 0;
        },
        passThroughAccountCreationTotal() {
            return this.passThroughAccountCreationUnit() * this.passThroughQuantity();
        },
        passThroughTotalPrice() {
            return this.passThroughAdBudgetTotal()
                + this.passThroughMaintenanceTotal()
                + this.passThroughAccountCreationTotal();
        },
        formatCurrency(value) {
            const numeric = Number(value) || 0;

            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            }).format(numeric);
        },
    };
};

window.invoicePortalForm = function invoicePortalForm(config = {}) {
    return {
        activeTab: config.defaultTransaction || 'down_payment',
        init() {
            window.addEventListener('invoice-transaction-tab-changed', (event) => {
                if (!event.detail || !event.detail.tab) {
                    return;
                }
                this.activeTab = event.detail.tab;
            });
        },
        formatCurrency(value) {
            const numeric = Number(value) || 0;

            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            }).format(numeric);
        },
    };
};

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