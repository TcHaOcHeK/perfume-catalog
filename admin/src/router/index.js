import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { LOGIN_URL } from '@/services/api'

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

// Куда вести неавторизованного: во внешнюю страницу входа (prod) или на Vue-логин (dev)
function toLogin(redirect) {
  if (LOGIN_URL) {
    window.location.href = LOGIN_URL
    return false
  }
  return { name: 'login', query: { redirect } }
}

// Защита маршрутов: наличие токена + роль admin/editor
router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth) {
    if (!auth.isAuthenticated) return toLogin(to.fullPath)

    // Страницу перезагрузили: токен есть, профиля нет -> подтягиваем (и проверяем токен)
    if (!auth.user) {
      try {
        await auth.fetchMe()
      } catch {
        auth.clearSession()
        return toLogin(to.fullPath)
      }
    }

    if (!auth.isStaff) {
      auth.clearSession()
      return toLogin(to.fullPath)
    }
  }

  if (to.name === 'login') {
    if (auth.isAuthenticated) return { name: 'dashboard' }
    if (LOGIN_URL) {
      window.location.href = LOGIN_URL
      return false
    }
  }
})

export default router
