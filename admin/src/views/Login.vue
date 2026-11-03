<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const login = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(login.value, password.value)
    router.push(route.query.redirect || { name: 'dashboard' })
  } catch (e) {
    error.value = e.response?.data?.error || 'Не удалось войти'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="login">
    <form class="card login__form" @submit.prevent="submit">
      <h1>Вход</h1>
      <input v-model.trim="login" class="input" placeholder="Login" autocomplete="username" required />
      <input v-model="password" class="input" type="password" placeholder="Password" autocomplete="current-password" required />
      <p v-if="error" class="state--error">{{ error }}</p>
      <button class="btn" :disabled="loading">{{ loading ? 'Входим…' : 'Войти' }}</button>
    </form>
  </main>
</template>

<style scoped>
.login { min-height: 100vh; display: grid; place-items: center; padding: 16px; }
.login__form { width: min(360px, 100%); display: grid; gap: 12px; }
</style>
