import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { catalogRoutesKey, setupNavigationSplash, watchSplash } from '@edc-motor/ui'
import './assets/scss/main.scss'
import App from './App.vue'
import router from './router'
import { i18n } from '@/i18n'
import { catalogRoutes } from '@/entities/catalogRoutes'
import { useSiteStore } from '@/stores/site'
import { api } from '@/lib/api'

const app = createApp(App).use(createPinia()).use(router).use(i18n)

// Enlaces del bloque `related`: mapa clave de preview => rutas del juego
// (ver src/entities/catalogRoutes.ts; vacío mientras no haya secciones).
app.provide(catalogRoutesKey, catalogRoutes)

// Modo "acento aleatorio" (configuración de la web): la SPA no recarga al
// navegar, así que el sorteo del color se repite en cada cambio de ruta.
router.afterEach(() => {
  useSiteStore().onNavigate()
})

// El splash del index.html se retira solo en el primer reposo de red tras
// el arranque (todas las peticiones pasan por `api`): registrar ANTES de
// montar, o las peticiones del onMounted saldrían sin contar.
watchSplash({ api })
// …y también es el estado de carga al NAVEGAR: si la vista nueva tarda en
// tener sus datos, el velo vuelve (nunca skeletons); una navegación rápida
// no lo ve ni un frame.
setupNavigationSplash(router)

app.mount('#app')
