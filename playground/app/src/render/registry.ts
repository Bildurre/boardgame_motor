import type { Component } from 'vue'
import { CharacterCard, HouseToken, SchemeCard } from '@playground/shared'

// Registro de componentes visuales por entidad renderizable (doc 01): el
// segmento de /_render/:entity/:id. Debe casar con el PreviewRegistry del
// backend (AppServiceProvider de la api).
export const renderRegistry: Record<string, Component> = {
  character: CharacterCard,
  scheme: SchemeCard,
  house: HouseToken,
}
