// Componentes de render de los bloques de PRESENTACIÓN del motor (doc 03).
// La app del juego los mezcla con los suyos en su blockRegistry.
import type { Component } from 'vue'
import BlockCta from './BlockCta.vue'
import BlockHeader from './BlockHeader.vue'
import BlockQuote from './BlockQuote.vue'
import BlockText from './BlockText.vue'
import BlockTextCard from './BlockTextCard.vue'

export { BlockCta, BlockHeader, BlockQuote, BlockText, BlockTextCard }
export { default as BlockShell } from './BlockShell.vue'

/** Clave del BlockType => componente (los cinco de presentación). */
export const motorBlockComponents: Record<string, Component> = {
  header: BlockHeader,
  text: BlockText,
  'text-card': BlockTextCard,
  quote: BlockQuote,
  cta: BlockCta,
}
