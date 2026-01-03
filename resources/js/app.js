import "./bootstrap";

import Alpine from "alpinejs";

const defaultTheme = document
  .querySelector('meta[name="default-theme"]')
  ?.getAttribute("content");
if (
  localStorage.theme === "dark" ||
  (!("theme" in localStorage) && defaultTheme === "dark") ||
  (!("theme" in localStorage) &&
    !defaultTheme &&
    window.matchMedia("(prefers-color-scheme: dark)").matches)
) {
  document.documentElement.classList.add("dark");
} else {
  document.documentElement.classList.remove("dark");
}

window.addEventListener("DOMContentLoaded", () => {
  document.getElementById("theme-toggle")?.addEventListener("click", () => {
    const isDark = document.documentElement.classList.toggle("dark");
    localStorage.theme = isDark ? "dark" : "light";
  });
});

window.Alpine = Alpine;

window.passThroughForm = function passThroughForm(config = {}) {
  const packages = Array.isArray(config.packages) ? config.packages : [];
  const defaults = config.defaults || {};

  const firstDefined = (...values) =>
    values.find((value) => value !== undefined && value !== null);
  const sanitizeCurrency = (value) => {
    if (value === undefined || value === null || value === "") {
      return 0;
    }

    if (typeof value === "number" && Number.isFinite(value)) {
      return Math.max(Math.round(value), 0);
    }

    const digits = String(value).replace(/\D/g, "");
    return digits ? Math.max(Number(digits), 0) : 0;
  };
  const sanitizeInteger = (value) => {
    if (value === undefined || value === null || value === "") {
      return 0;
    }

    if (typeof value === "number" && Number.isFinite(value)) {
      return Math.max(Math.floor(value), 0);
    }

    const digits = String(value).replace(/[^0-9]/g, "");
    return digits ? Math.max(Number(digits), 0) : 0;
  };
  const sanitizeCustomerType = (value) => {
    const normalized = String(value ?? "").toLowerCase();
    return normalized === "existing" ? "existing" : "new";
  };

  let normalizedPackageId = "";
  if (
    defaults.packageId !== undefined &&
    defaults.packageId !== null &&
    defaults.packageId !== ""
  ) {
    normalizedPackageId = String(defaults.packageId);
  } else if (
    packages.length > 0 &&
    packages[0]?.id !== undefined &&
    packages[0]?.id !== null
  ) {
    normalizedPackageId = String(packages[0].id);
  } else {
    normalizedPackageId = "custom";
  }

  const defaultQuantity = Number.parseInt(defaults.quantity, 10);
  const initialQuantity =
    Number.isFinite(defaultQuantity) && defaultQuantity > 0
      ? String(Math.floor(defaultQuantity))
      : "1";

  const defaultCustom = defaults.custom || {};
  const defaultUnits = defaults.units || {};
  const defaultTotals = defaults.totals || {};
  const defaultDuration = defaults.durationDays;

  const isCustomDefault = normalizedPackageId === "custom";
  const hasExplicitCustomValues =
    defaultCustom.dailyBalance !== undefined ||
    defaultCustom.durationDays !== undefined ||
    defaultCustom.maintenanceFee !== undefined ||
    defaultCustom.accountCreationFee !== undefined;

  const initialCustomerType = sanitizeCustomerType(defaultCustom.customerType);
  const initialDailyBalance =
    isCustomDefault && !hasExplicitCustomValues
      ? 0
      : sanitizeCurrency(
          firstDefined(
            defaultCustom.dailyBalance,
            isCustomDefault ? undefined : defaultUnits.dailyBalance,
          ),
        );
  const initialDurationDays =
    isCustomDefault && !hasExplicitCustomValues
      ? 0
      : sanitizeInteger(
          firstDefined(
            defaultCustom.durationDays,
            isCustomDefault ? undefined : defaultDuration,
          ),
        );
  const initialMaintenanceFee =
    isCustomDefault && !hasExplicitCustomValues
      ? 0
      : sanitizeCurrency(
          firstDefined(
            defaultCustom.maintenanceFee,
            isCustomDefault ? undefined : defaultUnits.maintenance,
          ),
        );
  const initialAccountCreationFeeRaw =
    isCustomDefault && !hasExplicitCustomValues
      ? 0
      : sanitizeCurrency(
          firstDefined(
            defaultCustom.accountCreationFee,
            isCustomDefault ? undefined : defaultUnits.accountCreation,
          ),
        );

  return {
    passThroughPackages: packages,
    passThroughPackageId: normalizedPackageId,
    passThroughQuantityInput: initialQuantity,
    customFields: {
      customerType: initialCustomerType,
      dailyBalance: initialDailyBalance,
      durationDays: initialDurationDays,
      maintenanceFee: initialMaintenanceFee,
      accountCreationFee:
        initialCustomerType === "new" ? initialAccountCreationFeeRaw : 0,
    },
    init() {
      if (!this.passThroughPackageId) {
        if (
          this.passThroughPackages.length > 0 &&
          this.passThroughPackages[0]?.id !== undefined
        ) {
          this.passThroughPackageId = String(this.passThroughPackages[0].id);
        } else {
          this.passThroughPackageId = "custom";
        }
      }

      this.normalizePassThroughQuantity();

      this.$nextTick(() => {
        this.initializeCustomInputs();

        if (this.customFields.customerType !== "new") {
          this.handleCustomCustomerTypeChange(this.customFields.customerType);
        } else {
          const input = this.customCurrencyInput("accountCreationFee");
          if (input && this.customFields.accountCreationFee > 0) {
            this.setInputFormattedValue(
              input,
              this.customFields.accountCreationFee,
            );
          }
        }

        const shouldInferValues = !isCustomDefault || hasExplicitCustomValues;

        if (shouldInferValues) {
          const adBudgetUnitDefault = sanitizeCurrency(
            firstDefined(defaultTotals.adBudget, defaults.adBudgetTotal),
          );
          const maintenanceTotalDefault = sanitizeCurrency(
            firstDefined(defaultTotals.maintenance, defaults.maintenanceTotal),
          );
          const accountCreationTotalDefault = sanitizeCurrency(
            firstDefined(
              defaultTotals.accountCreation,
              defaults.accountCreationTotal,
            ),
          );
          const totalPriceDefault = sanitizeCurrency(
            firstDefined(defaultTotals.overall, defaults.totalPrice),
          );
          const dailyBalanceTotalDefault = sanitizeCurrency(
            firstDefined(
              defaultTotals.dailyBalance,
              defaults.dailyBalanceTotal,
            ),
          );

          if (
            adBudgetUnitDefault > 0 &&
            this.passThroughDailyBalanceUnit() <= 0
          ) {
            const durationInput = this.customDurationInput();
            if (
              durationInput &&
              this.customFields.durationDays <= 0 &&
              this.customFields.dailyBalance > 0
            ) {
              const inferredDuration = Math.round(
                adBudgetUnitDefault /
                  Math.max(this.customFields.dailyBalance, 1),
              );
              this.customFields.durationDays =
                inferredDuration > 0
                  ? inferredDuration
                  : this.customFields.durationDays;
              durationInput.value =
                this.customFields.durationDays > 0
                  ? String(this.customFields.durationDays)
                  : "";
            }
          }

          if (
            maintenanceTotalDefault > 0 &&
            this.customFields.maintenanceFee <= 0 &&
            this.passThroughQuantity() > 0
          ) {
            this.customFields.maintenanceFee = Math.round(
              maintenanceTotalDefault / this.passThroughQuantity(),
            );
            this.setInputFormattedValue(
              this.customCurrencyInput("maintenanceFee"),
              this.customFields.maintenanceFee,
            );
          }

          if (
            this.customFields.customerType === "new" &&
            accountCreationTotalDefault > 0 &&
            this.customFields.accountCreationFee <= 0 &&
            this.passThroughQuantity() > 0
          ) {
            this.customFields.accountCreationFee = Math.round(
              accountCreationTotalDefault / this.passThroughQuantity(),
            );
            this.setInputFormattedValue(
              this.customCurrencyInput("accountCreationFee"),
              this.customFields.accountCreationFee,
            );
          }

          if (
            totalPriceDefault <= 0 &&
            dailyBalanceTotalDefault > 0 &&
            this.passThroughAdBudgetTotal() <= 0
          ) {
            const inferredDailyBalance = Math.round(
              dailyBalanceTotalDefault /
                Math.max(this.passThroughQuantity(), 1),
            );
            if (
              inferredDailyBalance > 0 &&
              this.customFields.dailyBalance <= 0
            ) {
              this.customFields.dailyBalance = inferredDailyBalance;
              this.setInputFormattedValue(
                this.customCurrencyInput("dailyBalance"),
                this.customFields.dailyBalance,
              );
            }
          }
        }
      });
    },
    formatPackageOption(pkg) {
      if (!pkg || typeof pkg !== "object") {
        return "";
      }
      const label = pkg.customer_label || "";
      const name = pkg.name || "";
      return label ? `${name} — ${label}` : name;
    },
    updatePassThroughQuantity(value) {
      this.passThroughQuantityInput = String(value ?? "").replace(
        /[^0-9]/g,
        "",
      );
    },
    normalizePassThroughQuantity() {
      this.passThroughQuantityInput = String(this.passThroughQuantity());
    },
    passThroughQuantity() {
      const digits = (this.passThroughQuantityInput || "").replace(
        /[^0-9]/g,
        "",
      );
      const numeric = Number(digits);
      if (!Number.isFinite(numeric) || numeric < 1) {
        return 1;
      }
      return Math.max(Math.floor(numeric), 1);
    },
    isCustomSelected() {
      return String(this.passThroughPackageId || "") === "custom";
    },
    selectedPackage() {
      if (!this.passThroughPackageId || this.isCustomSelected()) {
        return null;
      }

      return (
        this.passThroughPackages.find(
          (pkg) => String(pkg.id) === String(this.passThroughPackageId),
        ) || null
      );
    },
    hasPassThroughPackageSelected() {
      return this.isCustomSelected() || !!this.selectedPackage();
    },
    handleCustomCustomerTypeChange(value) {
      const normalized = sanitizeCustomerType(value);
      this.customFields.customerType = normalized;

      if (normalized !== "new") {
        this.customFields.accountCreationFee = 0;
        const input = this.customCurrencyInput("accountCreationFee");
        if (input) {
          this.setInputFormattedValue(input, 0);
        }
      }
    },
    handleCustomCurrencyInput(field, value) {
      const numeric = sanitizeCurrency(value);
      if (
        field === "accountCreationFee" &&
        this.customFields.customerType !== "new"
      ) {
        this.customFields.accountCreationFee = 0;
        const input = this.customCurrencyInput(field);
        if (input) {
          this.setInputFormattedValue(input, 0);
        }
        return;
      }

      if (Object.prototype.hasOwnProperty.call(this.customFields, field)) {
        this.customFields[field] = numeric;
      }

      const input = this.customCurrencyInput(field);
      if (input) {
        this.setInputFormattedValue(input, numeric);
      }
    },
    handleCustomDurationInput(value) {
      const numeric = sanitizeInteger(value);
      this.customFields.durationDays = numeric;
      const input = this.customDurationInput();
      if (input) {
        input.value = numeric > 0 ? String(numeric) : "";
      }
    },
    customCurrencyInput(field) {
      const refs = {
        dailyBalance: this.$refs.customDailyBalanceInput,
        maintenanceFee: this.$refs.customMaintenanceInput,
        accountCreationFee: this.$refs.customAccountCreationInput,
      };

      return refs[field] || null;
    },
    customDurationInput() {
      return this.$refs.customDurationInput || null;
    },
    initializeCustomInputs() {
      this.setInputFormattedValue(
        this.customCurrencyInput("dailyBalance"),
        this.customFields.dailyBalance,
      );
      this.setInputFormattedValue(
        this.customCurrencyInput("maintenanceFee"),
        this.customFields.maintenanceFee,
      );
      if (this.customFields.customerType === "new") {
        this.setInputFormattedValue(
          this.customCurrencyInput("accountCreationFee"),
          this.customFields.accountCreationFee,
        );
      }

      const durationInput = this.customDurationInput();
      if (durationInput) {
        durationInput.value =
          this.customFields.durationDays > 0
            ? String(this.customFields.durationDays)
            : "";
      }
    },
    setInputFormattedValue(input, value) {
      if (!input) {
        return;
      }

      const numeric = Number(value);
      const sanitized = Number.isFinite(numeric)
        ? Math.max(Math.round(numeric), 0)
        : 0;
      input.value = sanitized > 0 ? this.formatNumber(sanitized) : "";
    },
    summaryPackageName() {
      if (this.isCustomSelected()) {
        return "Paket Custom";
      }

      const pkg = this.selectedPackage();
      return pkg?.name || "-";
    },
    summaryCustomerLabel() {
      if (this.isCustomSelected()) {
        return this.customFields.customerType === "existing"
          ? "Pelanggan Lama"
          : "Pelanggan Baru";
      }

      const pkg = this.selectedPackage();
      return pkg?.customer_label || "-";
    },
    showsAccountCreationFee() {
      if (this.isCustomSelected()) {
        return (
          this.customFields.customerType === "new" &&
          this.passThroughAccountCreationUnit() > 0
        );
      }

      const pkg = this.selectedPackage();
      return pkg ? pkg.customer_type === "new" : false;
    },
    passThroughDailyBalanceUnit() {
      if (this.isCustomSelected()) {
        return this.customFields.dailyBalance || 0;
      }

      const pkg = this.selectedPackage();
      return pkg ? Number(pkg.daily_balance) || 0 : 0;
    },
    passThroughDailyBalanceTotal() {
      return this.passThroughDailyBalanceUnit() * this.passThroughQuantity();
    },
    passThroughDurationDays() {
      if (this.isCustomSelected()) {
        return this.customFields.durationDays || 0;
      }

      const pkg = this.selectedPackage();
      return pkg ? Number(pkg.duration_days) || 0 : 0;
    },
    passThroughAdBudgetUnit() {
      return (
        this.passThroughDailyBalanceUnit() * this.passThroughDurationDays()
      );
    },
    passThroughAdBudgetTotal() {
      return this.passThroughAdBudgetUnit() * this.passThroughQuantity();
    },
    passThroughMaintenanceUnit() {
      if (this.isCustomSelected()) {
        return this.customFields.maintenanceFee || 0;
      }

      const pkg = this.selectedPackage();
      return pkg ? Number(pkg.maintenance_fee) || 0 : 0;
    },
    passThroughMaintenanceTotal() {
      return this.passThroughMaintenanceUnit() * this.passThroughQuantity();
    },
    passThroughAccountCreationUnit() {
      if (this.isCustomSelected()) {
        if (this.customFields.customerType !== "new") {
          return 0;
        }

        return this.customFields.accountCreationFee || 0;
      }

      const pkg = this.selectedPackage();
      if (!pkg || pkg.customer_type !== "new") {
        return 0;
      }

      return Number(pkg.account_creation_fee) || 0;
    },
    passThroughAccountCreationTotal() {
      return this.passThroughAccountCreationUnit() * this.passThroughQuantity();
    },
    passThroughTotalPrice() {
      return (
        this.passThroughAdBudgetTotal() +
        this.passThroughMaintenanceTotal() +
        this.passThroughAccountCreationTotal()
      );
    },
    formatNumber(value) {
      const numeric = Number(value);
      const sanitized = Number.isFinite(numeric)
        ? Math.max(Math.round(numeric), 0)
        : 0;
      return sanitized.toLocaleString("id-ID");
    },
    formatNumberForSubmission(value) {
      const numeric = Number(value);
      const sanitized = Number.isFinite(numeric)
        ? Math.max(Math.round(numeric), 0)
        : 0;
      return sanitized.toLocaleString("id-ID");
    },
    formatCurrency(value) {
      const numeric = Number(value) || 0;

      return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
      }).format(numeric);
    },
  };
};

