<template>
  <div class="shop-page">
    <header class="shop-header">
      <div class="container">
        <FeaturedCategory :category="featuredCategory" />
      </div>
    </header>

    <main class="shop-main container">
      <aside class="shop-sidebar">
        <div class="sidebar-section">
          <h3 class="sidebar-title">Categories</h3>
          <CategoryTree 
            :categories="categories" 
            :selected-id="selectedCategoryId"
            @select="handleCategorySelect"
          />
        </div>
        
        <div class="sidebar-divider"></div>
        
        <div class="sidebar-section">
          <PriceFilter 
            :min="0" 
            :max="1000"
            @update="handlePriceUpdate" 
          />
        </div>
      </aside>

      <section class="shop-content">
        <div v-if="loading" class="loading-state">
          <p>Loading products...</p>
        </div>

        <template v-else>
          <div class="toolbar">
            <p class="result-count">Showing {{ filteredProducts.length }} results</p>
            <SortSelect v-model="sortBy" />
          </div>

          <div v-if="filteredProducts.length > 0" class="product-grid">
          <ProductCard 
            v-for="product in filteredProducts" 
            :key="product.id" 
            :product="product" 
            :category-name="getCategoryName(product.categoryId)"
          />
        </div>
        
          <div v-else class="no-results">
            <p>No products found matching your filters.</p>
            <button @click="resetFilters" class="clear-btn">Clear Filters</button>
          </div>
        </template>
      </section>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useProducts, type Category } from '../composables/useProducts';

const { products, categories, featuredCategory, init, loading } = useProducts();

// Fetch data on mount
onMounted(() => {
  init();
});

// State
const selectedCategoryId = ref<number | null>(null);
const priceRange = ref<{ min: number; max: number } | null>(null);
const sortBy = ref<string>('default');

// Helper to find all category IDs (including children)
const getAllCategoryIds = (parentId: number): number[] => {
  const ids: number[] = [parentId];
  
  const findAndAddChildren = (cats: Category[]) => {
    for (const cat of cats) {
      if (cat.id === parentId) {
        // Found the target category, add all its descendants
        const addDescendants = (c: Category) => {
          if (c.children) {
            c.children.forEach((child: Category) => {
              ids.push(child.id);
              addDescendants(child);
            });
          }
        };
        addDescendants(cat);
        return true; // Stop searching
      }
      if (cat.children && findAndAddChildren(cat.children)) {
        return true;
      }
    }
    return false;
  };

  findAndAddChildren(categories.value);
  return ids;
};

// Actions
const handleCategorySelect = (id: number) => {
  if (selectedCategoryId.value === id) {
    selectedCategoryId.value = null; // Toggle off
  } else {
    selectedCategoryId.value = id;
  }
};

const handlePriceUpdate = (range: { min: number; max: number }) => {
  priceRange.value = range;
};

const resetFilters = () => {
  selectedCategoryId.value = null;
  priceRange.value = null;
  sortBy.value = 'default';
};

const getCategoryName = (id: number) => {
  const findName = (cats: Category[]): string | undefined => {
    for (const cat of cats) {
      if (cat.id === id) return cat.name;
      if (cat.children) {
        const found = findName(cat.children);
        if (found) return found;
      }
    }
    return undefined;
  };
  return findName(categories.value);
};

// Filtering & Sorting Logic
const filteredProducts = computed(() => {
  let result = [...products.value];

  // 1. Category Filter
  if (selectedCategoryId.value) {
    const validIds = getAllCategoryIds(selectedCategoryId.value);
    result = result.filter(p => validIds.includes(p.categoryId));
  }

  // 2. Price Filter
  if (priceRange.value) {
    if (priceRange.value.min !== undefined && priceRange.value.min !== null) {
      result = result.filter(p => p.price >= priceRange.value!.min);
    }
    if (priceRange.value.max !== undefined && priceRange.value.max !== null) {
      result = result.filter(p => p.price <= priceRange.value!.max);
    }
  }

  // 3. Sorting
  switch (sortBy.value) {
    case 'price-asc':
      result.sort((a, b) => a.price - b.price);
      break;
    case 'price-desc':
      result.sort((a, b) => b.price - a.price);
      break;
    case 'name-asc':
      result.sort((a, b) => a.name.localeCompare(b.name));
      break;
    case 'name-desc':
      result.sort((a, b) => b.name.localeCompare(a.name));
      break;
  }

  return result;
});
</script>

<style scoped>
.shop-page {
  padding-bottom: 64px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 16px;
}

.shop-header {
  margin-top: 32px;
}

.shop-main {
  display: flex;
  gap: 32px;
}

.shop-sidebar {
  width: 250px;
  flex-shrink: 0;
}

.shop-content {
  flex-grow: 1;
}

.sidebar-title {
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 16px;
  color: #2c3e50;
}

.sidebar-divider {
  height: 1px;
  background-color: #eee;
  margin: 24px 0;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid #eee;
}

.result-count {
  color: #666;
  font-size: 0.95rem;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 24px;
}

.no-results, .loading-state {
  text-align: center;
  padding: 48px;
  background-color: #f9f9f9;
  border-radius: 8px;
  color: #666;
}

.clear-btn {
  margin-top: 16px;
  background-color: #333;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
}

@media (max-width: 768px) {
  .shop-main {
    flex-direction: column;
  }
  
  .shop-sidebar {
    width: 100%;
  }

  .product-grid {
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  }
}
</style>
