<template>
    <div class="catalog-filters flex h-full flex-col bg-white">
        <div class="flex shrink-0 items-center justify-between bg-[#FF6B4A] px-4 py-3.5 text-white lg:px-5 lg:py-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15">
                    <AppIcon name="sliders-horizontal" :size="16" />
                </span>
                <div>
                    <h2 class="font-heading text-sm font-bold leading-tight lg:text-base">Фільтри</h2>
                    <p v-if="activeCount > 0" class="text-[0.6875rem] text-white/80">
                        {{ activeCount }} {{ activeCount === 1 ? 'активний' : 'активних' }}
                    </p>
                </div>
            </div>
            <button
                type="button"
                class="flex h-8 w-8 items-center justify-center rounded-full border border-white/25 transition-colors hover:bg-white/10 lg:hidden"
                aria-label="Закрити фільтри"
                @click="$emit('close')">
                <AppIcon name="x" :size="18" />
            </button>
        </div>

        <div class="flex min-h-0 flex-1 flex-col">
            <div class="flex-1 space-y-4 overflow-y-auto px-4 py-4 lg:space-y-5 lg:px-5 lg:py-5">
                <section v-if="categoryTree.length" class="catalog-filters__block">
                    <p class="catalog-filters__label">Категорія</p>
                    <button
                        type="button"
                        class="catalog-filters__select"
                        @click="pickerOpen = true">
                        <span class="catalog-filters__select-text">
                            <span class="catalog-filters__select-label">{{ selectedCategoryLabel }}</span>
                            <span v-if="selectedCategoryPath" class="catalog-filters__select-path">{{ selectedCategoryPath }}</span>
                        </span>
                        <AppIcon name="chevron-down" :size="16" icon-class="shrink-0 text-slate-400" />
                    </button>
                    <button
                        v-if="selectedCategoryUrl"
                        type="button"
                        class="mt-1.5 text-left text-xs font-medium text-[#FF6B4A] hover:text-[#E85A3C]"
                        @click="$emit('navigate-category', '')">
                        Очистити категорію
                    </button>
                </section>

                <section class="catalog-filters__block">
                    <p class="catalog-filters__label">Ціна, грн</p>
                    <div class="mb-2.5 flex flex-wrap gap-1.5">
                        <button
                            v-for="preset in pricePresets"
                            :key="preset.label"
                            type="button"
                            class="catalog-filter-chip catalog-filter-chip--compact"
                            :class="{ 'catalog-filter-chip--active': isPricePresetActive(preset) }"
                            @click="applyPricePreset(preset)">
                            {{ preset.label }}
                        </button>
                    </div>
                    <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                        <input
                            :value="modelValue.priceMin"
                            type="number"
                            min="0"
                            step="1"
                            inputmode="numeric"
                            placeholder="Від"
                            class="catalog-filters__input"
                            @input="patch('priceMin', $event.target.value)" />
                        <span class="text-xs text-gray-300">—</span>
                        <input
                            :value="modelValue.priceMax"
                            type="number"
                            min="0"
                            step="1"
                            inputmode="numeric"
                            placeholder="До"
                            class="catalog-filters__input"
                            @input="patch('priceMax', $event.target.value)" />
                    </div>
                </section>

                <section class="catalog-filters__block">
                    <p class="catalog-filters__label">Наявність</p>
                    <div class="grid grid-cols-2 gap-1.5">
                        <button
                            v-for="opt in availabilityOptions"
                            :key="opt.value"
                            type="button"
                            class="catalog-filter-chip catalog-filter-chip--compact"
                            :class="{ 'catalog-filter-chip--active': currentAvailability === opt.value }"
                            @click="setAvailability(opt.value)">
                            {{ opt.label }}
                        </button>
                    </div>
                </section>

                <section class="catalog-filters__block">
                    <p class="catalog-filters__label">Особливості</p>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            v-for="chip in toggleChips"
                            :key="chip.key"
                            type="button"
                            class="catalog-filter-chip"
                            :class="{ 'catalog-filter-chip--active': modelValue[chip.key] === '1' }"
                            @click="toggle(chip.key)">
                            <AppIcon :name="chip.icon" :size="14" icon-class="shrink-0" />
                            <span>{{ chip.label }}</span>
                        </button>
                    </div>
                </section>
            </div>

            <div class="catalog-filters__footer shrink-0 border-t border-[#E8EEF2] bg-white px-4 py-3 lg:px-5 lg:py-4">
                <button
                    type="button"
                    class="flex w-full items-center justify-center gap-2 rounded-full border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-[#1A2B36] transition-colors hover:border-[#0E3A45] hover:text-[#0E3A45]"
                    @click="$emit('reset')">
                    Скинути фільтри
                </button>
                <button
                    type="button"
                    class="mt-2 flex w-full items-center justify-center gap-2 rounded-full bg-[#FF6B4A] px-4 py-2.5 font-heading text-sm font-semibold text-white transition-colors hover:bg-[#E85A3C] lg:hidden"
                    @click="$emit('close')">
                    Готово
                </button>
            </div>
        </div>

        <CategoryPickerModal
            :open="pickerOpen"
            :tree="categoryTree"
            :model-value="selectedCategoryUrl"
            @update:model-value="onCategoryPicked"
            @close="closePicker" />
    </div>
