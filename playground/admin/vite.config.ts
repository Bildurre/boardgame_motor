import { fileURLToPath, URL } from 'node:url'
import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'

// Panel de administración del playground (puerto 5174). PWA instalable (DC-01).
// La URL de la API vive en .env (VITE_API_URL), patrón kontuan. Sin proxy
// para la API; SÍ para /storage en desarrollo: el contenido guarda las
// imágenes e iconos con rutas relativas a la raíz (/storage/...), que en
// producción resuelven solas (misma web que la API) y aquí las reenvía el
// servidor de Vite al origen de la API.
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const apiOrigin = new URL(env.VITE_API_URL || 'http://localhost:8010/api').origin

  return {
    plugins: [
      vue(),
      VitePWA({
        registerType: 'autoUpdate',
        manifest: {
          // Identidad estable, idioma único declarado (el manifest no tiene
          // i18n) y perímetro explícito de la PWA. Cada juego añade encima lo
          // suyo: shortcuts a sus herramientas, categories, screenshots…
          id: '/',
          lang: 'es',
          dir: 'ltr',
          name: 'EdC Playground Admin',
          short_name: 'EdC Admin',
          description: 'Panel de administración de prueba del EdC Motor',
          theme_color: '#6c5ce7',
          background_color: '#0f1115',
          display: 'standalone',
          scope: '/',
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
    server: {
      port: 5174,
      proxy: { '/storage': { target: apiOrigin, changeOrigin: true } },
    },
    css: {
      preprocessorOptions: {
        scss: {
          additionalData: '@use "tokens" as *;\n',
          loadPaths: [
            fileURLToPath(new URL('../../packages/ui/scss', import.meta.url)),
            fileURLToPath(new URL('../../packages/admin-kit/scss', import.meta.url)),
            fileURLToPath(new URL('../packages/shared/scss', import.meta.url)),
            fileURLToPath(new URL('./src/assets/scss', import.meta.url)),
          ],
        },
      },
    },
  }
})
