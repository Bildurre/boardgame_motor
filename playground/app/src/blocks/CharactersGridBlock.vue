<script setup lang="ts">
import { computed } from 'vue'
import { BlockShell } from '@bgm/ui'
import { CharacterCard, type Character } from '@playground/shared'
import { useLocalesStore } from '@/stores/locales'

// Bloque con-datos de ESTE juego: rejilla de cartas de personaje (el backend
// resuelve los datos en CharactersGridBlock::resolveData; el payload trae la
// misma forma que consume CharacterCard).
const props = defineProps<{
  settings: Record<string, unknown>
  data: { characters?: Record<string, unknown>[] }
}>()

const locales = useLocalesStore()
const characters = computed(() => (props.data.characters ?? []) as unknown as Character[])
</script>

<template>
  <BlockShell :settings="settings" class="block--characters-grid">
    <h2 v-if="settings.title" class="block__title">{{ settings.title }}</h2>
    <div class="block__cards">
      <div v-for="character in characters" :key="character.id" class="block__card-slot">
        <CharacterCard :item="character" :locale="locales.current" />
      </div>
    </div>
  </BlockShell>
</template>
