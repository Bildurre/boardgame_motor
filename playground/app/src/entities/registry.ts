import type { Component } from 'vue'
import CharacterItem from './CharacterItem.vue'
import CharacterDetail from './CharacterDetail.vue'
import HouseItem from './HouseItem.vue'
import HouseDetail from './HouseDetail.vue'

// Listados públicos de entidades de ESTE juego (doc 10): patrón genérico
// "índice + detalle por slug". Cada sección declara su endpoint público, su
// segmento de URL por locale (debe casar con el sitemap del backend), la
// clave i18n del título y los componentes de tarjeta y detalle (reciben
// { item, locale }). Las vistas EntityIndexView/EntityDetailView hacen el
// resto: fetch, canónica por locale (DC-12) y SEO (useHead).
export interface EntitySection {
  key: string
  endpoint: string
  paths: Record<string, string>
  titleKey: string
  item: Component
  detail: Component
  /** Clave del PreviewRegistry si la entidad puede añadirse a la colección
   *  "para imprimir" (botón ＋ en el índice y el detalle). */
  collectible?: string
}

export const entitySections: EntitySection[] = [
  {
    key: 'characters',
    endpoint: '/characters',
    paths: { es: 'personajes', eu: 'pertsonaiak', en: 'characters' },
    titleKey: 'entities.characters',
    item: CharacterItem,
    detail: CharacterDetail,
    collectible: 'character',
  },
  {
    key: 'houses',
    endpoint: '/houses',
    paths: { es: 'casas', eu: 'etxeak', en: 'houses' },
    titleKey: 'entities.houses',
    item: HouseItem,
    detail: HouseDetail,
    collectible: 'house', // el token de 40 mm
  },
]

/** Todos los segmentos de URL (para el patrón de la ruta). */
export function sectionPattern(): string {
  return [...new Set(entitySections.flatMap((s) => Object.values(s.paths)))].join('|')
}

/** La sección a la que pertenece un segmento de URL (en cualquier locale). */
export function sectionFor(segment: string): EntitySection | null {
  return entitySections.find((s) => Object.values(s.paths).includes(segment)) ?? null
}
