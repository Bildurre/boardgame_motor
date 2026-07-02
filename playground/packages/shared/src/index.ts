// @playground/shared — lo específico del juego compartido entre admin y app
// (patrón kontuan). Aquí viven los componentes visuales de las entidades
// (fuente única del render a PNG, D8) y sus tipos.

export { default as CharacterCard } from './components/CharacterCard.vue'
export { default as SchemeCard } from './components/SchemeCard.vue'
export * from './types'
