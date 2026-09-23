<template>
  <div class="flex h-screen bg-gray-50">
    <!-- Левая панель - Древовидная структура -->
    <div class="w-1/3 bg-white border-r border-gray-200 overflow-y-auto">
      <div class="p-4">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">Категории</h2>
          <button 
            @click="createCategory('Новая категория')"
            class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
            + Добавить
          </button>
        </div>
        
        <!-- Древовидная структура категорий -->
        <div class="space-y-1">
          <CategoryTreeNode
            v-for="category in categories"
            :key="category.id"
            :category="category"
            :selected-id="selectedCategory?.id"
            @select="selectCategory"
            @edit="editCategory"
            @delete="deleteCategory"
            @create-child="createChildCategory"
          />
        </div>
      </div>
    </div>

    <!-- Правая панель - Детальный просмотр -->
    <div class="flex-1 bg-white overflow-y-auto">
      <div v-if="selectedCategory" class="p-6">
        <!-- Заголовок выбранного элемента -->
        <div class="mb-6">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center space-x-3">
              <svg class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
              </svg>
              <h1 class="text-2xl font-bold text-gray-900">{{ selectedCategory.name }}</h1>
              <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                Категория
              </span>
            </div>
            <div class="flex space-x-2">
              <button 
                @click="createChildCategory(selectedCategory.id)"
                class="px-3 py-1 text-sm bg-green-600 text-black rounded hover:bg-green-700">
                + Подкатегория
              </button>
              <button 
                @click="editCategory(selectedCategory.id)"
                class="px-3 py-1 text-sm bg-yellow-600 text-black rounded hover:bg-yellow">
                ✏️ Редактировать
              </button>
              <button 
                @click="deleteCategory(selectedCategory.id)"
                class="px-3 py-1 text-sm bg-red-600 text-black rounded hover:bg-red-700">
                🗑️ Удалить
              </button>
            </div>
          </div>
          <p class="text-sm text-gray-500">
            {{ selectedCategory.products_count || 0 }} товаров • 
            {{ selectedCategory.subcategories_count || 0 }} подкатегорий
          </p>
        </div>

        <!-- Подкатегории -->
        <div v-if="subcategories.length > 0" class="mb-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-3">Подкатегории</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="subcategory in subcategories"
              :key="subcategory.id"
              @click="selectCategory(subcategory.id)"
              class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer">
              <div class="flex items-center space-x-2 mb-2">
                <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                </svg>
                <span class="font-medium text-gray-900">{{ subcategory.name }}</span>
              </div>
              <p class="text-sm text-gray-500">{{ subcategory.products_count || 0 }} товаров</p>
            </div>
          </div>
        </div>

        <!-- Товары -->
        <div>
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Товары</h3>
            <span class="text-sm text-gray-500">{{ products.length }} товаров</span>
          </div>
          <div v-if="products.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div
              v-for="product in products"
              :key="product.id"
              class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
              <div class="flex items-start space-x-3">
                <img
                  v-if="product.image_path"
                  :src="`/storage/${product.image_path}`"
                  :alt="product.name"
                  class="w-16 h-16 object-cover rounded">
                <div v-else class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center text-gray-400">
                  <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                </div>
                <div class="flex-1">
                  <p class="font-medium text-gray-900">{{ product.name }}</p>
                  <p class="text-sm text-gray-500">Артикул: {{ product.articule }}</p>
                  <p class="text-sm text-gray-500">Цена: {{ formatPrice(product.price) }} UAH</p>
                </div>
                <button 
                  @click="removeProductFromCategory(product.id)"
                  class="text-red-500 hover:text-red-700">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
          <p v-else class="text-gray-500">Нет товаров в этой категории.</p>
        </div>
      </div>

      <!-- Placeholder когда ничего не выбрано -->
      <div v-else class="flex items-center justify-center h-full">
        <div class="text-center text-gray-500">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Выберите элемент</h3>
          <p class="mt-1 text-sm text-gray-500">Кликните на категорию для просмотра содержимого.</p>
        </div>
      </div>
    </div>

    <!-- Notification Toast -->
    <div
      v-if="notification.show"
      class="fixed bottom-4 right-4 max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
      <div class="p-4">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <svg v-if="notification.type === 'success'" class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <svg v-else-if="notification.type === 'error'" class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="ml-3 w-0 flex-1 pt-0.5">
            <p class="text-sm font-medium text-gray-900">{{ notification.title }}</p>
            <p class="mt-1 text-sm text-gray-500">{{ notification.message }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import CategoryTreeNode from './CategoryTreeNode.vue';

export default {
  name: 'CategoryTreeManager',
  components: {
    CategoryTreeNode
  },
  data() {
    return {
      categories: [],
      selectedCategory: null,
      subcategories: [],
      products: [],
      notification: {
        show: false,
        type: 'success',
        title: '',
        message: ''
      }
    };
  },
  mounted() {
    console.log('CategoryTreeManager mounted!');
    this.loadCategories();
  },
  created() {
    console.log('CategoryTreeManager created!');
  },
  methods: {
    async loadCategories() {
      try {
        console.log('Loading categories from API...');
        const response = await fetch('/api/categories/tree');
        console.log('API response status:', response.status);
        const data = await response.json();
        console.log('Categories loaded:', data);
        this.categories = data;
      } catch (error) {
        console.error('Error loading categories:', error);
        this.showNotification('error', 'Ошибка', 'Не удалось загрузить категории');
      }
    },
    
    async selectCategory(categoryId) {
      try {
        const response = await fetch(`/api/categories/${categoryId}`);
        const data = await response.json();
        this.selectedCategory = data.category;
        this.subcategories = data.subcategories || [];
        this.products = data.products || [];
      } catch (error) {
        this.showNotification('error', 'Ошибка', 'Не удалось загрузить данные категории');
      }
    },
    
    async createCategory(name, parentId = null) {
      const categoryName = prompt('Название категории:', name);
      if (!categoryName || categoryName.trim() === '') return;
      
      try {
        const response = await fetch('/api/categories', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({
            name: categoryName.trim(),
            parent_id: parentId,
            is_active: true,
            sort_order: 0
          })
        });
        
        if (response.ok) {
          this.showNotification('success', 'Успех', `Категория "${categoryName}" создана`);
          await this.loadCategories();
          if (parentId === this.selectedCategory?.id) {
            await this.selectCategory(parentId);
          }
        } else {
          throw new Error('Ошибка при создании категории');
        }
      } catch (error) {
        this.showNotification('error', 'Ошибка', 'Не удалось создать категорию');
      }
    },
    
    createChildCategory(parentId) {
      this.createCategory('Новая подкатегория', parentId);
    },
    
    async editCategory(categoryId) {
      const category = await this.getCategoryById(categoryId);
      if (!category) return;
      
      const newName = prompt('Новое название:', category.name);
      if (!newName || newName.trim() === '') return;
      
      try {
        const response = await fetch(`/api/categories/${categoryId}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({
            name: newName.trim()
          })
        });
        
        if (response.ok) {
          this.showNotification('success', 'Успех', 'Категория обновлена');
          await this.loadCategories();
          if (this.selectedCategory?.id === categoryId) {
            await this.selectCategory(categoryId);
          }
        } else {
          throw new Error('Ошибка при обновлении категории');
        }
      } catch (error) {
        this.showNotification('error', 'Ошибка', 'Не удалось обновить категорию');
      }
    },
    
    async deleteCategory(categoryId) {
      const category = await this.getCategoryById(categoryId);
      if (!category) return;
      
      if (!confirm(`Вы уверены, что хотите удалить категорию "${category.name}"? Все подкатегории будут перемещены на уровень выше.`)) {
        return;
      }
      
      try {
        const response = await fetch(`/api/categories/${categoryId}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          }
        });
        
        if (response.ok) {
          this.showNotification('success', 'Успех', 'Категория удалена');
          await this.loadCategories();
          if (this.selectedCategory?.id === categoryId) {
            this.selectedCategory = null;
            this.subcategories = [];
            this.products = [];
          }
        } else {
          throw new Error('Ошибка при удалении категории');
        }
      } catch (error) {
        this.showNotification('error', 'Ошибка', 'Не удалось удалить категорию');
      }
    },
    
    async removeProductFromCategory(productId) {
      if (!confirm('Вы уверены, что хотите отвязать этот товар от категории?')) {
        return;
      }
      
      try {
        const response = await fetch(`/api/categories/${this.selectedCategory.id}/products/${productId}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          }
        });
        
        if (response.ok) {
          this.showNotification('success', 'Успех', 'Товар отвязан от категории');
          await this.selectCategory(this.selectedCategory.id);
        } else {
          throw new Error('Ошибка при отвязке товара');
        }
      } catch (error) {
        this.showNotification('error', 'Ошибка', 'Не удалось отвязать товар');
      }
    },
    
    async getCategoryById(categoryId) {
      const findCategory = (categories, id) => {
        for (const category of categories) {
          if (category.id === id) return category;
          if (category.children && category.children.length > 0) {
            const found = findCategory(category.children, id);
            if (found) return found;
          }
        }
        return null;
      };
      
      return findCategory(this.categories, categoryId) || this.selectedCategory;
    },
    
    formatPrice(price) {
      return parseFloat(price).toFixed(2);
    },
    
    showNotification(type, title, message) {
      this.notification = {
        show: true,
        type,
        title,
        message
      };
      
      setTimeout(() => {
        this.notification.show = false;
      }, 3000);
    }
  }
};
</script>

<style scoped>
/* Можно добавить дополнительные стили если нужно */
</style>