</template>

<script>
import AppIcon from './AppIcon.vue';
import CategoryPickerModal from './CategoryPickerModal.vue';

export default {
    name: 'CatalogFilters',
    components: { AppIcon, CategoryPickerModal },
    props: {
        modelValue: {
            type: Object,
            required: true,
        },
        categoryTree: {
            type: Array,
            default: () => [],
        },
        selectedCategoryUrl: {
            type: String,
            default: '',
        },
    },
    emits: ['update:modelValue', 'reset', 'close', 'navigate-category'],
    data() {
        return {
            pickerOpen: false,
            pricePresets: [
                { label: 'до 500', min: '', max: '500' },
                { label: '500–2 000', min: '500', max: '2000' },
                { label: '2 000–5 000', min: '2000', max: '5000' },
                { label: 'від 5 000', min: '5000', max: '' },
            ],
            availabilityOptions: [
                { value: '1', label: 'В наявності' },
                { value: 'all', label: 'Всі' },
            ],
            toggleChips: [
                { key: 'discount', label: 'Зі знижкою', icon: 'tag' },
                { key: 'wholesale', label: 'Опт', icon: 'boxes' },
                { key: 'new', label: 'Новинки', icon: 'star' },
            ],
        };
    },
    computed: {
        currentAvailability() {
            return this.modelValue.availability === '1' ? '1' : 'all';
        },
        selectedNode() {
            if (!this.selectedCategoryUrl) return null;
            return this.findNode(this.categoryTree, this.selectedCategoryUrl);
        },
        selectedCategoryLabel() {
            return this.selectedNode?.name || 'Оберіть категорію';
        },
        selectedCategoryPath() {
            if (!this.selectedCategoryUrl) return '';
            const path = this.findPath(this.categoryTree, this.selectedCategoryUrl);
            if (!path || path.length <= 1) return '';
            return path.slice(0, -1).map((n) => n.name).join(' / ');
        },
        activeCount() {
            let count = 0;
            if (this.modelValue.priceMin || this.modelValue.priceMax) count++;
            if (this.selectedCategoryUrl) count++;
            if (this.modelValue.availability === '1') count++;
            if (this.modelValue.discount === '1') count++;
            if (this.modelValue.wholesale === '1') count++;
            if (this.modelValue.new === '1') count++;
            return count;
        },
    },
    methods: {
        patch(key, value) {
            this.$emit('update:modelValue', { ...this.modelValue, [key]: value });
        },
        toggle(key) {
            const next = this.modelValue[key] === '1' ? '' : '1';
            this.$emit('update:modelValue', { ...this.modelValue, [key]: next });
        },
        setAvailability(value) {
            // Default listing is "all"; only "in stock" is an active filter param.
            const next = value === 'all' ? '' : value;
            this.$emit('update:modelValue', { ...this.modelValue, availability: next });
        },
        applyPricePreset(preset) {
            if (this.isPricePresetActive(preset)) {
                this.$emit('update:modelValue', { ...this.modelValue, priceMin: '', priceMax: '' });
                return;
            }
            this.$emit('update:modelValue', {
                ...this.modelValue,
                priceMin: preset.min,
                priceMax: preset.max,
            });
        },
        isPricePresetActive(preset) {
            return String(this.modelValue.priceMin || '') === String(preset.min || '')
                && String(this.modelValue.priceMax || '') === String(preset.max || '');
        },
        onCategoryPicked(url) {
            this.$emit('navigate-category', url || '');
        },
        closePicker() {
            this.pickerOpen = false;
            document.body.style.overflow = '';
        },
        findNode(nodes, url) {
            for (const node of nodes || []) {
                if (node.url === url) return node;
                const found = this.findNode(node.children || [], url);
                if (found) return found;
            }
            return null;
        },
        findPath(nodes, url, trail = []) {
            for (const node of nodes || []) {
                const next = [...trail, node];
                if (node.url === url) return next;
                const found = this.findPath(node.children || [], url, next);
                if (found) return found;
            }
            return null;
        },
    },
};
</script>
