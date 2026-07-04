import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './assets/scss/main.scss'
import App from './App.vue'
import router from './router'
import { useSiteStore } from '@/stores/site'

const app = createApp(App).use(createPinia()).use(router)

// Modo "acento aleatorio" (configuración de la web): la SPA no recarga al
// navegar, así que el sorteo del color se repite en cada cambio de ruta.
router.afterEach(() => {
  useSiteStore().onNavigate()
})

app.mount('#app')
