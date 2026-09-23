<template>
    <div class="search-widget" :class="{ 'search-widget--header': isHeader, 'search-widget--open': headerOpen }">
        <form class="search-widget__form" @submit.prevent="performSearch">
            <AppIcon v-if="isHeader" name="search" :size="18" class="search-widget__form-icon" />
            <input
                ref="input"
                v-model="searchQuery"
                type="search"
                inputmode="search"
                autocomplete="off"
                placeholder="Пошук товарів..."
                class="search-widget__input"
                @input="onInput"
                @focus="onFocus"
            />
            <button v-if="!isHeader" type="submit" class="search-widget__submit" aria-label="Шукати">
                <AppIcon name="arrow-right" :size="16" />
            </button>
        </form>

        <Teleport v-if="isHeader && isMobileView" to="#searchResultsPanel">
            <Transition name="search-sheet">
                <SearchPanelBody
                    v-if="headerOpen && panelActive"
                    mode="sheet"
                    :loading="loading"
                    :query="searchQuery"
                    :suggestions="suggestions"
                    @submit="performSearch"
                    @pick="onResultClick" />
            </Transition>
        </Teleport>

        <Transition v-else-if="isHeader" name="search-dropdown">
            <SearchPanelBody
                v-if="headerOpen && panelActive"
                mode="dropdown"
                :loading="loading"
                :query="searchQuery"
                :suggestions="suggestions"
                @submit="performSearch"
                @pick="onResultClick" />
        </Transition>

        <div
            v-if="!isHeader && showSuggestions && suggestions.length > 0"
            class="search-widget__dropdown">
            <a
                v-for="item in suggestions"
                :key="item.id"
                :href="productUrl(item)"
                class="search-widget__dropdown-item">
                {{ item.name }}
            </a>
        </div>
    </div>
</template>

<script>
import AppIcon from './AppIcon.vue';
import SearchPanelBody from './SearchPanelBody.vue';

export default {
    name: 'Search',
    components: { AppIcon, SearchPanelBody },
    props: {
        variant: {
            type: String,
            default: 'inline',
        },
    },
    data() {
        return {
            searchQuery: '',
            suggestions: [],
            showSuggestions: false,
            searchTimeout: null,
            loading: false,
            headerOpen: false,
            isMobileView: typeof window !== 'undefined'
                ? window.matchMedia('(max-width: 1023px)').matches
                : false,
        };
    },
    computed: {
        isHeader() {
            return this.variant === 'header';
        },
        panelActive() {
            return this.loading || this.searchQuery.trim().length >= 2;
        },
    },
    mounted() {
        this.mobileMq = window.matchMedia('(max-width: 1023px)');
        this.onMobileChange = () => {
            this.isMobileView = this.mobileMq.matches;
        };
        this.mobileMq.addEventListener('change', this.onMobileChange);

        this.onHeaderOpen = () => {
            this.headerOpen = true;
            this.$nextTick(() => this.$refs.input?.focus());
        };
        this.onHeaderClose = () => {
            this.headerOpen = false;
            this.searchQuery = '';
            this.suggestions = [];
            this.loading = false;
            this.showSuggestions = false;
        };

        window.addEventListener('header-search-open', this.onHeaderOpen);
        window.addEventListener('header-search-close', this.onHeaderClose);
    },
    beforeUnmount() {
        this.mobileMq?.removeEventListener('change', this.onMobileChange);
        window.removeEventListener('header-search-open', this.onHeaderOpen);
        window.removeEventListener('header-search-close', this.onHeaderClose);
        if (this.searchTimeout) clearTimeout(this.searchTimeout);
    },
    methods: {
        onFocus() {
            this.showSuggestions = true;
        },
        onInput() {
            if (this.searchTimeout) clearTimeout(this.searchTimeout);

            if (this.searchQuery.trim().length >= 2) {
                this.loading = true;
                this.searchTimeout = setTimeout(() => this.fetchSuggestions(), 280);
            } else {
                this.suggestions = [];
                this.loading = false;
            }
        },
        async fetchSuggestions() {
            try {
                const response = await fetch(`/catalog/search?q=${encodeURIComponent(this.searchQuery.trim())}`);
                if (!response.ok) throw new Error('Search failed');
                const data = await response.json();
                this.suggestions = Array.isArray(data) ? data : [];
            } catch {
                this.suggestions = [];
            } finally {
                this.loading = false;
            }
        },
        performSearch() {
            const query = this.searchQuery.trim();
            if (!query) return;
            window.closeSearchModal?.();
            window.location.href = `/search?q=${encodeURIComponent(query)}`;
        },
        productUrl(item) {
            const category = item.category_url || 'catalog';
            return `/catalog/categoriya/${encodeURIComponent(category)}/${encodeURIComponent(item.url)}`;
        },
        onResultClick() {
            window.closeSearchModal?.();
        },
    },
};
</script>
