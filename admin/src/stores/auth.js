import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { authApi } from '@/services/api'

const TOKEN_KEY = 'accessToken'
const STAFF_ROLES = ['admin', 'editor'] // роли, которым разрешён вход в админ-панель

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem(TOKEN_KEY) || '')
  const user = ref(null)

  const isAuthenticated = computed(() => Boolean(token.value))
  const isStaff = computed(() => STAFF_ROLES.includes(user.value?.role))
  const isAdmin = computed(() => user.value?.role === 'admin')

  // POST /api/login {login, password} -> { token, user }
  async function login(loginValue, password) {
    const data = await authApi.login(loginValue, password)
    if (!STAFF_ROLES.includes(data.user?.role)) {
      throw new Error('Нет доступа к админ-панели')
    }
    token.value = data.token
    user.value = data.user
    localStorage.setItem(TOKEN_KEY, data.token)
  }

  // После перезагрузки страницы токен остаётся в localStorage, а user — нет:
  // запрашиваем профиль (заодно проверяем, что токен ещё действителен)
  async function fetchMe() {
    const data = await authApi.me()
    user.value = data.user
  }

  function clearSession() {
    token.value = ''
    user.value = null
    localStorage.removeItem(TOKEN_KEY)
  }

  async function logout() {
    try {
      await authApi.logout()
    } catch {
      // токен всё равно сбрасываем локально
    }
    clearSession()
  }

  return { token, user, isAuthenticated, isStaff, isAdmin, login, fetchMe, clearSession, logout }
})
