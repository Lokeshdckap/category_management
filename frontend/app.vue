<template>
  <div class="app-container">
    <Navbar />
    <NuxtRouteAnnouncer />
    <NuxtPage />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';

const { initAuth } = useAuth();

onMounted(() => {
  initAuth();
});
</script>

<style>
/* Global resets */
body {
  margin: 0;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
  background-color: #f9fafb;
  color: #333;
}

* {
  box-sizing: border-box;
}

a {
  text-decoration: none;
  color: inherit;
}
</style>
