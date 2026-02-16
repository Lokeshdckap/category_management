<template>
  <div class="price-filter">
    <h3 class="filter-title">Price Range</h3>
    <div class="price-inputs">
      <div class="input-group">
        <span class="currency">$</span>
        <input 
          type="number" 
          v-model.number="minPrice" 
          placeholder="Min" 
          class="price-input" 
          min="0"
        />
      </div>
      <span class="separator">-</span>
      <div class="input-group">
        <span class="currency">$</span>
        <input 
          type="number" 
          v-model.number="maxPrice" 
          placeholder="Max" 
          class="price-input" 
          min="0"
        />
      </div>
    </div>
    <button class="apply-btn" @click="applyFilter">Apply</button>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

const props = defineProps<{
  min?: number;
  max?: number;
}>();

const emit = defineEmits(['update']);

const minPrice = ref(props.min);
const maxPrice = ref(props.max);

const applyFilter = () => {
  emit('update', { min: minPrice.value, max: maxPrice.value });
};

// Sync local state if props change (optional, but good practice)
watch(() => props.min, (newVal) => minPrice.value = newVal);
watch(() => props.max, (newVal) => maxPrice.value = newVal);
</script>

<style scoped>
.price-filter {
  margin-bottom: 24px;
}

.filter-title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 12px;
  color: #333;
}

.price-inputs {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.input-group {
  position: relative;
  flex: 1;
}

.currency {
  position: absolute;
  left: 8px;
  top: 50%;
  transform: translateY(-50%);
  color: #888;
  font-size: 0.9rem;
}

.price-input {
  width: 100%;
  padding: 8px 8px 8px 20px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.9rem;
  outline: none;
  transition: border-color 0.2s;
}

.price-input:focus {
  border-color: #3498db;
}

.separator {
  color: #888;
}

.apply-btn {
  width: 100%;
  padding: 8px;
  background-color: #333;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.2s;
}

.apply-btn:hover {
  background-color: #000;
}
</style>
