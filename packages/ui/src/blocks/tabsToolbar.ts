import type { InjectionKey, Ref } from 'vue'

/**
 * Hueco POR ENCIMA de la barra de pestañas del bloque `tabs` (BlockTabs lo
 * provee): un bloque anidado con barra de búsqueda (un índice de entidad)
 * la teletransporta ahí, como en el admin (FilterBar sobre las tabs), en
 * vez de pintarla dentro de su pestaña. Solo la pestaña activa está
 * montada, así que nunca hay dos barras a la vez. `null` fuera de un
 * bloque de pestañas: el índice pinta su barra donde siempre.
 */
export const blockTabsToolbarKey: InjectionKey<Ref<HTMLElement | null>> =
  Symbol('block-tabs-toolbar')
