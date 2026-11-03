import { createCrudStore } from './createCrudStore'
import { usersApi } from '@/services/api'

export const useUsersStore = createCrudStore('users', usersApi)
