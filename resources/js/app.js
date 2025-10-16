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

window.invoiceTabsComponent = function (config) {
    return {
        id: config.id,
        variant: config.variant || 'internal',
        activeTab: config.defaultTab || 'down_payment',
        form: null,
        itemsContainer: null,
        addItemButton: null,
        totalElement: null,
        categoryOptions: [],
        itemIndex: 0,
        referenceUrlTemplate: '',
        settlementInvoiceInput: null,
        settlementRemainingInput: null,
        settlementPaidInput: null,
        settlementSummary: null,
        settlementSummaryFields: {},
        settlementError: null,
        settlementFetchTimeout: null,
        settlementAbortController: null,
        init() {
            this.form = document.getElementById(this.$el.dataset.formId) || this.$el.closest('form');
            this.itemsContainer = this.$el.querySelector('[data-items-container]');
            this.addItemButton = this.$el.querySelector('[data-add-item]');
            this.totalElement = this.$el.querySelector('[data-total-amount]');
            this.categoryOptions = JSON.parse(this.$el.dataset.categoryOptions || '[]');
            this.itemIndex = this.itemsContainer ? this.itemsContainer.querySelectorAll('[data-invoice-item]').length : 0;
            this.referenceUrlTemplate = this.$el.dataset.referenceUrlTemplate || '';
            this.settlementInvoiceInput = this.$el.querySelector('[name="settlement_invoice_number"]');
            this.settlementRemainingInput = this.$el.querySelector('[name="settlement_remaining_balance"]');
            this.settlementPaidInput = this.$el.querySelector('[name="settlement_paid_amount"]');
            this.settlementSummary = this.$el.querySelector('[data-settlement-summary]');
            this.settlementError = this.$el.querySelector('[data-settlement-error]');
            this.settlementSummaryFields = {
                client: this.$el.querySelector('[data-settlement-client]'),
                status: this.$el.querySelector('[data-settlement-status]'),
                whatsapp: this.$el.querySelector('[data-settlement-whatsapp]'),
                total: this.$el.querySelector('[data-settlement-total]'),
                downPayment: this.$el.querySelector('[data-settlement-down-payment]'),
                remaining: this.$el.querySelector('[data-settlement-remaining]'),
                dueDate: this.$el.querySelector('[data-settlement-due-date]'),
                address: this.$el.querySelector('[data-settlement-address]'),
            };

            this.initPriceInputs();
            this.initQuantityInputs();
            this.setupAddItem();
            this.setupRemoveItem();
            this.setupFormSubmit();
            this.setupSettlementReferenceLookup();
            this.applyTabScope();
            this.updateTotal();

            window.dispatchEvent(new CustomEvent('invoice-transaction-tab-changed', {
                detail: { tab: this.activeTab, id: this.id },
            }));
        },
        setTab(tab) {
            this.activeTab = tab;
            this.applyTabScope();
            this.updateTotal();
            window.dispatchEvent(new CustomEvent('invoice-transaction-tab-changed', {
                detail: { tab: this.activeTab, id: this.id },
            }));
        },
        tabClass(tab) {
            const activeClasses = this.variant === 'public'
                ? 'px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 text-white shadow'
                : 'px-4 py-2 text-sm font-semibold rounded-lg bg-blue-600 text-white shadow';
            const inactiveClasses = 'px-4 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200';

            return this.activeTab === tab ? activeClasses : inactiveClasses;
        },
        applyTabScope() {
            const scope = this.activeTab;
            const isSettlement = scope === 'settlement';
            const isPassThrough = scope === 'pass_through';
            this.$el.querySelectorAll('[data-transaction-scope]').forEach((element) => {
                const targetScope = element.dataset.transactionScope;
                if (targetScope === 'settlement') {
                    const enable = isSettlement;
                    element.disabled = !enable;
                    if (element.dataset.settlementRequired === 'true') {
                        element.required = enable;
                    }
                    if (!enable && element.type === 'radio') {
                        element.checked = false;
                    }
                } else if (targetScope === 'down-payment') {
                    const enable = scope === 'down_payment';
                    element.disabled = !enable;
                    if (element.dataset.downPaymentRequired === 'true') {
                        element.required = enable;
                    }
                } else if (targetScope === 'line-item') {
                    const disable = isSettlement || isPassThrough;
                    element.disabled = disable;
                }
            });

            const itemsWrapper = this.$el.querySelector('[data-items-wrapper]');
            if (itemsWrapper) {
                itemsWrapper.style.display = isSettlement || isPassThrough ? 'none' : '';
            }

            const settlementSection = this.$el.querySelector('[data-tab-visible="settlement"]');
            if (settlementSection) {
                settlementSection.style.display = isSettlement ? '' : 'none';
            }

            if (this.addItemButton) {
                const disabled = isSettlement || isPassThrough;
                this.addItemButton.disabled = disabled;
                this.addItemButton.classList.toggle('opacity-50', disabled);
                this.addItemButton.classList.toggle('cursor-not-allowed', disabled);
            }

            const totalWrapper = this.$el.querySelector('[data-total-wrapper]');
            if (totalWrapper) {
                totalWrapper.style.display = isSettlement || isPassThrough ? 'none' : '';
            }

            this.handleSettlementScopeChange();
        },
        initPriceInputs() {
            this.$el.querySelectorAll('[data-role="price-input"]').forEach((input) => {
                this.formatPrice(input);
                input.addEventListener('input', () => {
                    this.formatPrice(input);
                    this.updateTotal();
                });
                input.addEventListener('blur', () => this.formatPrice(input));
            });
        },
        initQuantityInputs() {
            this.$el.querySelectorAll('[data-role="quantity-input"]').forEach((input) => {
                input.addEventListener('input', () => this.updateTotal());
            });
        },
        setupAddItem() {
            if (!this.addItemButton || !this.itemsContainer) {
                return;
            }

            const template = this.$el.querySelector('template[data-item-template]');
            this.addItemButton.addEventListener('click', () => {
                if (!template) {
                    return;
                }

                const html = template.innerHTML.replace(/__INDEX__/g, String(this.itemIndex));
                const fragment = document.createElement('template');
                fragment.innerHTML = html.trim();
                const element = fragment.content.firstElementChild;

                if (!element) {
                    return;
                }

                const categorySelect = element.querySelector('select[name^="items"][name$="[category_id]"]');
                if (categorySelect) {
                    categorySelect.innerHTML = ['<option value="">Pilih kategori pemasukan</option>']
                        .concat(this.categoryOptions.map((option) => `<option value="${option.id}">${option.name}</option>`))
                        .join('');
                }

                this.itemsContainer.appendChild(element);

                element.querySelectorAll('[data-role="price-input"]').forEach((input) => {
                    this.formatPrice(input);
                    input.addEventListener('input', () => {
                        this.formatPrice(input);
                        this.updateTotal();
                    });
                    input.addEventListener('blur', () => this.formatPrice(input));
                });

                element.querySelectorAll('[data-role="quantity-input"]').forEach((input) => {
                    input.addEventListener('input', () => this.updateTotal());
                });

                this.itemIndex += 1;
                this.updateTotal();
            });
        },
        setupRemoveItem() {
            if (!this.itemsContainer) {
                return;
            }

            this.itemsContainer.addEventListener('click', (event) => {
                const button = event.target.closest('[data-remove-item]');
                if (!button) {
                    return;
                }

                const item = button.closest('[data-invoice-item]');
                if (item) {
                    item.remove();
                    this.updateTotal();
                }
            });
        },
        setupFormSubmit() {
            if (!this.form) {
                return;
            }

            this.form.addEventListener('submit', () => {
                this.$el.querySelectorAll('[data-role="price-input"]').forEach((input) => {
                    if (input.disabled) {
                        return;
                    }

                    input.value = input.dataset.rawValue || '';
                });
            });
        },
        setupSettlementReferenceLookup() {
            if (!this.settlementInvoiceInput || !this.referenceUrlTemplate) {
                return;
            }

            this.settlementInvoiceInput.addEventListener('input', () => {
                this.scheduleSettlementReferenceFetch();
            });

            this.settlementInvoiceInput.addEventListener('blur', () => {
                this.scheduleSettlementReferenceFetch(true);
            });
        },
        handleSettlementScopeChange() {
            if (!this.settlementInvoiceInput) {
                return;
            }

            if (this.activeTab === 'settlement') {
                this.scheduleSettlementReferenceFetch(true);
            } else {
                this.clearSettlementFeedback();
            }
        },
        scheduleSettlementReferenceFetch(immediate = false) {
            if (!this.settlementInvoiceInput || !this.referenceUrlTemplate) {
                return;
            }

            if (this.activeTab !== 'settlement') {
                this.clearSettlementFeedback();
                return;
            }

            const number = (this.settlementInvoiceInput.value || '').trim();

            if (this.settlementFetchTimeout) {
                clearTimeout(this.settlementFetchTimeout);
            }

            if (!number) {
                this.clearSettlementFeedback();
                return;
            }

            const fetchAction = () => {
                this.settlementFetchTimeout = null;
                this.fetchSettlementReference(number);
            };

            if (immediate) {
                fetchAction();
            } else {
                this.settlementFetchTimeout = setTimeout(fetchAction, 400);
            }
        },
        fetchSettlementReference(number) {
            if (!this.referenceUrlTemplate) {
                return;
            }

            const url = this.referenceUrlTemplate.replace('__NUMBER__', encodeURIComponent(number));

            if (this.settlementAbortController) {
                this.settlementAbortController.abort();
            }

            this.settlementAbortController = new AbortController();

            fetch(url, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
                signal: this.settlementAbortController.signal,
            })
                .then(async (response) => {
                    if (!response.ok) {
                        let message = 'Gagal mengambil data invoice referensi.';

                        try {
                            const payload = await response.json();
                            if (payload && typeof payload.message === 'string') {
                                message = payload.message;
                            }
                        } catch (error) {
                            // ignore parsing error
                        }

                        this.showSettlementError(message);
                        return;
                    }

                    const data = await response.json();
                    this.showSettlementSummary(data);
                })
                .catch((error) => {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    this.showSettlementError('Tidak dapat memuat data invoice referensi.');
                })
                .finally(() => {
                    this.settlementAbortController = null;
                });
        },
        showSettlementSummary(data) {
            this.clearSettlementFeedback();

            if (!this.settlementSummary) {
                return;
            }

            const currencyFormatter = (value) => this.formatCurrency(value ?? 0);

            if (this.settlementSummaryFields.client) {
                this.settlementSummaryFields.client.textContent = data?.client_name || '-';
            }

            if (this.settlementSummaryFields.status) {
                const status = (data?.status || '-').toString().replace(/_/g, ' ');
                this.settlementSummaryFields.status.textContent = status
                    .split(' ')
                    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');
            }

            if (this.settlementSummaryFields.whatsapp) {
                this.settlementSummaryFields.whatsapp.textContent = data?.client_whatsapp || '-';
            }

            if (this.settlementSummaryFields.total) {
                this.settlementSummaryFields.total.textContent = currencyFormatter(data?.total);
            }

            if (this.settlementSummaryFields.downPayment) {
                this.settlementSummaryFields.downPayment.textContent = currencyFormatter(data?.down_payment);
            }

            if (this.settlementSummaryFields.remaining) {
                this.settlementSummaryFields.remaining.textContent = currencyFormatter(data?.remaining_balance);
            }

            if (this.settlementSummaryFields.dueDate) {
                const dueDate = data?.due_date ? new Date(`${data.due_date}T00:00:00`) : null;
                this.settlementSummaryFields.dueDate.textContent = dueDate && !Number.isNaN(dueDate.getTime())
                    ? dueDate.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' })
                    : '-';
            }

            if (this.settlementSummaryFields.address) {
                this.settlementSummaryFields.address.textContent = data?.client_address || '-';
            }

            this.settlementSummary.classList.remove('hidden');

            this.setPriceInputValue(this.settlementRemainingInput, data?.remaining_balance);
            this.setPriceInputValue(this.settlementPaidInput, data?.remaining_balance);
        },
        showSettlementError(message) {
            if (this.settlementSummary) {
                this.settlementSummary.classList.add('hidden');
            }

            if (this.settlementError) {
                this.settlementError.textContent = message;
                this.settlementError.classList.remove('hidden');
            }
        },
        clearSettlementFeedback() {
            if (this.settlementError) {
                this.settlementError.textContent = '';
                this.settlementError.classList.add('hidden');
            }

            if (this.settlementSummary) {
                this.settlementSummary.classList.add('hidden');
            }
        },
        setPriceInputValue(input, value) {
            if (!input) {
                return;
            }

            const numeric = Number(value);
            const sanitized = Number.isFinite(numeric) ? Math.max(Math.round(numeric), 0) : 0;
            const rawString = String(sanitized);

            input.dataset.rawValue = rawString;
            input.value = rawString ? rawString.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
        },
        formatPrice(input) {
            const raw = (input.value || '').replace(/\D/g, '');
            input.dataset.rawValue = raw;
            input.value = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
        },
        formatCurrency(value) {
            const numeric = Number(value);
            const resolved = Number.isFinite(numeric) ? numeric : 0;

            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
            }).format(resolved);
        },
        updateTotal() {
            if (!this.itemsContainer || !this.totalElement) {
                return;
            }

            let total = 0;
            this.itemsContainer.querySelectorAll('[data-invoice-item]').forEach((item) => {
                const quantityInput = item.querySelector('[data-role="quantity-input"]');
                const priceInput = item.querySelector('[data-role="price-input"]');
                const quantity = parseInt(quantityInput?.value || '0', 10) || 0;
                const price = parseInt(priceInput?.dataset.rawValue || '0', 10) || 0;
                total += quantity * price;
            });

            this.totalElement.textContent = this.formatCurrency(total || 0);
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