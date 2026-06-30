import type { RouteRecordRaw } from 'vue-router'
import es from '@/i18n/locales/es.json'
import eu from '@/i18n/locales/eu.json'
import en from '@/i18n/locales/en.json'

// Rutas con segmentos de path traducidos (patrón kontuan): la ruta del locale
// actual es el `path`; las de los demás locales se añaden como `alias` para que
// una URL en cualquier idioma resuelva al mismo nombre de ruta.
type RoutePaths = typeof es.routes

const translations: Record<string, RoutePaths> = { es: es.routes, eu: eu.routes, en: en.routes }

export const supportedLocales = Object.keys(translations)

function paths(locale: string): RoutePaths {
  return translations[locale] ?? es.routes
}

function buildAliases(build: (p: RoutePaths) => string, currentLocale: string): string[] {
  return supportedLocales
    .filter((l) => l !== currentLocale)
    .map((l) => build(translations[l] ?? es.routes))
}

export function createLocalizedRoutes(locale: string): RouteRecordRaw[] {
  const p = paths(locale)

  return [
    {
      path: `/${p.login}`,
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guest: true },
      alias: buildAliases((t) => `/${t.login}`, locale),
    },
    {
      path: '/',
      name: 'dashboard',
      component: () => import('@/views/DashboardView.vue'),
      meta: { admin: true, titleKey: 'dashboard.title' },
    },
    {
      path: `/${p.houses}`,
      name: 'houses',
      component: () => import('@/views/houses/HousesListView.vue'),
      alias: buildAliases((t) => `/${t.houses}`, locale),
      meta: { admin: true, titleKey: 'houses.title', breadcrumbs: [{ key: 'houses' }] },
    },
    // Las altas/ediciones de House son modales (patrón kontuan), no rutas.
  ]
}
