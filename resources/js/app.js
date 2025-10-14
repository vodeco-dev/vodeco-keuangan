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

window.invoicePortalForm = function invoicePortalForm(config = {}) {
    const passThroughDefaults = config.passThroughDefaults || {};

    return {
        activeTab: config.defaultTransaction || 'down_payment',
        passThroughCustomerType: passThroughDefaults.customerType || 'new',
        passThroughDailyBalance: '',
        passThroughDailyBalanceDisplay: '',
        passThroughEstimatedDays: Number(passThroughDefaults.estimatedDays) > 0
            ? Number(passThroughDefaults.estimatedDays)
            : 1,
        passThroughMaintenanceFee: '',
        passThroughMaintenanceFeeDisplay: '',
        passThroughAccountCreationFee: '',
        passThroughAccountCreationFeeDisplay: '',
        init() {
            this.setCurrencyField('DailyBalance', passThroughDefaults.dailyBalance || '');
            this.setCurrencyField('MaintenanceFee', passThroughDefaults.maintenanceFee || '');
            this.setCurrencyField('AccountCreationFee', passThroughDefaults.accountCreationFee || '');

            window.addEventListener('invoice-transaction-tab-changed', (event) => {
                if (!event.detail || !event.detail.tab) {
                    return;
                }

                this.activeTab = event.detail.tab;
            });

            this.$watch('passThroughCustomerType', (value) => {
                if (value !== 'new') {
                    this.setCurrencyField('AccountCreationFee', '');
                }
            });
        },
        updateCurrencyField(kind, value) {
            this.setCurrencyField(kind, value);
        },
        setCurrencyField(kind, value) {
            const digits = String(value || '').replace(/\D/g, '');
            const base = `passThrough${kind}`;

            this[base] = digits;
            this[`${base}Display`] = digits ? this.formatNumber(digits) : '';
        },
        formatNumber(value) {
            return String(value).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },
        formatCurrency(value) {
            const numeric = Number(value) || 0;

            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            }).format(numeric);
        },
        passThroughAmount() {
            const daily = Number(this.passThroughDailyBalance) || 0;
            const days = Number(this.passThroughEstimatedDays) || 0;

            return daily * days;
        },
        maintenanceFeeValue() {
            return Number(this.passThroughMaintenanceFee) || 0;
        },
        accountCreationFeeValue() {
            if (this.passThroughCustomerType !== 'new') {
                return 0;
            }

            return Number(this.passThroughAccountCreationFee) || 0;
        },
        totalPassThrough() {
            return this.passThroughAmount()
                + this.maintenanceFeeValue()
                + this.accountCreationFeeValue();
        },
    };
};