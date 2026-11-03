import { createCrudStore } from './createCrudStore'
import { productsApi } from '@/services/api'

export const useProductsStore = createCrudStore('products', productsApi)
