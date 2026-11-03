<script setup>
import { onMounted } from 'vue'
import AdminLayout from '@/components/AdminLayout.vue'
import { useUsersStore } from '@/stores/users'

const store = useUsersStore()
onMounted(() => store.fetchAll())
</script>

<template>
  <AdminLayout>
    <p v-if="store.loading" class="state">Загрузка…</p>
    <p v-else-if="store.error" class="state state--error">{{ store.error }}</p>
    <p v-else-if="!store.items.length" class="state">Пока пусто</p>
    <ul v-else class="list">
      <li v-for="item in store.items" :key="item.id" class="card">{{ item.title ?? item.name ?? item.login }}</li>
    </ul>
  </AdminLayout>
</template>

<style scoped>
.list { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
</style>
