// Componentes de render de los bloques de PRESENTACIÓN del motor (doc 03).
// La app del juego los mezcla con los suyos en su blockRegistry.
import type { Component } from 'vue'
import BlockCta from './BlockCta.vue'
import BlockFaq from './BlockFaq.vue'
import BlockHeader from './BlockHeader.vue'
import BlockIndex from './BlockIndex.vue'
import BlockQuote from './BlockQuote.vue'
import BlockRelated from './BlockRelated.vue'
import BlockTabs from './BlockTabs.vue'
import BlockText from './BlockText.vue'
import BlockTextCard from './BlockTextCard.vue'

export {
  BlockCta,
  BlockFaq,
  BlockHeader,
  BlockIndex,
  BlockQuote,
  BlockRelated,
  BlockTabs,
  BlockText,
  BlockTextCard,
}
export { default as BlockShell } from './BlockShell.vue'
export { default as PageBackground } from './PageBackground.vue'
// Flujo de bloques de una página con CONTENEDORES (pestañas): la app pinta
// sus bloques con BlockFlow en vez de recorrer la lista plana.
export { default as BlockFlow } from './BlockFlow.vue'
export {
  CONTAINER_COMPONENTS,
  containerFlow,
  groupByDirectChild,
  type FlowEntry,
  type PageBlock,
} from './blockTree'
// Hueco sobre las pestañas al que un índice anidado sube su barra de búsqueda.
export { blockTabsToolbarKey } from './tabsToolbar'
// Mapa de rutas del catálogo (BlockRelated): la app lo provee por inject.
export {
  catalogRoutesKey,
  type CatalogItem,
  type CatalogRouteEntry,
  type CatalogRoutes,
} from './catalogRoutes'

/** Clave del BlockType => componente. */
export const motorBlockComponents: Record<string, Component> = {
  header: BlockHeader,
  text: BlockText,
  'text-card': BlockTextCard,
  quote: BlockQuote,
  index: BlockIndex,
  cta: BlockCta,
  faq: BlockFaq,
  related: BlockRelated,
  tabs: BlockTabs,
}
