import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'

// Panel de administración del playground (puerto 5174). PWA instalable (DC-01).
// La URL de la API vive en .env (VITE_API_URL), patrón kontuan; sin proxy.
export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      manifest: {
        name: 'BGM Playground Admin',
        short_name: 'BGM Admin',
        description: 'Panel de administración de prueba del Boardgame Motor',
        theme_color: '#6c5ce7',
        background_color: '#0f1115',
        display: 'standalone',
        start_url: '/',
        icons: [],
      },
    }),
  ],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  server: { port: 5174 },
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: '@use "tokens" as *;\n',
        loadPaths: [
          fileURLToPath(new URL('../../packages/ui/scss', import.meta.url)),
          fileURLToPath(new URL('../../packages/admin-kit/scss', import.meta.url)),
          fileURLToPath(new URL('./src/assets/scss', import.meta.url)),
        ],
      },
    },
  },
})
