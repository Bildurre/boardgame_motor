import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { createLocalizedRoutes } from './i18n-paths'
import i18n from '@/i18n'

function currentLocale(): string {
  return (i18n.global.locale as unknown as { value: string }).value || 'es'
}

const router = createRouter({
  history: createWebHistory(),
  routes: createLocalizedRoutes(currentLocale()),
})

/**
 * Al cambiar de idioma: reconstruye las rutas con los segmentos traducidos y
 * redirige a la misma ruta (por nombre) para que la URL pase al nuevo idioma.
 */
export function onLocaleChange(newLocale: string) {
  const from = router.currentRoute.value

  for (const route of router.getRoutes()) {
    if (route.name && router.hasRoute(route.name)) router.removeRoute(route.name)
  }
  for (const route of createLocalizedRoutes(newLocale)) router.addRoute(route)

  if (from.name) {
    router.replace({ name: from.name, params: from.params, query: from.query })
  }
}

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  if (auth.token && !auth.user) {
    try { await auth.fetchMe() } catch { /* token inválido */ }
  }
  if (to.meta.admin) {
    if (!auth.isAuthenticated) return { name: 'login' }
    if (!auth.canAccessAdmin) {
      await auth.logout()
      return { name: 'login', query: { denied: '1' } }
    }
  }
  if (to.meta.guest && auth.isAuthenticated && auth.canAccessAdmin) return { name: 'dashboard' }
})

export default router
