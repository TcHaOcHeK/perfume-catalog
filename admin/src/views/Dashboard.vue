<script setup>
import { onMounted, ref } from 'vue'
import AdminLayout from '@/components/AdminLayout.vue'
import { ping } from '@/services/api'

// Заглушка: счётчики появятся вместе с API аналитики
const stats = [
  { label: 'Products', value: '—' },
  { label: 'Categories', value: '—' },
  { label: 'Brands', value: '—' },
  { label: 'Users', value: '—' }
]

// Проверка связи фронтенда с PHP API через прокси
const apiStatus = ref('проверяем…')
onMounted(async () => {
  try {
    const data = await ping()
    apiStatus.value = data.message === 'pong' ? 'доступен' : 'неожиданный ответ'
  } catch {
    apiStatus.value = 'недоступен'
  }
})
</script>

<template>
  <AdminLayout>
    <div class="grid">
      <div v-for="s in stats" :key="s.label" class="card">
        <strong class="value">{{ s.value }}</strong>
        <p>{{ s.label }}</p>
      </div>
    </div>
    <p class="status">API: {{ apiStatus }}</p>
  </AdminLayout>
</template>

<style scoped>
.grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.value { font-size: 28px; }
.status { margin-top: 16px; color: var(--muted); }
@media (min-width: 768px) { .grid { grid-template-columns: repeat(4, 1fr); } }
</style>
