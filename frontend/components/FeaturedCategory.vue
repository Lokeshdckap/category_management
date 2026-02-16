<template>
  <NuxtLink :to="category.slug_url ? '/' + category.slug_url : '/shop'" class="featured-category-link">
    <div class="featured-category" :style="{ backgroundImage: `url(${category.image})` }">
      <div class="featured-overlay">
        <div class="featured-content">
          <span class="featured-label">Featured Collection</span>
          <h2 class="featured-title">{{ category.name }}</h2>
          <button class="featured-btn">Shop Now</button>
        </div>
      </div>
    </div>
  </NuxtLink>
</template>

<script setup lang="ts">
import type { Category } from '../composables/useProducts';

defineProps<{
  category: Category;
}>();
</script>

<style scoped>
.featured-category-link {
  text-decoration: none;
  color: inherit;
  display: block;
}

.featured-category {
  width: 100%;
  height: 300px;
  border-radius: 16px;
  background-size: cover;
  background-position: center;
  position: relative;
  overflow: hidden;
  margin-bottom: 32px;
}

.featured-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(to right, rgba(0, 0, 0, 0.7) 0%, rgba(0, 0, 0, 0.2) 100%);
  display: flex;
  align-items: center;
  padding: 48px;
}

.featured-content {
  color: white;
  max-width: 500px;
}

.featured-label {
  text-transform: uppercase;
  letter-spacing: 2px;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 12px;
  display: block;
  opacity: 0.9;
}

.featured-title {
  font-size: 3rem;
  font-weight: 800;
  margin: 0 0 24px;
  line-height: 1.1;
}

.featured-btn {
  background-color: white;
  color: #333;
  border: none;
  padding: 12px 28px;
  border-radius: 50px;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.featured-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

@media (max-width: 768px) {
  .featured-category {
    height: 250px;
  }
  
  .featured-overlay {
    padding: 24px;
  }
  
  .featured-title {
    font-size: 2rem;
  }
}
</style>
