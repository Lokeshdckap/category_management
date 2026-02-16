<template>
  <ul class="category-tree">
    <li v-for="category in categories" :key="category.id" class="category-item">
      <div 
        class="category-label" 
        :class="{ 'active': selectedId === category.id }"
        @click="handleCategoryClick(category)"
      >
        <div class="checkbox-container">
          <div class="checkbox" :class="{ 'checked': selectedId === category.id }">
            <span v-if="selectedId === category.id" class="check-icon">✓</span>
          </div>
          <span class="category-name">{{ category.name }}</span>
        </div>
        <span v-if="category.children && category.children.length" class="toggle-icon">
          {{ expandedIds.includes(category.id) ? '−' : '+' }}
        </span>
      </div>
      
      <div v-if="category.children && category.children.length" class="subcategory-wrapper">
         <CategoryTree 
           v-if="expandedIds.includes(category.id)"
           :categories="category.children" 
           :selected-id="selectedId"
           @select="$emit('select', $event)"
         />
      </div>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import type { Category } from '../composables/useProducts';

const props = defineProps<{
  categories: Category[];
  selectedId?: number | null;
}>();

const emit = defineEmits(['select']);

const expandedIds = ref<number[]>([]);

watch(() => props.selectedId, (newId) => {
  if (newId) {
    const expandPath = (cats: Category[]): boolean => {
      for (const cat of cats) {
        if (cat.id === newId) return true;
        if (cat.children && expandPath(cat.children)) {
          if (!expandedIds.value.includes(cat.id)) {
            expandedIds.value.push(cat.id);
          }
          return true;
        }
      }
      return false;
    };
    expandPath(props.categories);
  }
}, { immediate: true });

const toggleExpand = (id: number) => {
  const index = expandedIds.value.indexOf(id);
  if (index > -1) {
    expandedIds.value.splice(index, 1);
  } else {
    expandedIds.value.push(id);
  }
};

const handleCategoryClick = (category: Category) => {
  if (category.slug_url) {
    navigateTo('/' + category.slug_url);
  }
  emit('select', category.id);
  if (category.children && category.children.length) {
    toggleExpand(category.id);
  }
};
</script>

<style scoped>
.category-tree {
  list-style: none;
  padding: 0;
  margin: 0;
}

.category-item {
  margin-bottom: 4px;
}

.category-label {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  cursor: pointer;
  border-radius: 6px;
  transition: background-color 0.2s;
  color: #555;
}

.category-label:hover {
  background-color: #f5f5f5;
  color: #333;
}

.category-label.active {
  background-color: #f0f7ff;
  color: #1976d2;
  font-weight: 600;
}

.checkbox-container {
  display: flex;
  align-items: center;
  gap: 10px;
}

.checkbox {
  width: 18px;
  height: 18px;
  border: 1px solid #ccc;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  background: white;
  flex-shrink: 0;
}

.checkbox.checked {
  background-color: #1976d2;
  border-color: #1976d2;
}

.check-icon {
  color: white;
  font-size: 10px;
  font-weight: bold;
  line-height: 1;
}

.category-name {
  flex-grow: 1;
}

.toggle-icon {
  font-size: 1.2rem;
  color: #999;
  margin-left: 8px;
  line-height: 1;
}

.subcategory-wrapper {
  padding-left: 16px;
  margin-top: 2px;
}
</style>
