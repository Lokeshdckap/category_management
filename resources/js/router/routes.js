const routes = [
    {
        path: '/',
        redirect: '/auth/login'  // Add this redirect
    },
    {
        path: '/auth',
        component: () => import('layouts/AuthLayout.vue'),
        children: [
            {
                path: 'login',
                name: 'Login',
                component: () => import('pages/auth/LoginPage.vue'),
                meta: { guest: true }
            }
        ]
    },
    {
        path: '/admin',
        component: () => import('layouts/AdminLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                redirect: '/admin/dashboard'  // Add redirect for /admin
            },
            {
                path: 'dashboard',
                name: 'Dashboard',
                component: () => import('pages/admin/DashboardPage.vue')
            },
            {
                path: 'categories',
                name: 'CategoryList',
                component: () => import('pages/admin/categories/CategoryList.vue')
            },
            {
                path: 'categories/create',
                name: 'CategoryCreate',
                component: () => import('pages/admin/categories/CategoryForm.vue')
            },
            {
                path: 'categories/:uuid/edit',
                name: 'CategoryEdit',
                component: () => import('pages/admin/categories/CategoryForm.vue')
            },
            {
                path: 'products',
                name: 'ProductList',
                component: () => import('pages/admin/products/ProductList.vue')
            },
            {
                path: 'products/create',
                name: 'ProductCreate',
                component: () => import('pages/admin/products/ProductCreate.vue')
            },
            {
                path: 'suppliers/create',
                name: 'SupplierCreate',
                component: () => import('pages/admin/suppliers/SupplierForm.vue')
            },
            {
                path: 'products/:uuid/edit',
                name: 'ProductEdit',
                component: () => import('pages/admin/products/ProductEdit.vue')
            },
        ]
    },
    {
        path: '/:catchAll(.*)*',
        component: () => import('pages/ErrorNotFound.vue')
    }
];

export default routes;