<script setup>
import Sidebar from './Sidebar.vue'
import Header from './Header.vue'
</script>

<template>
  <div class="layout">
    <Sidebar />
    <main class="layout__content">
      <Header />
      <slot />
    </main>
  </div>
</template>

<style scoped>
.layout__content { padding: 16px 16px 80px; max-width: 1100px; margin: 0 auto; }

@media (min-width: 768px) {
  .layout__content { margin-left: 220px; padding: 24px 32px; max-width: none; }
}
</style>
