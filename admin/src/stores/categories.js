import { createCrudStore } from './createCrudStore'
import { categoriesApi } from '@/services/api'

export const useCategoriesStore = createCrudStore('categories', categoriesApi)
