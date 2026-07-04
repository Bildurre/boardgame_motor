<script setup lang="ts">
import { CharacterCard, type Character } from '@playground/shared'

// Tarjeta del listado público de personajes: la propia carta del juego
// (fuente única, D8), envuelta para encajar en la rejilla genérica.
defineProps<{ item: Character; locale: string }>()
</script>

<template>
  <CharacterCard :item="item" :locale="locale" />
</template>
