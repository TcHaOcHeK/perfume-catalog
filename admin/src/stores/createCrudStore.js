import { defineStore } from 'pinia'
import { ref } from 'vue'

// Общий шаблон стора для списка сущностей (товары, бренды, категории, пользователи).
// Ожидаемый ответ списка: массив или { items: [...] } / { <id>: [...] }.
export function createCrudStore(id, resource) {
  return defineStore(id, () => {
    const items = ref([])
    const loading = ref(false)
    const error = ref('')

    async function fetchAll(params) {
      loading.value = true
      error.value = ''
      try {
        const data = await resource.list(params)
        items.value = Array.isArray(data) ? data : (data.items ?? data[id] ?? [])
      } catch (e) {
        error.value = e.response?.data?.error || 'Не удалось загрузить данные'
      } finally {
        loading.value = false
      }
    }

    async function remove(itemId) {
      await resource.remove(itemId)
      items.value = items.value.filter((item) => item.id !== itemId)
    }

    return { items, loading, error, fetchAll, remove }
  })
}
