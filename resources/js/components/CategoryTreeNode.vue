<template>
  <div class="category-tree-node">
    <div
      @click="$emit('select', category.id)"
      :class="[
        'flex items-center justify-between py-2 px-3 rounded-md hover:bg-gray-100 cursor-pointer transition-colors',
        selectedId === category.id ? 'bg-blue-50 border-l-4 border-blue-500' : ''
      ]">
      <div class="flex items-center space-x-2">
        <svg
          v-if="category.children && category.children.length > 0"
          @click.stop="toggleExpanded"
          :class="[
            'w-4 h-4 text-gray-500 cursor-pointer transition-transform',
            isExpanded ? 'transform rotate-90' : ''
          ]"
          fill="currentColor"
          viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
        </svg>
        <div v-else class="w-4 h-4"></div>
        
        <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
          <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
        </svg>
        
        <span class="text-sm font-medium text-gray-900">{{ category.name }}</span>
      </div>
      
      <div class="flex items-center space-x-2">
        <span class="text-xs text-gray-500">{{ category.products_count || 0 }}</span>
        <div class="flex space-x-1" @click.stop>
          <button
            @click="$emit('create-child', category.id)"
            class="p-1 text-gray-400 hover:text-blue-600 transition-colors"
            title="Добавить подкатегорию">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
            </svg>
          </button>
          <button
            @click="$emit('edit', category.id)"
            class="p-1 text-gray-400 hover:text-yellow-600 transition-colors"
            title="Редактировать">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
            </svg>
          </button>
          <button
            @click="$emit('delete', category.id)"
            class="p-1 text-gray-400 hover:text-red-600 transition-colors"
            title="Удалить">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd"/>
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
          </button>
        </div>
      </div>
    </div>
    
    <!-- Вложенные категории -->
    <div v-if="isExpanded && category.children && category.children.length > 0" class="ml-4 space-y-1 mt-1">
      <CategoryTreeNode
        v-for="child in category.children"
        :key="child.id"
        :category="child"
        :selected-id="selectedId"
        @select="$emit('select', $event)"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
        @create-child="$emit('create-child', $event)"
      />
    </div>
  </div>
</template>

<script>
export default {
  name: 'CategoryTreeNode',
  props: {
    category: {
      type: Object,
      required: true
    },
    selectedId: {
      type: Number,
      default: null
    }
  },
  data() {
    return {
      isExpanded: false
    };
  },
  methods: {
    toggleExpanded() {
      this.isExpanded = !this.isExpanded;
    }
  }
};
</script>

<style scoped>
.category-tree-node {
  position: relative;
}
</style>
