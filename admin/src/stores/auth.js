import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { authApi } from '@/services/api'

const TOKEN_KEY = 'accessToken'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem(TOKEN_KEY) || '')
  const user = ref(null)

  const isAuthenticated = computed(() => Boolean(token.value))

  // Контракт для ПР8: POST /api/login {login, password} -> { token, user }
  async function login(loginValue, password) {
    const data = await authApi.login(loginValue, password)
    token.value = data.token
    user.value = data.user ?? null
    localStorage.setItem(TOKEN_KEY, data.token)
  }

  async function logout() {
    try {
      await authApi.logout()
    } catch {
      // токен всё равно сбрасываем локально
    }
    token.value = ''
    user.value = null
    localStorage.removeItem(TOKEN_KEY)
  }

  return { token, user, isAuthenticated, login, logout }
})
