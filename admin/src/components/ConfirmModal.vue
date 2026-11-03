<script setup>
// Подтверждение удаления (ПР10): <ConfirmModal v-model="show" message="..." @confirm="remove" />
defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Подтверждение' },
  message: { type: String, default: 'Вы уверены?' }
})
const emit = defineEmits(['update:modelValue', 'confirm'])

function confirm() {
  emit('confirm')
  emit('update:modelValue', false)
}
</script>

<template>
  <div v-if="modelValue" class="overlay" @click.self="emit('update:modelValue', false)">
    <div class="modal card">
      <h2>{{ title }}</h2>
      <p class="modal__text">{{ message }}</p>
      <div class="modal__actions">
        <button class="btn btn--ghost" @click="emit('update:modelValue', false)">Отмена</button>
        <button class="btn btn--danger" @click="confirm">Удалить</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.overlay { position: fixed; inset: 0; display: grid; place-items: center; background: rgba(0, 0, 0, .4); z-index: 100; }
.modal { width: min(360px, 90vw); }
.modal__text { margin: 8px 0 16px; color: var(--muted); }
.modal__actions { display: flex; justify-content: flex-end; gap: 8px; }
</style>
