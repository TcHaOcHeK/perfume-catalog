<script setup>
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <header class="header">
    <h1 class="header__title">{{ route.meta.title }}</h1>
    <button class="btn btn--ghost" @click="logout">Выйти</button>
  </header>
</template>

<style scoped>
.header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.header__title { font-size: 20px; }
</style>
