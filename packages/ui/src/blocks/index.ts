// Componentes de render de los bloques de PRESENTACIÓN del motor (doc 03).
// La app del juego los mezcla con los suyos en su blockRegistry.
import type { Component } from 'vue'
import BlockCta from './BlockCta.vue'
import BlockFaq from './BlockFaq.vue'
import BlockHeader from './BlockHeader.vue'
import BlockIndex from './BlockIndex.vue'
import BlockQuote from './BlockQuote.vue'
import BlockRelated from './BlockRelated.vue'
import BlockText from './BlockText.vue'
import BlockTextCard from './BlockTextCard.vue'

export {
  BlockCta,
  BlockFaq,
  BlockHeader,
  BlockIndex,
  BlockQuote,
  BlockRelated,
  BlockText,
  BlockTextCard,
}
export { default as BlockShell } from './BlockShell.vue'
export { default as PageBackground } from './PageBackground.vue'
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
}
