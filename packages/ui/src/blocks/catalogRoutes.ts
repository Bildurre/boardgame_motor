import type { InjectionKey } from 'vue'
import type { RouteLocationRaw } from 'vue-router'

// Resolución de enlaces del catálogo público: el paquete ui no conoce las
// rutas del juego, así que la app PROVEE este mapa (clave del PreviewRegistry
// => fábricas de ruta) con `app.provide(catalogRoutesKey, mapa)`. BlockRelated
// lo inyecta; sin mapa (o sin entrada) los ítems se pintan sin enlace.

/** Ítem del catálogo público del motor (`GET /api/catalog/{key}`). */
export interface CatalogItem {
  id: number
  name: string
  slug: string | null
  preview: string | null
}

export interface CatalogRouteEntry {
  /** Ruta del índice de la entidad (botón "ver todos"). */
  index?: (locale: string) => RouteLocationRaw
  /** Ruta del detalle de un ítem; null = ese ítem no enlaza. */
  single?: (item: CatalogItem, locale: string) => RouteLocationRaw | null
}

export type CatalogRoutes = Record<string, CatalogRouteEntry>

export const catalogRoutesKey: InjectionKey<CatalogRoutes> = Symbol('catalogRoutes')
