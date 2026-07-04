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
      meta: { admin: true, nav: 'dashboard', titleKey: 'dashboard.title' },
    },
    {
      path: `/${p.houses}`,
      name: 'houses',
      component: () => import('@/views/houses/HousesListView.vue'),
      alias: buildAliases((t) => `/${t.houses}`, locale),
      meta: {
        admin: true,
        nav: 'houses',
        titleKey: 'houses.title',
        breadcrumbs: [{ key: 'houses' }],
      },
    },
    // Las altas/ediciones son modales (patrón kontuan). El detalle sí es ruta.
    {
      path: `/${p.houses}/:slug`,
      name: 'house-single',
      component: () => import('@/views/houses/HouseSingleView.vue'),
      alias: buildAliases((t) => `/${t.houses}/:slug`, locale),
      meta: {
        admin: true,
        nav: 'houses',
        titleKey: 'houses.title',
        breadcrumbs: [{ key: 'houses', to: 'houses' }],
      },
    },
    {
      path: `/${p.schemes}`,
      name: 'schemes',
      component: () => import('@/views/schemes/SchemesListView.vue'),
      alias: buildAliases((t) => `/${t.schemes}`, locale),
      meta: {
        admin: true,
        nav: 'schemes',
        titleKey: 'schemes.title',
        breadcrumbs: [{ key: 'schemes' }],
      },
    },
    {
      path: `/${p.schemes}/:slug`,
      name: 'scheme-single',
      component: () => import('@/views/schemes/SchemeSingleView.vue'),
      alias: buildAliases((t) => `/${t.schemes}/:slug`, locale),
      meta: {
        admin: true,
        nav: 'schemes',
        titleKey: 'schemes.title',
        breadcrumbs: [{ key: 'schemes', to: 'schemes' }],
      },
    },
    {
      path: `/${p.characters}`,
      name: 'characters',
      component: () => import('@/views/characters/CharactersListView.vue'),
      alias: buildAliases((t) => `/${t.characters}`, locale),
      meta: {
        admin: true,
        nav: 'characters',
        titleKey: 'characters.title',
        breadcrumbs: [{ key: 'characters' }],
      },
    },
    {
      path: `/${p.characters}/:slug`,
      name: 'character-single',
      component: () => import('@/views/characters/CharacterSingleView.vue'),
      alias: buildAliases((t) => `/${t.characters}/:slug`, locale),
      meta: {
        admin: true,
        nav: 'characters',
        titleKey: 'characters.title',
        breadcrumbs: [{ key: 'characters', to: 'characters' }],
      },
    },
    {
      path: `/${p.icons}`,
      name: 'icons',
      component: () => import('@/views/icons/IconsListView.vue'),
      alias: buildAliases((t) => `/${t.icons}`, locale),
      meta: { admin: true, nav: 'icons', titleKey: 'icons.title', breadcrumbs: [{ key: 'icons' }] },
    },
    {
      path: `/${p.previews}`,
      name: 'previews',
      component: () => import('@/views/previews/PreviewsView.vue'),
      alias: buildAliases((t) => `/${t.previews}`, locale),
      meta: {
        admin: true,
        nav: 'previews',
        titleKey: 'previewsManager.title',
        breadcrumbs: [{ key: 'previews' }],
      },
    },
    {
      path: `/${p.pages}`,
      name: 'pages',
      component: () => import('@/views/pages/PagesListView.vue'),
      alias: buildAliases((t) => `/${t.pages}`, locale),
      meta: { admin: true, nav: 'pages', titleKey: 'pages.title', breadcrumbs: [{ key: 'pages' }] },
    },
    {
      path: `/${p.pages}/:id`,
      name: 'page',
      component: () => import('@/views/pages/PageSingleView.vue'),
      alias: buildAliases((t) => `/${t.pages}/:id`, locale),
      meta: {
        admin: true,
        nav: 'pages',
        titleKey: 'pages.title',
        breadcrumbs: [{ key: 'pages', to: 'pages' }],
      },
    },
    {
      path: `/${p.pdfs}`,
      name: 'pdfs',
      component: () => import('@/views/pdfs/PdfsView.vue'),
      alias: buildAliases((t) => `/${t.pdfs}`, locale),
      meta: {
        admin: true,
        nav: 'pdfs',
        titleKey: 'pdfs.viewTitle',
        breadcrumbs: [{ key: 'pdfs' }],
      },
    },
    {
      path: `/${p.settings}`,
      name: 'settings',
      component: () => import('@/views/settings/SettingsView.vue'),
      alias: buildAliases((t) => `/${t.settings}`, locale),
      meta: {
        admin: true,
        nav: 'settings',
        titleKey: 'settings.title',
        breadcrumbs: [{ key: 'settings' }],
      },
    },
    // URLs desconocidas: al dashboard (evita la página en blanco).
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      redirect: { name: 'dashboard' },
    },
  ]
}
