<script setup>
// Таблица/список товаров — реализуется в ПР10
defineProps({ items: { type: Array, default: () => [] } })
</script>

<template>
  <ul class="list">
    <li v-for="p in items" :key="p.id" class="card">{{ p.title }}</li>
  </ul>
</template>

<style scoped>
.list { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
</style>
