<template>
  <div class="sort-select">
    <label for="sort" class="sort-label">Sort by:</label>
    <select id="sort" :value="modelValue" @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)" class="sort-dropdown">
      <option value="default">Default</option>
      <option value="price-asc">Price: Low to High</option>
      <option value="price-desc">Price: High to Low</option>
      <option value="name-asc">Name: A to Z</option>
      <option value="name-desc">Name: Z to A</option>
    </select>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: string;
}>();

defineEmits(['update:modelValue']);
</script>

<style scoped>
.sort-select {
  display: flex;
  align-items: center;
  gap: 8px;
}

.sort-label {
  font-size: 0.9rem;
  color: #666;
  white-space: nowrap;
}

.sort-dropdown {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 0.9rem;
  outline: none;
  background-color: white;
  cursor: pointer;
  transition: border-color 0.2s;
}

.sort-dropdown:focus {
  border-color: #3498db;
}
</style>
