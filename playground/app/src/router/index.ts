import { createRouter, createWebHistory } from 'vue-router'
import { i18n } from '@/i18n'
import { sectionPattern } from '@/entities/registry'
import { useAuthStore } from '@/stores/auth'
import { useLocalesStore } from '@/stores/locales'

// Router público con PREFIJO DE LOCALE (doc 04, DC-03): /es/…, /eu/…, /en/….
// El prefijo es la única fuente del idioma activo (se sincroniza al store en
// el guard); el contenido llega ya localizado de la API. Los segmentos de
// los listados de entidades y los slugs son traducibles: cada vista redirige
// a la canónica del idioma activo (DC-12).
const LOCALES = ['es', 'eu', 'en'] // debe casar con motor.locales de la API
const LOCALE_PATTERN = LOCALES.join('|')

/** Locale a usar cuando la URL no trae prefijo (persistido por el store). */
function storedLocale(): string {
  const saved = localStorage.getItem('bgm_app_locale')
  return saved && LOCALES.includes(saved) ? saved : 'es'
}

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // Captura a PNG (doc 01): sin navegación ni layout, solo el componente.
    {
      path: '/_render/:entity/:id',
      name: 'render',
      component: () => import('@/views/RenderView.vue'),
      meta: { bare: true },
    },
    {
      path: `/:locale(${LOCALE_PATTERN})`,
      children: [
        { path: '', name: 'home', component: () => import('@/views/HomeView.vue') },
        {
          path: 'login',
          name: 'login',
          component: () => import('@/views/LoginView.vue'),
          meta: { guest: true },
        },
        {
          path: 'registro',
          name: 'register',
          component: () => import('@/views/RegisterView.vue'),
          meta: { guest: true },
        },
        {
          path: 'cuenta',
          name: 'account',
          component: () => import('@/views/account/AccountView.vue'),
          meta: { auth: true },
        },
        {
          path: 'cuenta/seguridad',
          name: 'security',
          component: () => import('@/views/account/SecurityView.vue'),
          meta: { auth: true },
        },
        // Listados de entidades del juego (doc 10): el segmento (en cualquier
        // locale) decide la sección; van ANTES que la página por slug.
        {
          path: `:section(${sectionPattern()})`,
          name: 'entity-index',
          component: () => import('@/views/EntityIndexView.vue'),
        },
        {
          path: `:section(${sectionPattern()})/:slug([a-z0-9-]+)`,
          name: 'entity-detail',
          component: () => import('@/views/EntityDetailView.vue'),
        },
        // Páginas del CRM (doc 03): slug traducible en un único segmento.
        {
          path: ':slug([a-z0-9-]+)',
          name: 'page',
          component: () => import('@/views/PageView.vue'),
        },
        // Desconocidas DENTRO del locale: a la home de ese locale.
        {
          path: ':pathMatch(.*)*',
          redirect: (to) => ({ name: 'home', params: { locale: to.params.locale } }),
        },
      ],
    },
    // URLs sin prefijo (la raíz y enlaces antiguos): se les antepone el
    // locale persistido (/reglamento -> /es/reglamento).
    {
      path: '/:pathMatch(.*)*',
      name: 'unprefixed',
      redirect: (to) => `/${storedLocale()}${to.fullPath === '/' ? '' : to.fullPath}`,
    },
  ],
})

router.beforeEach(async (to) => {
  // El prefijo manda: sincroniza el store (persiste + ?locale de la API) y
  // los textos de la propia interfaz.
  const locales = useLocalesStore()
  const locale = String(to.params.locale ?? '')
  if (locale && LOCALES.includes(locale)) {
    if (locales.current !== locale) locales.setCurrent(locale)
    i18n.global.locale.value = locale as typeof i18n.global.locale.value
  }

  const auth = useAuthStore()
  if (auth.token && !auth.user) {
    try {
      await auth.fetchMe()
    } catch {
      // token inválido: el interceptor ya lo limpió
    }
  }
  if (to.meta.auth && !auth.isAuthenticated) {
    return { name: 'login', params: { locale: locale || storedLocale() } }
  }
  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'account', params: { locale: locale || storedLocale() } }
  }
})

export default router
