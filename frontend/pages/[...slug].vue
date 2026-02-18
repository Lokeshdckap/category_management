<template>
  <div class="dynamic-page">
    <div v-if="loading" class="loading-overlay">
      <div class="loader"></div>
      <p>Loading...</p>
    </div>

    <template v-else-if="entityType === 'category'">
      <div class="shop-page">
        <header v-if="currentEntity" class="category-header">
          <div class="container">
            <nav class="breadcrumb">
              <NuxtLink to="/">Home</NuxtLink>
              <span class="separator">/</span>
              <span class="current">{{ currentEntity.name }}</span>
            </nav>
            <h1 class="category-title">{{ currentEntity.name }}</h1>
          </div>
        </header>

        <main class="shop-main container">
          <aside class="shop-sidebar">
            <div class="sidebar-section">
              <h3 class="sidebar-title">Categories</h3>
              <CategoryTree 
                :categories="sidebarCategories" 
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
              <p>No products found in this category.</p>
            </div>
          </section>
        </main>
      </div>
    </template>

    <template v-else-if="entityType === 'product'">
        <div class="pdp-wrapper">
            <div class="pdp-container">
            <nav class="breadcrumb">
                <NuxtLink to="/">Home</NuxtLink>
                <span class="separator">/</span>
                <NuxtLink v-if="currentEntity.category" :to="'/' + currentEntity.category.slug_url">
                    {{ currentEntity.category.name }}
                </NuxtLink>
                <span class="separator">/</span>
                <span class="current">{{ currentEntity.name }}</span>
            </nav>

            <div class="product-layout">
                <!-- Image Section -->
                <div class="image-gallery">
                <div class="main-image">
                    <img 
                    :src="mainImage || 'https://via.placeholder.com/600x600?text=No+Image'" 
                    :alt="currentEntity.name" 
                    />
                </div>
                <div v-if="currentEntity.images && currentEntity.images.length > 1" class="thumbnails">
                    <div 
                    v-for="(img, index) in currentEntity.images" 
                    :key="index" 
                    class="thumb"
                    :class="{ active: mainImage === `http://localhost:8000${img}` }"
                    @click="mainImage = `http://localhost:8000${img}`"
                    >
                    <img :src="`http://localhost:8000${img}`" />
                    </div>
                </div>
                </div>

                <!-- Info Section -->
                <div class="product-info">
                <div class="badge" v-if="currentEntity.category">{{ currentEntity.category.name }}</div>
                <h1 class="title">{{ currentEntity.name }}</h1>
                <p class="sku">SKU: {{ currentEntity.sku }}</p>

                <div class="price-section">
                    <span class="price">${{ displayPrice.toFixed(2) }}</span>
                    <span v-if="currentEntity.total_price > displayPrice" class="old-price">
                        ${{ currentEntity.total_price.toFixed(2) }}
                    </span>
                </div>

                <p class="description">{{ currentEntity.short_description || currentEntity.description }}</p>

                <div class="actions">
                    <div class="quantity-selector">
                    <button @click="qty > 1 && qty--">-</button>
                    <input type="number" v-model="qty" readonly />
                    <button @click="qty++">+</button>
                    </div>
                    <button class="add-to-cart" @click="handleAddToCart">Add to Cart</button>
                </div>

                <div class="features">
                    <div class="feature-item">
                    <span class="icon">🚚</span>
                    <span>Free shipping on orders over $100</span>
                    </div>
                    <div class="feature-item">
                    <span class="icon">🛡️</span>
                    <span>2 Year Warranty included</span>
                    </div>
                </div>

                <div class="tabs">
                    <h3 class="tab-title">Product Details</h3>
                    <div class="tab-content">
                    {{ currentEntity.description }}
                    </div>
                </div>
                </div>
            </div>

            <!-- Related Products Section -->
            <div v-if="currentEntity.related_products && currentEntity.related_products.length > 0" class="related-products">
                <h2 class="section-title">Related Products</h2>
                <div class="product-grid">
                    <ProductCard 
                        v-for="product in currentEntity.related_products" 
                        :key="product.id" 
                        :product="product"
                        :category-name="currentEntity.category?.name"
                    />
                </div>
            </div>
            </div>
        </div>
    </template>

    <div v-else class="error-state">
      <h2>Not found</h2>
      <NuxtLink to="/" class="btn">Back to Home</NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useProducts, type Category } from '../composables/useProducts';
import { useAuth } from '../composables/useAuth';

const route = useRoute();
const slug = computed(() => {
    const s = route.params.slug || '';
    return Array.isArray(s) ? s.join('/') : s;
});

const { products, categories, fetchProducts, fetchCategories, resolveSlug, getProductPrice, loading } = useProducts();
const { user } = useAuth();

const displayPrice = computed(() => {
    if (entityType.value !== 'product' || !currentEntity.value) return 0;
    return getProductPrice(currentEntity.value, user.value?.customer_group_id);
});

const entityType = ref<'category' | 'product' | null>(null);
const currentEntity = ref<any>(null);
const selectedCategoryId = ref<number | null>(null);
const priceRange = ref<{ min: number; max: number } | null>(null);
const sortBy = ref<string>('default');

// PDP specific state
const mainImage = ref<string>('');
const qty = ref(1);

