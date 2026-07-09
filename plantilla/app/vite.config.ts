import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'

// Web pública del juego (puerto 5173). PWA instalable (DC-01).
// La URL de la API vive en .env (VITE_API_URL), patrón kontuan; sin proxy.
export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      manifest: {
        name: 'Plantilla EdC',
        short_name: 'Plantilla',
        description: 'Web pública del juego',
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
  // Los paquetes del motor son "fuente" (.ts/.vue sin compilar). Consumidos
  // desde node_modules, Vite los pre-empaqueta pero externaliza sus .vue, que
  // importan su propia copia de los composables: los singletons (toast,
  // confirm) se duplican y la UI deja de reaccionar. Se excluye para servirlo
  // como fuente (en el monorepo, al ir enlazado, no se pre-empaqueta y el
  // exclude es inocuo).
  optimizeDeps: {
    exclude: ['@edc-motor/ui'],
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
