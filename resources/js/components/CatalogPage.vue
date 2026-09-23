<template>
    <div class="catalog-page bg-[#F2F7FA]">
        <nav aria-label="Breadcrumb" class="catalog-page__crumbs">
            <div class="mx-auto max-w-site px-4 sm:px-6 lg:px-8">
                <ol class="flex flex-wrap items-center gap-1.5 text-[0.8125rem] text-slate-500">
                    <li>
                        <a
                            :href="config.routes?.home || '/'"
                            class="transition-colors hover:text-[#0E3A45]"
                            aria-label="Головна"
                            @click="onCrumbClick($event, config.routes?.home || '/')">
                            <AppIcon name="home" :size="15" />
                        </a>
                    </li>
                    <template v-for="(crumb, index) in breadcrumbs" :key="`${crumb.label}-${index}`">
                        <li><AppIcon name="chevron-right" :size="13" class="text-gray-300" /></li>
                        <li>
                            <a
                                v-if="crumb.url && index < breadcrumbs.length - 1"
                                :href="crumb.url"
                                class="transition-colors hover:text-[#0E3A45]"
                                @click="onCrumbClick($event, crumb.url)">
                                {{ crumb.label }}
                            </a>
                            <span
                                v-else
                                class="max-w-xs truncate font-semibold text-[#1A2B36]"
                                aria-current="page">
                                {{ crumb.label }}
                            </span>
                        </li>
                    </template>
                </ol>
            </div>
        </nav>

        <section class="mx-auto max-w-site px-4 py-5 sm:px-6 md:py-8 lg:px-8">
            <div class="grid items-start gap-4 lg:grid-cols-[270px_1fr] lg:gap-5">
                <aside class="catalog-filters-drawer fixed left-0 z-[72] w-[min(300px,88vw)] -translate-x-full transition-transform duration-300 ease-out lg:sticky lg:top-[calc(var(--header-height)+1rem)] lg:z-auto lg:w-auto lg:self-start lg:translate-x-0 lg:transition-none"
                       :class="{ 'translate-x-0': filtersOpen }">
                    <div class="catalog-filters-drawer__inner h-full lg:max-h-[calc(100vh-var(--header-height)-2rem)] lg:overflow-hidden">
                        <CatalogFilters
                            v-model="filters"
                            :category-tree="categoryTree"
                            :selected-category-url="currentCategoryUrl"
                            @navigate-category="navigateToCategory"
                            @reset="resetFilters"
                            @close="closeFilters" />
                    </div>
                </aside>

                <div v-if="filtersOpen"
                     class="catalog-filters-backdrop fixed inset-x-0 bottom-0 z-[60] bg-[#0E3A45]/45 lg:hidden"
                     :style="{ top: 'var(--header-height, 108px)' }"
                     @click="closeFilters"></div>

                <div id="catalog-products-anchor" class="min-w-0">
                    <div v-if="activeFilterTags.length" class="mb-3 flex flex-wrap items-center gap-2">
                        <button
                            v-for="tag in activeFilterTags"
                            :key="tag.key"
                            type="button"
                            class="catalog-active-tag"
                            :title="`Прибрати: ${tag.label}`"
                            @click="removeFilter(tag.key)">
                            {{ tag.label }}
                            <AppIcon name="x" :size="12" />
                        </button>
                        <button
                            type="button"
                            class="text-[0.75rem] font-semibold text-[#FF6B4A] transition-colors hover:text-[#E85A3C]"
                            @click="resetFilters">
                            Скинути
                        </button>
                    </div>

                    <div class="catalog-toolbar mb-4">
                        <div class="min-w-0">
                            <h1 v-if="config.hero?.title" class="font-heading text-xl font-bold text-[#0E3A45] sm:text-2xl">
                                {{ config.hero.title }}
                            </h1>
                            <p class="mt-0.5 text-sm text-slate-500">
                                {{ totalCount }} {{ totalCount === 1 ? 'товар' : 'товарів' }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <button type="button"
                                    class="catalog-toolbar__filters-btn lg:hidden"
                                    @click="openFilters">
                                <AppIcon name="sliders-horizontal" :size="16" />
                                Фільтри
                                <span v-if="activeFiltersCount" class="catalog-toolbar__badge">{{ activeFiltersCount }}</span>
                            </button>
                            <label class="catalog-toolbar__sort">
                                <span class="sr-only">Сортування</span>
                                <select id="catalogSort" v-model="sort">
                                    <option value="default">За замовчуванням</option>
                                    <option value="price_asc">Ціна ↑</option>
                                    <option value="price_desc">Ціна ↓</option>
                                    <option value="name_asc">А–Я</option>
                                    <option value="name_desc">Я–А</option>
                                    <option v-if="config.showNewest" value="newest">Новинки</option>
                                </select>
                            </label>
                        </div>
                    </div>

                    <div v-if="loading" class="mb-4 flex items-center justify-center gap-3 rounded-2xl bg-white/80 py-5 text-sm text-slate-500 shadow-sm">
                        <div class="h-5 w-5 animate-spin rounded-full border-2 border-gray-200 border-t-[#FF6B4A]"></div>
                        Завантаження...
                    </div>

                    <div :class="{ 'opacity-50 pointer-events-none': loading }">
                        <ProductList :products="products" :show-new-badge="false" />
                    </div>

                    <nav v-if="pagination.last_page > 1" class="mt-8" aria-label="Сторінки">
                        <div class="flex flex-wrap items-center justify-center gap-1.5">
                            <button v-if="pagination.current_page > 1"
                                    type="button"
                                    class="catalog-page-btn"
                                    @click="goToPage(pagination.current_page - 1)">
                                <AppIcon name="chevron-left" :size="16" />
                            </button>
                            <span v-else class="catalog-page-btn catalog-page-btn--disabled">
                                <AppIcon name="chevron-left" :size="16" />
                            </span>

                            <template v-for="page in visiblePages" :key="page.key">
                                <span v-if="page.type === 'dots'" class="px-1 text-sm text-gray-400">…</span>
                                <span v-else-if="page.type === 'current'" class="catalog-page-btn catalog-page-btn--current">
                                    {{ page.num }}
                                </span>
                                <button v-else
                                        type="button"
                                        class="catalog-page-btn"
                                        @click="goToPage(page.num)">
                                    {{ page.num }}
                                </button>
                            </template>

                            <button v-if="pagination.current_page < pagination.last_page"
                                    type="button"
                                    class="catalog-page-btn"
                                    @click="goToPage(pagination.current_page + 1)">
                                <AppIcon name="chevron-right" :size="16" />
                            </button>
                            <span v-else class="catalog-page-btn catalog-page-btn--disabled">
                                <AppIcon name="chevron-right" :size="16" />
                            </span>
                        </div>
                        <p v-if="pagination.total > 0" class="mt-3 text-center text-[0.8125rem] text-slate-500">
                            {{ pagination.from }}–{{ pagination.to }} з {{ pagination.total }}
                        </p>
                    </nav>
                </div>
            </div>
        </section>

        <section v-if="config.popularProducts?.length" class="border-t border-[#D5E2E8]/80 bg-white">
            <div class="mx-auto max-w-site px-4 py-12 sm:px-6 md:py-14 lg:px-8">
                <div class="mb-7 flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <p class="mb-1 font-heading text-[0.6875rem] font-semibold uppercase tracking-[0.18em] text-[#FF6B4A]">Хіти</p>
                        <h2 class="font-heading text-2xl font-bold text-[#0E3A45]">Популярні іграшки</h2>
                    </div>
                    <a :href="config.routes?.catalog || '/catalog'"
                       class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#0E3A45] transition-colors hover:text-[#FF6B4A]"
                       @click="onCrumbClick($event, config.routes?.catalog || '/catalog')">
                        Усі товари <AppIcon name="arrow-right" :size="16" />
                    </a>
                </div>
                <ProductList :products="config.popularProducts" :show-new-badge="true" />
            </div>
        </section>

        <section v-if="config.showCta" class="bg-[#0E3A45] text-white">
            <div class="mx-auto max-w-site px-4 py-12 text-center sm:px-6 md:py-14 lg:px-8">
                <h2 class="mb-3 font-heading text-2xl font-bold md:text-3xl">Не знайшли потрібну іграшку?</h2>
                <p class="mx-auto mb-7 max-w-lg text-white/75">Напишіть нам — підберемо варіант для подарунка чи оптової партії</p>
                <div class="flex flex-wrap justify-center gap-3">
                    <a href="tel:0636310041"
                       class="inline-flex items-center gap-2 rounded-full bg-[#FF6B4A] px-6 py-3.5 font-heading font-semibold text-white transition-colors hover:bg-[#E85A3C]">
                        <AppIcon name="phone" :size="18" /> Зателефонувати
                    </a>
                    <a href="mailto:office@mtoys.com.ua"
                       class="inline-flex items-center gap-2 rounded-full border-2 border-white/35 px-6 py-3.5 font-heading font-semibold text-white transition-colors hover:border-white hover:bg-white/10">
                        <AppIcon name="mail" :size="18" /> Написати
                    </a>
                </div>
            </div>
        </section>
    </div>
</template>

<script>
import AppIcon from './AppIcon.vue';
import ProductList from './ProductList.vue';
import CatalogFilters from './CatalogFilters.vue';
import { isScrollRestoring } from '../spa/scrollMemory.js';
import { spaState } from '../spa/spaStore.js';

export default {
    name: 'CatalogPage',
    components: { AppIcon, ProductList, CatalogFilters },
    props: {
        config: { type: Object, default: () => ({}) },
    },
    data() {
        return {
            filtersOpen: false,
            loading: false,
            sort: 'default',
            products: [],
            pagination: {},
            breadcrumbs: [],
            filters: {
                priceMin: '',
                priceMax: '',
                availability: '',
                discount: '',
                wholesale: '',
                new: '',
            },
            syncingQuery: false,
            suppressLiveApply: true,
            applyTimer: null,
        };
    },
    computed: {
        spaMode() {
            return Boolean(this.$router);
        },
        totalCount() {
            return this.pagination.total ?? this.products.length ?? 0;
        },
        categoryTree() {
            if (Array.isArray(this.config.categoryTree) && this.config.categoryTree.length) {
                return this.config.categoryTree;
            }
            return (this.config.categories || []).map((c) => ({ ...c, children: c.children || [] }));
        },
        currentCategoryUrl() {
            if (this.spaMode && this.$route.name === 'category') {
                return this.$route.params.category || '';
            }
            return this.config.currentCategory?.url || '';
        },
        currentCategoryName() {
            if (!this.currentCategoryUrl) return '';
            return this.findCategoryInTree(this.categoryTree, this.currentCategoryUrl)?.name
                || this.config.currentCategory?.name
                || this.currentCategoryUrl;
        },
        visiblePages() {
            const current = this.pagination.current_page || 1;
            const last = this.pagination.last_page || 1;
            const start = Math.max(1, current - 2);
            const end = Math.min(last, current + 2);
            const pages = [];

            if (start > 1) {
                pages.push({ type: 'page', num: 1, key: 'p1' });
                if (start > 2) pages.push({ type: 'dots', key: 'dots-start' });
            }

            for (let i = start; i <= end; i++) {
                pages.push({
                    type: i === current ? 'current' : 'page',
                    num: i,
                    key: `p${i}`,
                });
            }

            if (end < last) {
                if (end < last - 1) pages.push({ type: 'dots', key: 'dots-end' });
                pages.push({ type: 'page', num: last, key: `p${last}` });
            }

            return pages;
        },
        activeFiltersCount() {
            let count = 0;
            if (this.filters.priceMin) count++;
            if (this.filters.priceMax) count++;
            if (this.currentCategoryUrl) count++;
            if (this.filters.availability === '1') count++;
            if (this.filters.discount === '1') count++;
            if (this.filters.wholesale === '1') count++;
            if (this.filters.new === '1') count++;
            return count;
        },
        activeFilterTags() {
            const tags = [];
            if (this.filters.priceMin || this.filters.priceMax) {
                const min = this.filters.priceMin || '0';
                const max = this.filters.priceMax || '∞';
                tags.push({ key: 'price', label: `${min}–${max} ₴` });
            }
            if (this.currentCategoryUrl) {
                tags.push({ key: 'category', label: this.currentCategoryName });
            }
            if (this.filters.availability === '1') {
                tags.push({ key: 'availability', label: 'В наявності' });
            }
            if (this.filters.discount === '1') {
                tags.push({ key: 'discount', label: 'Зі знижкою' });
            }
            if (this.filters.wholesale === '1') {
                tags.push({ key: 'wholesale', label: 'Опт' });
            }
            if (this.filters.new === '1') {
                tags.push({ key: 'new', label: 'Новинки' });
            }
            return tags;
        },
    },
    watch: {
        // Shallow only: deep watch re-hydrated stale spaState products after
        // filter/pagination fetch mutated nested config fields (hero, etc.).
        config: {
            handler(next) {
                this.hydrateFromConfig(next);
            },
        },
        filters: {
            deep: true,
            handler() {
                this.scheduleLiveApply();
            },
        },
        sort() {
            this.scheduleLiveApply();
        },
        '$route.query': {
            deep: true,
            handler() {
                if (!this.spaMode || this.syncingQuery) return;
                this.suppressLiveApply = true;
                this.readUrlParams();
                const page = parseInt(this.$route.query.page || '1', 10);
                this.fetchProducts(page, false).finally(() => {
                    this.$nextTick(() => { this.suppressLiveApply = false; });
                });
            },
        },
    },
    mounted() {
        this.hydrateFromConfig(this.config);
        this.readUrlParams();
        this.$nextTick(() => { this.suppressLiveApply = false; });
        if (!this.spaMode) {
            window.addEventListener('popstate', this.handlePopState);
        }
    },
    unmounted() {
        if (this.applyTimer) clearTimeout(this.applyTimer);
        if (!this.spaMode) {
            window.removeEventListener('popstate', this.handlePopState);
        }
    },
    methods: {
        hydrateFromConfig(config = {}) {
            if (Array.isArray(config.products)) {
                this.products = [...config.products];
            }
            if (config.pagination && typeof config.pagination === 'object') {
                this.pagination = { ...config.pagination };
            }
            if (Array.isArray(config.breadcrumbs)) {
                this.breadcrumbs = [...config.breadcrumbs];
            }
        },
        scheduleLiveApply() {
            if (this.suppressLiveApply) return;
            if (this.applyTimer) clearTimeout(this.applyTimer);
            this.applyTimer = setTimeout(() => {
                this.fetchProducts(1, true, false);
            }, 280);
        },
        readUrlParams() {
            const source = this.spaMode
                ? this.$route.query
                : Object.fromEntries(new URLSearchParams(window.location.search).entries());

            this.filters.priceMin = source.price_min || '';
            this.filters.priceMax = source.price_max || '';
            // Only "in stock" is a real filter; legacy ?availability=out is ignored.
            this.filters.availability = source.availability === '1' ? '1' : '';
            this.filters.discount = source.discount || '';
            this.filters.wholesale = source.wholesale || '';
            this.filters.new = source.new || '';
            this.sort = source.sort || 'default';
        },
        buildParams(page = 1) {
            const params = new URLSearchParams();
            if (this.filters.priceMin) params.set('price_min', this.filters.priceMin);
            if (this.filters.priceMax) params.set('price_max', this.filters.priceMax);
            if (this.filters.availability) params.set('availability', this.filters.availability);
            if (this.filters.discount) params.set('discount', this.filters.discount);
            if (this.filters.wholesale) params.set('wholesale', this.filters.wholesale);
            if (this.filters.new) params.set('new', this.filters.new);
            if (this.sort && this.sort !== 'default') params.set('sort', this.sort);
            if (page > 1) params.set('page', String(page));
            return params;
        },
        buildApiUrl(page = 1) {
            const apiBase = this.config.spaApiUrl || '/api/spa/catalog';
            const params = this.buildParams(page);
            const query = params.toString();
            return query ? `${apiBase}?${query}` : apiBase;
        },
        buildBrowserUrl(page = 1) {
            const base = this.config.filterBaseUrl || window.location.pathname;
            const params = this.buildParams(page);
            const query = params.toString();
            return query ? `${base}?${query}` : base;
        },
        buildBrowserQuery(page = 1) {
            const params = this.buildParams(page);
            const query = {};
            params.forEach((value, key) => {
                query[key] = value;
            });
            return query;
        },
        handlePopState() {
            this.suppressLiveApply = true;
            this.readUrlParams();
            const page = parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10);
            this.fetchProducts(page, false).finally(() => {
                this.$nextTick(() => { this.suppressLiveApply = false; });
            });
        },
        goToPage(page) {
            if (page < 1 || page > (this.pagination.last_page || 1) || page === this.pagination.current_page) {
                return;
            }
            this.fetchProducts(page, true, true);
        },
        fetchProducts(page = 1, pushState = true, scrollToList = false) {
            this.loading = true;
            spaState.pageBusy = true;
            const fetchUrl = this.buildApiUrl(page);

            return fetch(fetchUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            })
                .then((response) => {
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then((data) => {
                    if (!data.success) throw new Error(data.message || 'Filter error');

                    const payload = data.data && typeof data.data === 'object' ? data.data : data;

                    this.products = Array.isArray(payload.products) ? payload.products : [];
                    this.pagination = payload.pagination && typeof payload.pagination === 'object'
                        ? { ...payload.pagination }
                        : {};

                    if (Array.isArray(payload.breadcrumbs)) {
                        this.breadcrumbs = [...payload.breadcrumbs];
                    }

                    if (pushState) {
                        if (this.spaMode) {
                            this.syncingQuery = true;
                            this.$router.replace({
                                name: this.$route.name,
                                params: { ...this.$route.params },
                                query: this.buildBrowserQuery(page),
                            }).finally(() => {
                                this.syncingQuery = false;
                            });
                        } else {
                            window.history.replaceState({ catalogPage: page }, '', this.buildBrowserUrl(page));
                        }
                    }

                    if (scrollToList) {
                        this.$nextTick(() => {
                            if (isScrollRestoring) return;
                            document.getElementById('catalog-products-anchor')?.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start',
                            });
                        });
                    }
                })
                .catch(() => {
                    window.$toast?.error?.('Не вдалося завантажити товари. Спробуйте ще раз.');
                })
                .finally(() => {
                    this.loading = false;
                    spaState.pageBusy = false;
                });
        },
        navigateToCategory(url) {
            const query = this.buildBrowserQuery(1);
            if (!this.spaMode) {
                const href = url
                    ? `/catalog/categoriya/${encodeURIComponent(url)}${Object.keys(query).length ? `?${new URLSearchParams(query)}` : ''}`
                    : `/catalog${Object.keys(query).length ? `?${new URLSearchParams(query)}` : ''}`;
                window.location.href = href;
                return;
            }

            if (!url) {
                this.$router.push({ name: 'catalog', query });
                return;
            }

            this.$router.push({
                name: 'category',
                params: { category: url },
                query,
            });
        },
        onCrumbClick(event, url) {
            if (!this.spaMode || !url) return;
            try {
                const parsed = new URL(url, window.location.origin);
                if (parsed.origin !== window.location.origin) return;
                event.preventDefault();
                this.$router.push(parsed.pathname + parsed.search);
            } catch {
                // keep default navigation
            }
        },
        closeFilters() {
            this.filtersOpen = false;
            document.body.style.overflow = '';
        },
        openFilters() {
            this.filtersOpen = true;
            document.body.style.overflow = 'hidden';
        },
        resetFilters() {
            this.suppressLiveApply = true;
            this.filters = {
                priceMin: '',
                priceMax: '',
                availability: '',
                discount: '',
                wholesale: '',
                new: '',
            };
            this.sort = 'default';
            this.$nextTick(() => {
                this.suppressLiveApply = false;
                if (this.currentCategoryUrl) {
                    this.navigateToCategory('');
                } else {
                    this.fetchProducts(1, true, false);
                }
            });
        },
        removeFilter(key) {
            if (key === 'category') {
                this.navigateToCategory('');
                return;
            }
            this.suppressLiveApply = true;
            if (key === 'price') {
                this.filters.priceMin = '';
                this.filters.priceMax = '';
            } else {
                this.filters[key] = '';
            }
            this.$nextTick(() => {
                this.suppressLiveApply = false;
                this.fetchProducts(1, true, false);
            });
        },
        findCategoryInTree(nodes, url) {
            for (const node of nodes || []) {
                if (node.url === url) return node;
                const found = this.findCategoryInTree(node.children || [], url);
                if (found) return found;
            }
            return null;
        },
    },
};
</script>
