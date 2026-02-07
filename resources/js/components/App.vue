<template>
    <component :is="layoutComponent">
        <router-view />
    </component>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import AuthLayout from '../layouts/AuthLayout.vue';
import AdminLayout from '../layouts/AdminLayout.vue';

const route = useRoute();

const layoutComponent = computed(() => {
    const layoutName = route.meta.layout || 'main';
    
    const layouts = {
        auth: AuthLayout,
        admin: AdminLayout
    };
    
    return layouts[layoutName] || AdminLayout; 
});
</script>