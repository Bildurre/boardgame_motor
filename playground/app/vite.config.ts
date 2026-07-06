import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'

// Web pública del playground (puerto 5173). PWA instalable (DC-01).
// La URL de la API vive en .env (VITE_API_URL), patrón kontuan; sin proxy.
export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      manifest: {
        name: 'EdC Playground',
        short_name: 'EdC',
        description: 'Web pública de prueba del EdC Motor',
        theme_color: '#6c5ce7',
        background_color: '#0f1115',
        display: 'standalone',
        start_url: '/',
        icons: [
          { src: '/pwa-192.png', sizes: '192x192', type: 'image/png' },
          { src: '/pwa-512.png', sizes: '512x512', type: 'image/png' },
          { src: '/pwa-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],
      },
    }),
  ],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  server: { port: 5173 },
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: '@use "tokens" as *;\n',
        loadPaths: [
          fileURLToPath(new URL('../../packages/ui/scss', import.meta.url)),
          fileURLToPath(new URL('../packages/shared/scss', import.meta.url)),
          fileURLToPath(new URL('./src/assets/scss', import.meta.url)),
        ],
      },
    },
  },
})
