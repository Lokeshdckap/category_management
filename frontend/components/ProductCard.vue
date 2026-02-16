<template>
  <NuxtLink :to="product.category_slug_url ? `/${product.category_slug_url}/${product.slug}` : `/products/${product.slug}`" class="product-card-link">
    <div class="product-card">
      <div class="product-image-container">
        <img 
          :src="`http://localhost:8000${product.image}`" 
          :alt="product.name" 
          class="product-image" 
          loading="lazy" 
        />
      </div>
      <div class="product-details">
        <h3 class="product-title">{{ product.name }}</h3>
        <p class="product-category" v-if="categoryName">{{ categoryName }}</p>
        <div class="product-footer">
          <span class="product-price">${{ product.price.toFixed(2) }}</span>
          <button class="add-to-cart-btn" @click.stop.prevent="handleAddToCart">Add to Cart</button>
        </div>
      </div>
    </div>
  </NuxtLink>
</template>

<script setup lang="ts">
import type { Product } from '../composables/useProducts';

const props = defineProps<{
  product: Product;
  categoryName?: string;
}>();
const handleAddToCart = () => {
  // Logic for adding to cart
  alert(`Added ${props.product.name} to cart!`);
};
</script>

<style scoped>
.product-card-link {
  text-decoration: none;
  color: inherit;
  display: block;
}

.product-card {
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  overflow: hidden;
  background-color: #fff;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  display: flex;
  flex-direction: column;
}

.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
}

.product-image-container {
  width: 100%;
  height: 200px;
  overflow: hidden;
  background-color: #f5f5f5;
}

.product-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.product-card:hover .product-image {
  transform: scale(1.05);
}

.product-details {
  padding: 16px;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.product-title {
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 8px;
  color: #333;
  line-height: 1.4;
  /* Limit to 2 lines */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  flex-grow: 1;
}

.product-category {
  font-size: 0.85rem;
  color: #888;
  margin: 0 0 12px;
}

.product-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
}

.product-price {
  font-size: 1.25rem;
  font-weight: 700;
  color: #2c3e50;
}

.add-to-cart-btn {
  background-color: #3498db;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.add-to-cart-btn:hover {
  background-color: #2980b9;
}
</style>
