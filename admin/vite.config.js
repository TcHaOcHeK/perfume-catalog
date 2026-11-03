import { fileURLToPath, URL } from 'node:url'
import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const target = env.VITE_PROXY_TARGET || 'http://localhost:8080'

  return {
    // В production админка лежит в /perfume-catalog/admin/
    base: mode === 'production' ? '/perfume-catalog/admin/' : '/',

    plugins: [vue()],

    resolve: {
      alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) }
    },

    server: {
      port: 5173,
      proxy: {
        // REST API: /api/* -> /perfume-catalog/api/*
        '/api': {
          target,
          changeOrigin: true,
          rewrite: (path) => path.replace(/^\/api/, '/perfume-catalog/api')
        },
        // Картинки товаров и прочая статика публичного сайта
        '/perfume-catalog': {
          target,
          changeOrigin: true
        }
      }
    }
  }
})
