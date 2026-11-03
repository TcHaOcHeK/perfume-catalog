import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import Login from '@/views/Login.vue'
import Dashboard from '@/views/Dashboard.vue'
import ProductsList from '@/views/ProductsList.vue'
import ProductForm from '@/views/ProductForm.vue'
import BrandsList from '@/views/BrandsList.vue'
import CategoriesList from '@/views/CategoriesList.vue'
import UsersList from '@/views/UsersList.vue'

const protectedMeta = (title) => ({ requiresAuth: true, title })

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/login', name: 'login', component: Login },

    { path: '/', name: 'dashboard', component: Dashboard, meta: protectedMeta('Dashboard') },
    { path: '/products', name: 'products', component: ProductsList, meta: protectedMeta('Products') },
    { path: '/products/create', name: 'product-create', component: ProductForm, meta: protectedMeta('New product') },
    { path: '/products/:id/edit', name: 'product-edit', component: ProductForm, meta: protectedMeta('Edit product') },
    { path: '/categories', name: 'categories', component: CategoriesList, meta: protectedMeta('Categories') },
    { path: '/brands', name: 'brands', component: BrandsList, meta: protectedMeta('Brands') },
    { path: '/users', name: 'users', component: UsersList, meta: protectedMeta('Users') },

    { path: '/:pathMatch(.*)*', redirect: { name: 'dashboard' } }
  ]
})

// Защита маршрутов: проверка наличия токена
router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  if (to.name === 'login' && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }
})

export default router
