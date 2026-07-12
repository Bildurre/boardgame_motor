import type { CatalogItem, CatalogRoutes } from '@edc-motor/ui'
import { entitySections } from './registry'

// Mapa de rutas del catálogo público (clave del PreviewRegistry => fábricas
// de ruta) para los enlaces del bloque `related`. Se provee en main.ts con
// `app.provide(catalogRoutesKey, catalogRoutes)`. Las claves sin sección
// pública (p. ej. `scheme` o `house-counter`) simplemente no se mapean:
// sus ítems se pintan sin enlace.
function sectionRoutes(sectionKey: string): CatalogRoutes[string] {
  const section = entitySections.find((s) => s.key === sectionKey)
  if (!section) return {}
  return {
    index: (locale: string) => ({
      name: 'entity-index',
      params: { locale, section: section.paths[locale] ?? Object.values(section.paths)[0] },
    }),
    single: (item: CatalogItem, locale: string) =>
      item.slug
        ? {
            name: 'entity-detail',
            params: {
              locale,
              section: section.paths[locale] ?? Object.values(section.paths)[0],
              slug: item.slug,
            },
          }
        : null,
  }
}

export const catalogRoutes: CatalogRoutes = {
  character: sectionRoutes('characters'),
  house: sectionRoutes('houses'), // el token de 40 mm tiene single propio
}
