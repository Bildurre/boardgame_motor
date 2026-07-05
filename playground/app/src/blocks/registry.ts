import type { Component } from 'vue'
import { motorBlockComponents } from '@bgm/ui'
import CharactersGridBlock from './CharactersGridBlock.vue'
import FeaturedHouseBlock from './FeaturedHouseBlock.vue'
import HousesSchemesBlock from './HousesSchemesBlock.vue'

// Registro de componentes de bloque de la web pública (doc 03): los de
// presentación vienen del motor; aquí se añaden los de ESTE juego. La clave
// casa con BlockType::$key del backend.
export const blockRegistry: Record<string, Component> = {
  ...motorBlockComponents,
  'characters-grid': CharactersGridBlock,
  'houses-schemes': HousesSchemesBlock,
  'featured-house': FeaturedHouseBlock,
}
