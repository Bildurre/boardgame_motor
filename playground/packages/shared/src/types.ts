// Tipos de las entidades del juego tal y como las sirven los Resources de la
// API de admin (traducciones completas por locale, para editar).

export type Translations = Record<string, string>

export interface EntityBase {
  id: number
  slug: Translations
  image: string | null
  is_published: boolean
  deleted_at: string | null
}

export interface Scheme extends EntityBase {
  title: Translations
  description: Translations
  cost: number
  house_id?: number
  house?: { id: number; name: Translations } | null
}

export interface House extends EntityBase {
  name: Translations
  description: Translations
  color: string | null
  schemes?: Scheme[]
}

export interface Character extends EntityBase {
  name: Translations
  description: Translations
  ability: Translations
  cost: number
  power: number
  prestige: number
  intrigue: number
  money: number
  defense: number
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}
