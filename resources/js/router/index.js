import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/',
        redirect: '/login'
    },
    {
        path: '/login',
        name: 'Login',
        component: () => import('../pages/auth/LoginPage.vue'),
        meta: {
            guest: true,
            layout: 'auth'
        }
    },
    {
        path: '/dashboard',
        name: 'Dashboard',
        component: () => import('../pages/admin/DashboardPage.vue'),
        meta: {
            requiresAuth: true,
            layout: 'admin'
        }
    },
    {
        path: '/categories',
        name: 'CategoryList',
        component: () => import('../pages/admin/categories/CategoryList.vue'),
        meta: {
            requiresAuth: true,
            layout: 'admin'
        }
    },
    {
        path: '/categories/create',
        name: 'CategoryCreate',
        component: () => import('../pages/admin/categories/CategoryForm.vue'),
        meta: {
            requiresAuth: true,
            layout: 'admin'
        }
    },
    {
        path: '/categories/:uuid/edit',
        name: 'CategoryEdit',
        component: () => import('../pages/admin/categories/CategoryForm.vue'),
        meta: {
            requiresAuth: true,
            layout: 'admin'
        }
    },
    {
        path: '/products',
        name: 'ProductList',
        component: () => import('../pages/admin/products/ProductList.vue'),
        meta: {
            requiresAuth: true,
            layout: 'admin'
        }
    },
    {
        path: '/products/create',
        name: 'ProductCreate',
        component: () => import('../pages/admin/products/ProductCreate.vue'),
        meta: {
            requiresAuth: true,
            layout: 'admin'
        }
    },
    {
        path: '/products/:uuid/edit',
        name: 'ProductEdit',
        component: () => import('../pages/admin/products/ProductEdit.vue'),
        meta: {
            requiresAuth: true,
            layout: 'admin'
        }
    },
    {
        path: '/suppliers',
        name: 'SupplierList',
        component: () => import('../pages/admin/suppliers/SupplierList.vue'),
        meta: {
            requiresAuth: true,
            layout: 'admin'
        }
    },
    {
        path: '/supplier/create',
        name: 'SupplierCreate',
        component: () => import('../pages/admin/suppliers/SupplierForm.vue'),
        meta: {
            requiresAuth: true,
            layout: 'admin'
        }
    },
    {
        path: '/suppliers/:id/edit',
        name: 'SupplierEdit',
        component: () => import('../pages/admin/suppliers/SupplierForm.vue'),
        meta: {
            requiresAuth: true,
            layout: 'admin'
        }
    },
    {
        path: '/product-reports',
        name: 'ProductReport',
        component: () => import('../pages/admin/reports/ProductReport.vue'),
        meta: {
            requiresAuth: true,
            layout: 'admin'
        }
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'NotFound',
        component: () => import('../pages/ErrorNotFound.vue'),
        meta: {
            layout: 'auth'
        }
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

// Navigation guard
router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('auth_token');

    if (to.meta.requiresAuth && !token) {
        next({ name: 'Login' });
    } else if (to.meta.guest && token) {
        next({ name: 'Dashboard' });
    } else {
        next();
    }
});

export default router;