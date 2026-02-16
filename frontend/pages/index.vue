<template>
  <div class="home-page">
    <!-- Hero Section -->
    <header class="hero">
      <div class="container hero-container">
        <FeaturedCategory :category="featuredCategory" />
      </div>
    </header>

    <!-- Featured Categories Section -->
    <section class="featured-categories container">
      <div class="section-header">
        <h2 class="section-title">Shop by Category</h2>
        <p class="section-subtitle">Discover our curated collections</p>
      </div>

      <div v-if="loading" class="loading-state">
        <p>Loading categories...</p>
      </div>

      <div v-else class="category-grid">
        <NuxtLink 
          v-for="cat in featuredCategories" 
          :key="cat.id" 
          :to="'/' + cat.slug_url" 
          class="category-card"
        >
          <div class="category-image">
            <img 
  :src="cat.image 
    ? `http://127.0.0.1:8000${cat.image}` 
    : 'https://via.placeholder.com/400x300?text=No+Image'"
  :alt="cat.name" 
/>

          </div>
          <div class="category-info">
            <h3 class="category-name">{{ cat.name }}</h3>
            <span class="explore-link">Explore Collection →</span>
          </div>
        </NuxtLink>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useProducts } from '../composables/useProducts';

const { featuredCategory, featuredCategories, init, loading } = useProducts();

onMounted(() => {
  init();
});
</script>

<style scoped>
.home-page {
  padding-bottom: 80px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.hero {
  margin-top: 40px;
  margin-bottom: 60px;
}

.section-header {
  text-align: center;
  margin-bottom: 40px;
}

.section-title {
  font-size: 2.5rem;
  font-weight: 800;
  color: #2c3e50;
  margin: 0 0 10px;
}

.section-subtitle {
  color: #888;
  font-size: 1.1rem;
}

.category-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 30px;
}

.category-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0,0,0,0.05);
  text-decoration: none;
  color: inherit;
  transition: transform 0.3s, box-shadow 0.3s;
  border: 1px solid #eee;
}

.category-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 30px rgba(0,0,0,0.1);
}

.category-image {
  height: 200px;
  overflow: hidden;
}

.category-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s;
}

.category-card:hover .category-image img {
  transform: scale(1.1);
}

.category-info {
  padding: 24px;
}

.category-name {
  font-size: 1.4rem;
  font-weight: 700;
  margin: 0 0 12px;
  color: #2c3e50;
}

.explore-link {
  color: #3498db;
  font-weight: 600;
  font-size: 0.9rem;
}

.loading-state {
  text-align: center;
  padding: 60px;
  color: #888;
}

.cta-section {
    margin-top: 80px;
}

.cta-card {
    background: #2c3e50;
    color: white;
    padding: 60px;
    border-radius: 20px;
    text-align: center;
}

.cta-card h2 {
    font-size: 2.2rem;
    margin-bottom: 30px;
}

.btn-primary {
    display: inline-block;
    background: #3498db;
    color: white;
    padding: 15px 40px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 700;
    font-size: 1.1rem;
    transition: background 0.2s;
}

.btn-primary:hover {
    background: #2980b9;
}

@media (max-width: 768px) {
  .section-title {
    font-size: 2rem;
  }
}
</style>
