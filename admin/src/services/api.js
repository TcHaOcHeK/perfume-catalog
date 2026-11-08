import axios from 'axios'

const TOKEN_KEY = 'accessToken'
// Куда отправлять неавторизованного: в prod — публичная страница входа, в dev — пусто (используем Vue-логин)
export const LOGIN_URL = import.meta.env.VITE_LOGIN_URL || ''

const api = axios.create({
  // dev: /api (проксируется Vite), prod: /perfume-catalog/api
  baseURL: import.meta.env.VITE_API_URL || '/api',
  headers: { Accept: 'application/json' }
})

// Подставляем JWT в каждый запрос
api.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY)
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// 401 (токен истёк/неверный) -> сбрасываем сессию и уходим на логин
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const isLoginRequest = error.config?.url === '/login'
    if (error.response?.status === 401 && !isLoginRequest) {
      localStorage.removeItem(TOKEN_KEY)
      window.location.href = LOGIN_URL || `${import.meta.env.BASE_URL}login`
    }
    return Promise.reject(error)
  }
)

// Фабрика CRUD-ресурса: GET/POST/PUT/DELETE /api/<path>
export function createResource(path) {
  return {
    list: (params) => api.get(`/${path}`, { params }).then((r) => r.data),
    get: (id) => api.get(`/${path}/${id}`).then((r) => r.data),
    create: (payload) => api.post(`/${path}`, payload).then((r) => r.data),
    update: (id, payload) => api.put(`/${path}/${id}`, payload).then((r) => r.data),
    remove: (id) => api.delete(`/${path}/${id}`).then((r) => r.data)
  }
}

export const productsApi = createResource('products')
export const brandsApi = createResource('brands')
export const categoriesApi = createResource('categories')
export const usersApi = createResource('users')

export const authApi = {
  login: (login, password) => api.post('/login', { login, password }).then((r) => r.data),
  logout: () => api.post('/logout').then((r) => r.data),
  me: () => api.get('/me').then((r) => r.data)
}

export const ping = () => api.get('/ping').then((r) => r.data)

export default api