const loadData = async () => {
    if (categories.value.length === 0) {
        await fetchCategories();
    }

    const resolved = await resolveSlug(slug.value);
    
    if (resolved) {
        entityType.value = resolved.type;
        currentEntity.value = resolved.data;
        
        if (resolved.type === 'category') {
            selectedCategoryId.value = resolved.data.id;
            await fetchProducts({ category_id: resolved.data.id });
        } else if (resolved.type === 'product') {
            mainImage.value = resolved.data.image ? `http://localhost:8000${resolved.data.image}` : '';
        }
    } else {
        entityType.value = null;
    }
};

onMounted(loadData);
watch(slug, loadData);

const sidebarCategories = computed(() => {
    return categories.value;
});

const handleCategorySelect = (id: number) => {
    selectedCategoryId.value = id;
};

const handlePriceUpdate = (range: { min: number; max: number }) => {
  priceRange.value = range;
};

const handleAddToCart = () => {
    alert(`Added ${currentEntity.value.name} to cart!`);
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

const filteredProducts = computed(() => {
  let result = [...products.value];
  if (priceRange.value) {
    if (priceRange.value.min !== undefined && priceRange.value.min !== null) {
      result = result.filter(p => p.price >= priceRange.value!.min);
    }
    if (priceRange.value.max !== undefined && priceRange.value.max !== null) {
      result = result.filter(p => p.price <= priceRange.value!.max);
    }
  }
  switch (sortBy.value) {
    case 'price-asc': result.sort((a, b) => a.price - b.price); break;
    case 'price-desc': result.sort((a, b) => b.price - a.price); break;
    case 'name-asc': result.sort((a, b) => a.name.localeCompare(b.name)); break;
    case 'name-desc': result.sort((a, b) => b.name.localeCompare(a.name)); break;
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

.category-header {
  background: white;
  padding: 40px 0;
  border-bottom: 1px solid #eee;
  margin-bottom: 40px;
}

.breadcrumb {
  margin-bottom: 10px;
  font-size: 0.9rem;
  color: #888;
}

.breadcrumb .separator {
  margin: 0 10px;
}

.breadcrumb a {
  text-decoration: none;
  color: #666;
}

.category-title {
  font-size: 2.5rem;
  font-weight: 800;
  color: #2c3e50;
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
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 24px;
}

.loading-state, .no-results {
  text-align: center;
  padding: 60px;
  background: #f9f9f9;
  border-radius: 12px;
  color: #666;
}

@media (max-width: 768px) {
  .shop-main {
    flex-direction: column;
  }
  .shop-sidebar {
    width: 100%;
  }
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: white;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.loader {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* PDP Styles */
.pdp-wrapper {
  padding: 40px 0 80px;
  background-color: #f8fafc;
  min-height: 100vh;
}

.pdp-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.product-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
  margin-top: 30px;
  background: white;
  padding: 40px;
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.main-image {
  aspect-ratio: 1;
  background: #f1f5f9;
  border-radius: 16px;
  overflow: hidden;
  margin-bottom: 20px;
}

.main-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.thumbnails {
  display: flex;
  gap: 12px;
}

.thumb {
  width: 80px;
  height: 80px;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  border: 2px solid transparent;
  transition: all 0.2s;
}

.thumb.active {
  border-color: #3b82f6;
}

.thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.badge {
  display: inline-block;
  padding: 4px 12px;
  background: #eff6ff;
  color: #3b82f6;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 16px;
}

.title {
  font-size: 2.5rem;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 8px;
  line-height: 1.2;
}

.sku {
  color: #94a3b8;
  font-size: 0.9rem;
  margin-bottom: 24px;
}

.price-section {
  display: flex;
  align-items: baseline;
  gap: 12px;
  margin-bottom: 32px;
}

.price {
  font-size: 2rem;
  font-weight: 700;
  color: #3b82f6;
}

.old-price {
  color: #94a3b8;
  text-decoration: line-through;
  font-size: 1.2rem;
}

.description {
  color: #64748b;
  line-height: 1.6;
  margin-bottom: 40px;
  font-size: 1.1rem;
}

.actions {
  display: flex;
  gap: 20px;
  margin-bottom: 40px;
}

.quantity-selector {
  display: flex;
  align-items: center;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}

.quantity-selector button {
  width: 48px;
  height: 48px;
  background: white;
  border: none;
  cursor: pointer;
  font-size: 1.2rem;
  transition: background 0.2s;
}

.quantity-selector button:hover {
  background: #f8fafc;
}

.quantity-selector input {
  width: 60px;
  height: 48px;
  border: none;
  border-left: 1px solid #e2e8f0;
  border-right: 1px solid #e2e8f0;
  text-align: center;
  font-weight: 600;
}

.add-to-cart {
  flex-grow: 1;
  background: #1e293b;
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s;
}

.add-to-cart:hover {
  background: #334155;
}

.features {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 40px;
  padding-top: 40px;
  border-top: 1px solid #f1f5f9;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 12px;
  color: #475569;
  font-size: 0.9rem;
}

.feature-item .icon {
  font-size: 1.2rem;
}

.tabs {
  margin-top: 40px;
}

.tab-title {
  font-size: 1.2rem;
  font-weight: 700;
  margin-bottom: 16px;
  color: #1e293b;
}

.tab-content {
  color: #64748b;
  line-height: 1.8;
}

/* Related Products Styles */
.related-products {
  margin-top: 80px;
  padding-top: 60px;
  border-top: 1px solid #e2e8f0;
}

.related-products .section-title {
  font-size: 1.8rem;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 32px;
  text-align: center;
}

.error-state {
  text-align: center;
  padding: 100px 20px;
}

@media (max-width: 968px) {
  .product-layout {
    grid-template-columns: 1fr;
    gap: 40px;
    padding: 24px;
  }
}
</style>
