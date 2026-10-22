import Alpine from 'alpinejs';
import {
    Chart,
    DoughnutController,
    BarController,
    LineController,
    ArcElement,
    BarElement,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler,
    Tooltip,
    Legend,
} from 'chart.js';

Chart.register(
    DoughnutController,
    BarController,
    LineController,
    ArcElement,
    BarElement,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler,
    Tooltip,
    Legend,
);

export const THEME_STORAGE_KEY = 'bookflow.theme';

const numberFormatter = new Intl.NumberFormat('id-ID');
const compactFormatter = new Intl.NumberFormat('id-ID', {
    notation: 'compact',
    maximumFractionDigits: 1,
});

const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function resolveTheme(preference) {
    if (preference === 'dark' || preference === 'light') {
        return preference;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'dark'
        : 'light';
}

/**
 * Reads the resolved palette from CSS custom properties so Chart.js agrees
 * with the surrounding surfaces in both light and dark mode.
 */
export function chartTheme() {
    const styles = getComputedStyle(document.documentElement);
    const dark = document.documentElement.dataset.theme === 'dark';
    const read = (name, fallback) => {
        const value = styles.getPropertyValue(name).trim();

        return value === '' ? fallback : value;
    };

    return {
        dark,
        ink: read('--t-ink', dark ? '#e8edf7' : '#0f172a'),
        inkSoft: read('--t-ink-soft', dark ? '#a9b6cd' : '#475569'),
        muted: read('--t-ink-muted', dark ? '#7686a3' : '#94a3b8'),
        grid: dark ? 'rgba(148,163,184,0.16)' : 'rgba(100,116,139,0.14)',
        tooltipBg: dark ? '#162034' : '#ffffff',
        tooltipInk: dark ? '#e8edf7' : '#0f172a',
        tooltipBorder: dark ? '#22304c' : '#e2e8f0',
        brand: '#2560e8',
        success: '#10b981',
        warning: '#f59e0b',
        danger: '#ef4444',
        violet: '#8b5cf6',
        cyan: '#06b6d4',
    };
}

function formatValue(value, currency) {
    if (currency) {
        return 'Rp ' + numberFormatter.format(Math.round(Number(value)));
    }

    return numberFormatter.format(Number(value));
}

function formatCompact(value, currency) {
    return (currency ? 'Rp ' : '') + compactFormatter.format(Number(value));
}

/**
 * Single source of truth for the theme preference (light | dark | system).
 * The resolved value is written to <html data-theme>; the boot script in the
 * layout performs the same resolution before first paint.
 */
Alpine.data('themeSwitcher', () => ({
    open: false,
    preference: 'system',

    init() {
        let stored = null;

        try {
            stored = window.localStorage.getItem(THEME_STORAGE_KEY);
        } catch {
            stored = null;
        }

        this.preference = ['light', 'dark', 'system'].includes(stored)
            ? stored
            : 'system';

        this.apply();

        window
            .matchMedia('(prefers-color-scheme: dark)')
            .addEventListener('change', () => {
                if (this.preference === 'system') {
                    this.apply();
                }
            });
    },

    choose(preference) {
        this.preference = preference;

        try {
            window.localStorage.setItem(THEME_STORAGE_KEY, preference);
        } catch {
            /* storage unavailable — theme still applies for this session */
        }

        this.apply();
    },

    apply() {
        document.documentElement.dataset.theme = resolveTheme(this.preference);
        document.documentElement.dataset.themePreference = this.preference;

        window.dispatchEvent(new CustomEvent('bookflow:theme-changed'));
    },
}));

/**
 * Declarative Chart.js wrapper.
 *
 * Usage: <canvas x-data="chart({ type: 'bar', currency: true, data: {...} })" x-ref="canvas"></canvas>
 */
Alpine.data('chart', (config = {}) => ({
    instance: null,

    init() {
        this.render();

        this._onTheme = () => this.render();
        window.addEventListener('bookflow:theme-changed', this._onTheme);
    },

    destroy() {
        window.removeEventListener('bookflow:theme-changed', this._onTheme);

        if (this.instance) {
            this.instance.destroy();
            this.instance = null;
        }
    },

    render() {
        const canvas = this.$refs.canvas;

        if (!canvas) {
            return;
        }

        if (this.instance) {
            this.instance.destroy();
            this.instance = null;
        }

        const theme = chartTheme();
        const currency = config.currency === true;
        const horizontal = config.horizontal === true;
        const type = config.type ?? 'bar';
        const isPie = type === 'doughnut' || type === 'pie';

        const scales = isPie
            ? {}
            : {
                  x: {
                      grid: {
                          display: false,
                      },
                      ticks: {
                          color: theme.muted,
                          callback: (value) =>
                              horizontal
                                  ? formatCompact(value, currency)
                                  : value,
                      },
                  },
                  y: {
                      grid: {
                          color: theme.grid,
                      },
                      border: {
                          display: false,
                      },
                      ticks: {
                          color: theme.muted,
                          callback: (value) =>
                              horizontal
                                  ? value
                                  : formatCompact(value, currency),
                      },
                  },
              };

        this.instance = new Chart(canvas, {
            type,
            data: config.data ?? { labels: [], datasets: [] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: horizontal ? 'y' : 'x',
                cutout: config.cutout ?? undefined,
                animation: prefersReducedMotion()
                    ? false
                    : {
                          duration: 400,
                      },
                plugins: {
                    legend: {
                        display:
                            config.legend ??
                            (type === 'line' || type === 'pie'),
                        position: 'bottom',
                        labels: {
                            color: theme.inkSoft,
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                            padding: 14,
                        },
                    },
                    tooltip: {
                        backgroundColor: theme.tooltipBg,
                        titleColor: theme.tooltipInk,
                        bodyColor: theme.tooltipInk,
                        borderColor: theme.tooltipBorder,
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: !isPie,
                        callbacks: {
                            label: (context) => {
                                const parsed = context.parsed;
                                const raw =
                                    typeof parsed === 'number'
                                        ? parsed
                                        : (parsed.y ?? parsed.x ?? 0);
                                const prefix = context.dataset.label
                                    ? context.dataset.label + ': '
                                    : '';

                                return prefix + formatValue(raw, currency);
                            },
                        },
                    },
                },
                scales,
            },
        });
    },
}));

/**
 * Repeater helper for document line items (orders, goods receipts).
 *
 * Usage:
 *   <div x-data="lineItems({ products: [...], items: [...] })">…</div>
 */
Alpine.data('lineItems', (config = {}) => ({
    products: config.products ?? [],
    productQuery: '',
    zone: config.zone ?? '',
    discountPercent: config.discountPercent ?? '0',
    schools: {},
    blank: config.blank ?? {
        product_id: '',
        quantity: 1,
        unit_price: '',
        discount_percent: '0',
        discount_amount: '0',
        product_search: '',
        showDropdown: false,
    },
    items: [],

    init() {
        this.items =
            Array.isArray(config.items) && config.items.length > 0
                ? config.items.map((item) => {
                      const merged = {
                          ...this.blank,
                          ...item,
                          showDropdown: false,
                      };
                      if (merged.product_id && !merged.product_search) {
                          const product = this.products.find(
                              (candidate) =>
                                  String(candidate.value) ===
                                  String(merged.product_id),
                          );
                          merged.product_search = product?.label ?? '';
                      }
                      return merged;
                  })
                : [{ ...this.blank }];
    },

    add() {
        this.items.push({ ...this.blank });
    },

    remove(index) {
        if (this.items.length > 1) {
            this.items.splice(index, 1);
        }
    },

    pick(index) {
        const product = this.products.find(
            (candidate) =>
                String(candidate.value) ===
                String(this.items[index].product_id),
        );

        if (!product) {
            return;
        }

        const targetKey = config.priceKey ?? 'unit_price';
        const sourceKey = config.bookPriceKey ?? 'price';
        const current = this.items[index][targetKey];
        const zonaPrice = this.zonePriceFor(product);

        if (current === '' || current === null || current === undefined) {
            this.items[index][targetKey] =
                zonaPrice || product[sourceKey] || '';
        }
    },

    zonePriceFor(product) {
        if (!this.zone || !product?.zona_prices) {
            return null;
        }

        return product.zona_prices[String(this.zone)] ?? null;
    },

    applyZone() {
        this.items.forEach((item, index) => {
            const product = this.products.find(
                (candidate) =>
                    String(candidate.value) === String(item.product_id),
            );

            if (!product) {
                return;
            }

            const targetKey = config.priceKey ?? 'unit_price';
            const zonaPrice = this.zonePriceFor(product);

            if (
                zonaPrice !== null &&
                zonaPrice !== undefined &&
                zonaPrice !== ''
            ) {
                this.items[index][targetKey] = zonaPrice;
            }
        });
    },

    filteredProducts() {
        const q = this.productQuery.trim().toLowerCase();

        if (q === '') {
            return this.products;
        }

        return this.products.filter((product) =>
            String(product.label ?? '')
                .toLowerCase()
                .includes(q),
        );
    },

    subtotal() {
        return this.items.reduce((carry, item) => {
            const line = Number(item.unit_price || 0) * Number(item.quantity || 0);
            const pct = Number(item.discount_percent || 0);
            const disc = pct > 0 ? (line * pct) / 100 : Number(item.discount_amount || 0);
            return carry + line - disc;
        }, 0);
    },

    lineTotal(item) {
        const line = Number(item.unit_price || 0) * Number(item.quantity || 0);
        const pct = Number(item.discount_percent || 0);
        const disc = pct > 0 ? (line * pct) / 100 : Number(item.discount_amount || 0);
        return Math.max(0, line - disc);
    },

    orderDiscount() {
        return (this.subtotal() * Number(this.discountPercent || 0)) / 100;
    },

    grandTotal() {
        return Math.max(0, this.subtotal() - this.orderDiscount());
    },

    money(value) {
        return 'Rp ' + numberFormatter.format(Math.round(Number(value || 0)));
    },
}));

/**
 * Searchable multi-product picker (catalog form).
 *
 * Usage:
 *   <div x-data="productPicker({ products: [...], selected: [...] })">…</div>
 */
Alpine.data('productPicker', (config = {}) => ({
    products: config.products ?? [],
    selected: (Array.isArray(config.selected) ? config.selected : []).map(
        Number,
    ),
    query: '',
    category: '',
    showDropdown: false,

    get categories() {
        const names = this.products.map((product) => this.categoryOf(product));

        return [...new Set(names)]
            .filter(Boolean)
            .sort((a, b) => a.localeCompare(b, 'id'));
    },

    get available() {
        const q = this.query.trim().toLowerCase();

        return this.products
            .filter((product) => !this.selected.includes(Number(product.id)))
            .filter(
                (product) =>
                    this.category === '' ||
                    this.categoryOf(product) === this.category,
            )
            .filter(
                (product) =>
                    q === '' ||
                    String(product.code ?? '')
                        .toLowerCase()
                        .includes(q) ||
                    String(product.title ?? '')
                        .toLowerCase()
                        .includes(q),
            );
    },

    get selectedProducts() {
        return this.selected
            .map((id) =>
                this.products.find((product) => Number(product.id) === id),
            )
            .filter(Boolean);
    },

    categoryOf(product) {
        return product.book?.category?.name ?? '';
    },

    add(id) {
        const productId = Number(id);

        if (!this.selected.includes(productId)) {
            this.selected.push(productId);
        }

        this.query = '';
        this.$refs.picker?.focus();
    },

    remove(id) {
        this.selected = this.selected.filter(
            (productId) => productId !== Number(id),
        );
    },

    money(value) {
        return numberFormatter.format(Math.round(Number(value || 0)));
    },
}));

/**
 * Client-side preview for photo inputs.
 *
 * Usage:
 *   <div x-data="photoPreview">
 *       <input type="file" accept="image/*" @change="pick($event)" />
 *       <img x-show="preview" :src="preview" />
 *   </div>
 */
Alpine.data('photoPreview', () => ({
    preview: null,

    pick(event) {
        const file = event.target.files?.[0];

        if (!file) {
            this.preview = null;
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            this.preview = reader.result;
        };
        reader.readAsDataURL(file);
    },
}));

Alpine.data('permissionPicker', (selected = []) => ({
    selected: (Array.isArray(selected) ? selected : []).map(Number),

    allOn(ids) {
        return ids.every((id) => this.selected.includes(id));
    },

    toggleAll(ids) {
        if (this.allOn(ids)) {
            this.selected = this.selected.filter((id) => !ids.includes(id));

            return;
        }

        this.selected = Array.from(new Set([...this.selected, ...ids]));
    },

    clear() {
        this.selected = [];
    },

    groupCount(ids) {
        return ids.filter((id) => this.selected.includes(id)).length;
    },
}));

if (!window.Alpine) {
    window.Alpine = Alpine;
    Alpine.start();
}
