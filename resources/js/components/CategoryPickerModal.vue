<template>
    <Teleport to="body">
        <Transition name="cat-picker">
            <div
                v-if="open"
                class="cat-picker"
                role="dialog"
                aria-modal="true"
                aria-labelledby="cat-picker-title"
                @keydown.esc.prevent="$emit('close')">
                <div class="cat-picker__backdrop" @click="$emit('close')"></div>

                <div class="cat-picker__dialog">
                    <div class="cat-picker__head">
                        <div>
                            <h3 id="cat-picker-title" class="cat-picker__title">Оберіть категорію</h3>
                            <p class="cat-picker__subtitle">Категорії та підкатегорії</p>
                        </div>
                        <button type="button" class="cat-picker__close" aria-label="Закрити" @click="$emit('close')">
                            <AppIcon name="x" :size="18" />
                        </button>
                    </div>

                    <div class="cat-picker__search">
                        <AppIcon name="search" :size="16" icon-class="text-slate-400" />
                        <input
                            ref="searchInput"
                            v-model="query"
                            type="search"
                            placeholder="Пошук категорії..."
                            class="cat-picker__search-input"
                            autocomplete="off" />
                    </div>

                    <div class="cat-picker__body">
                        <button
                            type="button"
                            class="cat-picker__item cat-picker__item--all"
                            :class="{ 'cat-picker__item--active': !draft }"
                            @click="draft = ''">
                            <span>Усі категорії</span>
                            <AppIcon v-if="!draft" name="circle-check" :size="16" />
                        </button>

                        <div v-for="node in visibleTree" :key="node.url" class="cat-picker__branch">
                            <div class="cat-picker__row">
                                <button
                                    v-if="node.children?.length"
                                    type="button"
                                    class="cat-picker__expand"
                                    :aria-expanded="isExpanded(node.url)"
                                    @click="toggleExpand(node.url)">
                                    <AppIcon
                                        name="chevron-right"
                                        :size="16"
                                        :icon-class="isExpanded(node.url) ? 'rotate-90' : ''" />
                                </button>
                                <span v-else class="cat-picker__expand-spacer"></span>

                                <button
                                    type="button"
                                    class="cat-picker__item"
                                    :class="{ 'cat-picker__item--active': draft === node.url }"
                                    @click="select(node.url)">
                                    <span class="cat-picker__name">{{ node.name }}</span>
                                    <span v-if="node.count != null" class="cat-picker__count">{{ node.count }}</span>
                                </button>
                            </div>

                            <div v-if="node.children?.length && isExpanded(node.url)" class="cat-picker__children">
                                <div v-for="child in node.children" :key="child.url" class="cat-picker__branch">
                                    <div class="cat-picker__row">
                                        <button
                                            v-if="child.children?.length"
                                            type="button"
                                            class="cat-picker__expand"
                                            :aria-expanded="isExpanded(child.url)"
                                            @click="toggleExpand(child.url)">
                                            <AppIcon
                                                name="chevron-right"
                                                :size="16"
                                                :icon-class="isExpanded(child.url) ? 'rotate-90' : ''" />
                                        </button>
                                        <span v-else class="cat-picker__expand-spacer"></span>

                                        <button
                                            type="button"
                                            class="cat-picker__item"
                                            :class="{ 'cat-picker__item--active': draft === child.url }"
                                            @click="select(child.url)">
                                            <span class="cat-picker__name">{{ child.name }}</span>
                                            <span v-if="child.count != null" class="cat-picker__count">{{ child.count }}</span>
                                        </button>
                                    </div>

                                    <div v-if="child.children?.length && isExpanded(child.url)" class="cat-picker__children cat-picker__children--deep">
                                        <button
                                            v-for="grand in child.children"
                                            :key="grand.url"
                                            type="button"
                                            class="cat-picker__item cat-picker__item--leaf"
                                            :class="{ 'cat-picker__item--active': draft === grand.url }"
                                            @click="select(grand.url)">
                                            <span class="cat-picker__name">{{ grand.name }}</span>
                                            <span v-if="grand.count != null" class="cat-picker__count">{{ grand.count }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p v-if="!visibleTree.length" class="cat-picker__empty">Нічого не знайдено</p>
                    </div>

                    <div class="cat-picker__footer">
                        <button type="button" class="cat-picker__btn cat-picker__btn--ghost" @click="draft = ''">
                            Скинути
                        </button>
                        <button type="button" class="cat-picker__btn cat-picker__btn--primary" @click="confirm">
                            Обрати
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script>
import AppIcon from './AppIcon.vue';

export default {
    name: 'CategoryPickerModal',
    components: { AppIcon },
    props: {
        open: { type: Boolean, default: false },
        tree: { type: Array, default: () => [] },
        modelValue: { type: String, default: '' },
    },
    emits: ['update:modelValue', 'close'],
    data() {
        return {
            query: '',
            draft: '',
            expanded: {},
        };
    },
    computed: {
        visibleTree() {
            const q = this.query.trim().toLowerCase();
            if (!q) return this.tree;

            const filterNode = (node) => {
                const selfMatch = (node.name || '').toLowerCase().includes(q);
                const kids = (node.children || []).map(filterNode).filter(Boolean);
                if (selfMatch || kids.length) {
                    return { ...node, children: kids.length ? kids : (selfMatch ? node.children : []) };
                }
                return null;
            };

            return this.tree.map(filterNode).filter(Boolean);
        },
    },
    watch: {
        open(val) {
            if (val) {
                this.draft = this.modelValue || '';
                this.query = '';
                this.expanded = this.buildExpandedForSelected(this.draft);
                this.$nextTick(() => this.$refs.searchInput?.focus());
                document.body.style.overflow = 'hidden';
                return;
            }
            document.body.style.overflow = '';
        },
    },
    beforeUnmount() {
        document.body.style.overflow = '';
    },
    methods: {
        isExpanded(url) {
            return Boolean(this.expanded[url]) || Boolean(this.query.trim());
        },
        toggleExpand(url) {
            this.expanded = { ...this.expanded, [url]: !this.expanded[url] };
        },
        select(url) {
            this.draft = this.draft === url ? '' : url;
        },
        confirm() {
            this.$emit('update:modelValue', this.draft);
            document.body.style.overflow = '';
            this.$emit('close');
        },
        buildExpandedForSelected(url) {
            if (!url) return {};
            const map = {};
            const walk = (nodes, parents = []) => {
                for (const node of nodes) {
                    if (node.url === url) {
                        parents.forEach((p) => { map[p] = true; });
                        if (node.children?.length) map[node.url] = true;
                        return true;
                    }
                    if (node.children?.length && walk(node.children, [...parents, node.url])) {
                        return true;
                    }
                }
                return false;
            };
            walk(this.tree);
            return map;
        },
    },
};
</script>
