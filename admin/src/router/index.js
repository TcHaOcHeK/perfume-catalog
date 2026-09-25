import { createRouter, createWebHistory } from 'vue-router'

import Login from '@/views/Login.vue'
import Dashboard from '@/views/Dashboard.vue'
import ProductsList from '@/views/ProductsList.vue'
import ProductForm from '@/views/ProductForm.vue'
import BrandsList from '@/views/BrandsList.vue'
import CategoriesList from '@/views/CategoriesList.vue'
import UsersList from '@/views/UsersList.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),

  routes: [
    {
      path: '/login',
      name: 'login',
      component: Login
    },
    {
      path: 'admin/dashboard',
      name: 'dashboard',
      component: Dashboard,
      meta: {
        requiresAuth: true
      }
    },
    {
      path: 'admin/products',
      name: 'products',
      component: ProductsList,
      meta: {
        requiresAuth: true
      }
    },
    {
      path: 'admin/products/create',
      name: 'product-create',
      component: ProductForm,
      meta: {
        requiresAuth: true
      }
    },
    {
      path: 'admin/products/:id/edit',
      name: 'product-edit',
      component: ProductForm,
      meta: {
        requiresAuth: true
      }
    },
    {
      path: 'admin/brands',
      name: 'brands',
      component: BrandsList,
      meta: {
        requiresAuth: true
      }
    },
    {
      path: 'admin/categories',
      name: 'categories',
      component: CategoriesList,
      meta: {
        requiresAuth: true
      }
    },
    {
      path: 'admin/',
      name: 'dashboard',
      component:Dashboard,
      meta: {
        requiresAuth: true
      }
    }

  ],
})

// Проверка авторизации перед переходом
router.beforeEach((to) => {

  const isAuthenticated =
    Boolean(localStorage.getItem('accessToken'))

  // Пользователь пытается открыть защищённую страницу
  // без авторизации
  if (to.meta.requiresAuth && !isAuthenticated) {
    return {
      name: 'login',
      query: {
        redirect: to.fullPath
      }
    }
  }

  // Авторизованный пользователь пытается
  // снова открыть страницу входа
  if (to.name === 'login' && isAuthenticated) {
    return {
      name: 'dashboard'
    }
  }
})


export default router