window.invoicePortalForm = function invoicePortalForm(config = {}) {
  return {
    activeTab: config.defaultTransaction || "down_payment",
    init() {
      window.addEventListener("invoice-transaction-tab-changed", (event) => {
        if (!event.detail || !event.detail.tab) {
          return;
        }
        this.activeTab = event.detail.tab;
      });
    },
    formatCurrency(value) {
      const numeric = Number(value) || 0;

      return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
      }).format(numeric);
    },
  };
};

window.invoiceTabsComponent = function (config) {
  return {
    id: config.id,
    variant: config.variant || "internal",
    activeTab: config.defaultTab || "down_payment",
    form: null,
    itemsContainer: null,
    addItemButton: null,
    totalElement: null,
    categoryOptions: [],
    itemIndex: 0,
    referenceUrlTemplate: "",
    settlementInvoiceInput: null,
    settlementRemainingInput: null,
    settlementPaidInput: null,
    settlementSummary: null,
    settlementSummaryFields: {},
    settlementError: null,
    settlementFetchTimeout: null,
    settlementAbortController: null,
    init() {
      this.form =
        document.getElementById(this.$el.dataset.formId) ||
        this.$el.closest("form");
      this.itemsContainer = this.$el.querySelector("[data-items-container]");
      this.addItemButton = this.$el.querySelector("[data-add-item]");
      this.totalElement = this.$el.querySelector("[data-total-amount]");
      this.categoryOptions = JSON.parse(
        this.$el.dataset.categoryOptions || "[]",
      );
      this.itemIndex = this.itemsContainer
        ? this.itemsContainer.querySelectorAll("[data-invoice-item]").length
        : 0;
      this.referenceUrlTemplate = this.$el.dataset.referenceUrlTemplate || "";
      this.settlementInvoiceInput = this.$el.querySelector(
        '[name="settlement_invoice_number"]',
      );
      this.settlementRemainingInput = this.$el.querySelector(
        '[name="settlement_remaining_balance"]',
      );
      this.settlementPaidInput = this.$el.querySelector(
        '[name="settlement_paid_amount"]',
      );
      this.settlementSummary = this.$el.querySelector(
        "[data-settlement-summary]",
      );
      this.settlementError = this.$el.querySelector("[data-settlement-error]");
      this.settlementSummaryFields = {
        client: this.$el.querySelector("[data-settlement-client]"),
        status: this.$el.querySelector("[data-settlement-status]"),
        whatsapp: this.$el.querySelector("[data-settlement-whatsapp]"),
        total: this.$el.querySelector("[data-settlement-total]"),
        downPayment: this.$el.querySelector("[data-settlement-down-payment]"),
        remaining: this.$el.querySelector("[data-settlement-remaining]"),
        dueDate: this.$el.querySelector("[data-settlement-due-date]"),
        address: this.$el.querySelector("[data-settlement-address]"),
      };

      this.initPriceInputs();
      this.initQuantityInputs();
      this.setupAddItem();
      this.setupRemoveItem();
      this.setupFormSubmit();
      this.setupSettlementReferenceLookup();
      this.applyTabScope();
      this.updateTotal();

      window.dispatchEvent(
        new CustomEvent("invoice-transaction-tab-changed", {
          detail: { tab: this.activeTab, id: this.id },
        }),
      );
    },
    setTab(tab) {
      this.activeTab = tab;
      this.applyTabScope();
      this.updateTotal();
      window.dispatchEvent(
        new CustomEvent("invoice-transaction-tab-changed", {
          detail: { tab: this.activeTab, id: this.id },
        }),
      );
    },
    tabClass(tab) {
      const activeClasses =
        this.variant === "public"
          ? "px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 text-white shadow"
          : "px-4 py-2 text-sm font-semibold rounded-lg bg-blue-600 text-white shadow";
      const inactiveClasses =
        "px-4 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200";

      return this.activeTab === tab ? activeClasses : inactiveClasses;
    },
    applyTabScope() {
      const scope = this.activeTab;
      const isSettlement = scope === "settlement";
      const isPassThrough = scope === "pass_through";
      const isDownPayment = scope === "down_payment";

      // Use $nextTick to ensure Alpine.js has finished rendering
      this.$nextTick(() => {
        this.$el
          .querySelectorAll("[data-transaction-scope]")
          .forEach((element) => {
            const targetScope = element.dataset.transactionScope;
            if (targetScope === "settlement") {
              const enable = isSettlement;
              element.disabled = !enable;
              if (element.dataset.settlementRequired === "true") {
                element.required = enable;
              }
              if (!enable && element.type === "radio") {
                element.checked = false;
              }
            } else if (targetScope === "down-payment") {
              // Enable the down payment field when down_payment tab is active
              element.disabled = !isDownPayment;
              if (element.dataset.downPaymentRequired === "true") {
                element.required = isDownPayment;
              }
            } else if (targetScope === "line-item") {
              const disable = isSettlement || isPassThrough;
              element.disabled = disable;
            }
          });
      });

      // Move remaining tab-dependent operations inside $nextTick as well
      this.$nextTick(() => {
        const itemsWrapper = this.$el.querySelector("[data-items-wrapper]");
        if (itemsWrapper) {
          itemsWrapper.style.display =
            isSettlement || isPassThrough ? "none" : "";
        }

        const settlementSection = this.$el.querySelector(
          '[data-tab-visible="settlement"]',
        );
        if (settlementSection) {
          settlementSection.style.display = isSettlement ? "" : "none";
        }

        if (this.addItemButton) {
          const disabled = isSettlement || isPassThrough;
          this.addItemButton.disabled = disabled;
          this.addItemButton.classList.toggle("opacity-50", disabled);
          this.addItemButton.classList.toggle("cursor-not-allowed", disabled);
        }

        const totalWrapper = this.$el.querySelector("[data-total-wrapper]");
        if (totalWrapper) {
          totalWrapper.style.display =
            isSettlement || isPassThrough ? "none" : "";
        }

        this.handleSettlementScopeChange();
      });
    },
    initPriceInputs() {
      this.$el
        .querySelectorAll('[data-role="price-input"]')
        .forEach((input) => {
          this.formatPrice(input);
          input.addEventListener("input", () => {
            this.formatPrice(input);
            this.updateTotal();
          });
          input.addEventListener("blur", () => this.formatPrice(input));
        });
    },
    initQuantityInputs() {
      this.$el
        .querySelectorAll('[data-role="quantity-input"]')
        .forEach((input) => {
          input.addEventListener("input", () => this.updateTotal());
        });
    },
    setupAddItem() {
      if (!this.addItemButton || !this.itemsContainer) {
        return;
      }

      const template = this.$el.querySelector("template[data-item-template]");
      this.addItemButton.addEventListener("click", () => {
        if (!template) {
          return;
        }

        const html = template.innerHTML.replace(
          /__INDEX__/g,
          String(this.itemIndex),
        );
        const fragment = document.createElement("template");
        fragment.innerHTML = html.trim();
        const element = fragment.content.firstElementChild;

        if (!element) {
          return;
        }

        const categorySelect = element.querySelector(
          'select[name^="items"][name$="[category_id]"]',
        );
        if (categorySelect) {
          categorySelect.innerHTML = [
            '<option value="">Pilih kategori pemasukan</option>',
          ]
            .concat(
              this.categoryOptions.map(
                (option) =>
                  `<option value="${option.id}">${option.name}</option>`,
              ),
            )
            .join("");
        }

        this.itemsContainer.appendChild(element);

        element
          .querySelectorAll('[data-role="price-input"]')
          .forEach((input) => {
            this.formatPrice(input);
            input.addEventListener("input", () => {
              this.formatPrice(input);
              this.updateTotal();
            });
            input.addEventListener("blur", () => this.formatPrice(input));
          });

        element
          .querySelectorAll('[data-role="quantity-input"]')
          .forEach((input) => {
            input.addEventListener("input", () => this.updateTotal());
          });

        this.itemIndex += 1;
        this.updateTotal();
      });
    },
    setupRemoveItem() {
      if (!this.itemsContainer) {
        return;
      }

      this.itemsContainer.addEventListener("click", (event) => {
        const button = event.target.closest("[data-remove-item]");
        if (!button) {
          return;
        }

        const item = button.closest("[data-invoice-item]");
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

      this.form.addEventListener("submit", () => {
        this.$el
          .querySelectorAll('[data-role="price-input"]')
          .forEach((input) => {
            if (input.disabled) {
              return;
            }

            input.value = input.dataset.rawValue || "";
          });
      });
    },
    setupSettlementReferenceLookup() {
      if (!this.settlementInvoiceInput || !this.referenceUrlTemplate) {
        return;
      }

      this.settlementInvoiceInput.addEventListener("input", () => {
        this.scheduleSettlementReferenceFetch();
      });

      this.settlementInvoiceInput.addEventListener("blur", () => {
        this.scheduleSettlementReferenceFetch(true);
      });
    },
    handleSettlementScopeChange() {
      if (!this.settlementInvoiceInput) {
        return;
      }

      if (this.activeTab === "settlement") {
        this.scheduleSettlementReferenceFetch(true);
      } else {
        this.clearSettlementFeedback();
      }
    },
    scheduleSettlementReferenceFetch(immediate = false) {
      if (!this.settlementInvoiceInput || !this.referenceUrlTemplate) {
        return;
      }

      if (this.activeTab !== "settlement") {
        this.clearSettlementFeedback();
        return;
      }

      const number = (this.settlementInvoiceInput.value || "").trim();

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

      const url = this.referenceUrlTemplate.replace(
        "__NUMBER__",
        encodeURIComponent(number),
      );

      if (this.settlementAbortController) {
        this.settlementAbortController.abort();
      }

      this.settlementAbortController = new AbortController();

      fetch(url, {
        method: "GET",
        headers: {
          Accept: "application/json",
        },
        signal: this.settlementAbortController.signal,
      })
        .then(async (response) => {
          if (!response.ok) {
            let message = "Gagal mengambil data invoice referensi.";

            try {
              const payload = await response.json();
              if (payload && typeof payload.message === "string") {
                message = payload.message;
              }
            } catch (error) {}

            this.showSettlementError(message);
            return;
          }

          const data = await response.json();
          this.showSettlementSummary(data);
        })
        .catch((error) => {
          if (error.name === "AbortError") {
            return;
          }

          this.showSettlementError(
            "Tidak dapat memuat data invoice referensi.",
          );
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
        this.settlementSummaryFields.client.textContent =
          data?.client_name || "-";
      }

      if (this.settlementSummaryFields.status) {
        const status = (data?.status || "-").toString().replace(/_/g, " ");
        this.settlementSummaryFields.status.textContent = status
          .split(" ")
          .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
          .join(" ");
      }

      if (this.settlementSummaryFields.whatsapp) {
        this.settlementSummaryFields.whatsapp.textContent =
          data?.client_whatsapp || "-";
      }

      if (this.settlementSummaryFields.total) {
        this.settlementSummaryFields.total.textContent = currencyFormatter(
          data?.total,
        );
      }

      if (this.settlementSummaryFields.downPayment) {
        this.settlementSummaryFields.downPayment.textContent =
          currencyFormatter(data?.down_payment);
      }

      if (this.settlementSummaryFields.remaining) {
        this.settlementSummaryFields.remaining.textContent = currencyFormatter(
          data?.remaining_balance,
        );
      }

      if (this.settlementSummaryFields.dueDate) {
        const dueDate = data?.due_date
          ? new Date(`${data.due_date}T00:00:00`)
          : null;
        this.settlementSummaryFields.dueDate.textContent =
          dueDate && !Number.isNaN(dueDate.getTime())
            ? dueDate.toLocaleDateString("id-ID", {
                year: "numeric",
                month: "long",
                day: "numeric",
              })
            : "-";
      }

      if (this.settlementSummaryFields.address) {
        this.settlementSummaryFields.address.textContent =
          data?.client_address || "-";
      }

      this.settlementSummary.classList.remove("hidden");

      this.setPriceInputValue(
        this.settlementRemainingInput,
        data?.remaining_balance,
      );
      this.setPriceInputValue(
        this.settlementPaidInput,
        data?.remaining_balance,
      );
    },
    showSettlementError(message) {
      if (this.settlementSummary) {
        this.settlementSummary.classList.add("hidden");
      }

      if (this.settlementError) {
        this.settlementError.textContent = message;
        this.settlementError.classList.remove("hidden");
      }
    },
    clearSettlementFeedback() {
      if (this.settlementError) {
        this.settlementError.textContent = "";
        this.settlementError.classList.add("hidden");
      }

      if (this.settlementSummary) {
        this.settlementSummary.classList.add("hidden");
      }
    },
    setPriceInputValue(input, value) {
      if (!input) {
        return;
      }

      const numeric = Number(value);
      const sanitized = Number.isFinite(numeric)
        ? Math.max(Math.round(numeric), 0)
        : 0;
      const rawString = String(sanitized);

      input.dataset.rawValue = rawString;
      input.value = rawString
        ? rawString.replace(/\B(?=(\d{3})+(?!\d))/g, ".")
        : "";
    },
    formatPrice(input) {
      const raw = (input.value || "").replace(/\D/g, "");
      input.dataset.rawValue = raw;
      input.value = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, ".") : "";
    },
    formatCurrency(value) {
      const numeric = Number(value);
      const resolved = Number.isFinite(numeric) ? numeric : 0;

      return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
      }).format(resolved);
    },
    updateTotal() {
      if (!this.itemsContainer || !this.totalElement) {
        return;
      }

      let total = 0;
      this.itemsContainer
        .querySelectorAll("[data-invoice-item]")
        .forEach((item) => {
          const quantityInput = item.querySelector(
            '[data-role="quantity-input"]',
          );
          const priceInput = item.querySelector('[data-role="price-input"]');
          const quantity = parseInt(quantityInput?.value || "0", 10) || 0;
          const price = parseInt(priceInput?.dataset.rawValue || "0", 10) || 0;
          total += quantity * price;
        });

      this.totalElement.textContent = this.formatCurrency(total || 0);
    },
  };
};

Alpine.start();

document.addEventListener("DOMContentLoaded", function () {
  const togglePassword = document.getElementById("togglePassword");
  const password = document.getElementById("password");

  if (togglePassword) {
    togglePassword.addEventListener("click", function (e) {
      const type =
        password.getAttribute("type") === "password" ? "text" : "password";
      password.setAttribute("type", type);

      this.querySelectorAll(".eye-open").forEach((icon) =>
        icon.classList.toggle("hidden"),
      );
      this.querySelectorAll(".eye-closed").forEach((icon) =>
        icon.classList.toggle("hidden"),
      );
    });
  }
});
