const routes = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        component: () => import('pages/IndexPage.vue')
      }
    ]
  },

  {
    path: '/login',
    component: () => import('layouts/AuthLayout.vue'),
    meta: { guest: true },
    children: [
      {
        path: '',
        component: () => import('pages/auth/LoginPage.vue')
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
        component: () => import('pages/admin/DashboardPage.vue')
      },
      {
        path: 'categories',
        children: [
          { path: '', component: () => import('pages/admin/categories/CategoryList.vue') },
          { path: 'create', component: () => import('pages/admin/categories/CategoryForm.vue') },
          { path: ':uuid/edit', component: () => import('pages/admin/categories/CategoryForm.vue') },
        ]
      }
    ]
  },

  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue')
  }
]

export default routes
