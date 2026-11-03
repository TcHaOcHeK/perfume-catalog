import { createCrudStore } from './createCrudStore'
import { brandsApi } from '@/services/api'

export const useBrandsStore = createCrudStore('brands', brandsApi)
