import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { watchSplash } from '@edc-motor/ui'
import './assets/scss/main.scss'
import App from './App.vue'
import router from './router'
import i18n from './i18n'
import { api } from '@/lib/api'

// El splash del index.html se retira solo en el primer reposo de red tras
// el arranque (todas las peticiones pasan por `api`): registrar ANTES de
// montar, o las peticiones del onMounted saldrían sin contar.
watchSplash({ api })

createApp(App).use(createPinia()).use(i18n).use(router).mount('#app')
