<script setup>
import { RouterLink } from 'vue-router'

const items = [
  { to: '/', label: 'Dashboard' },
  { to: '/products', label: 'Products' },
  { to: '/categories', label: 'Categories' },
  { to: '/users', label: 'Users' },
  { to: '/brands', label: 'Brands' }
]
</script>

<template>
  <nav class="sidebar">
    <RouterLink
      v-for="item in items"
      :key="item.to"
      :to="item.to"
      class="sidebar__link"
      active-class="sidebar__link--active"
      :exact-active-class="item.to === '/' ? 'sidebar__link--active' : ''"
    >
      {{ item.label }}
    </RouterLink>
  </nav>
</template>

<style scoped>
/* mobile first: нижняя панель, как в прототипе */
.sidebar {
  position: fixed;
  inset: auto 0 0 0;
  display: flex;
  justify-content: space-around;
  padding: 8px;
  background: var(--surface);
  border-top: 1px solid var(--surface-2);
  z-index: 10;
}
.sidebar__link { padding: 8px 10px; border-radius: 999px; font-size: 13px; }
.sidebar__link--active { background: var(--accent); color: #fff; }

@media (min-width: 768px) {
  .sidebar {
    inset: 0 auto 0 0;
    width: 220px;
    flex-direction: column;
    justify-content: flex-start;
    gap: 4px;
    padding: 24px 16px;
    border-top: 0;
    border-right: 1px solid var(--surface-2);
  }
  .sidebar__link { font-size: 15px; padding: 10px 16px; }
}
</style>
