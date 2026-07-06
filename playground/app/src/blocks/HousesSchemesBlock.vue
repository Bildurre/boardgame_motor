<script setup lang="ts">
import { BlockShell } from '@edc-motor/ui'
import { SchemeCard, type Scheme } from '@playground/shared'
import { useLocalesStore } from '@/stores/locales'

// Bloque con-datos de ESTE juego: casas publicadas con sus argucias.
interface HouseRow {
  id: number
  name: Record<string, string>
  color: string | null
  schemes: Record<string, unknown>[]
}

defineProps<{ settings: Record<string, unknown>; data: { houses?: HouseRow[] } }>()

const locales = useLocalesStore()

function houseName(house: HouseRow): string {
  return house.name[locales.current] || Object.values(house.name)[0] || ''
}
</script>

<!-- eslint-disable vue/no-v-html -- HTML saneado en servidor (DC-09) -->
<template>
  <BlockShell :settings="settings" class="block--houses-schemes">
    <h2 v-if="settings.title" class="block__title">{{ settings.title }}</h2>
    <div v-if="settings.intro" class="block__text rich-content" v-html="settings.intro" />
    <section v-for="house in data.houses ?? []" :key="house.id" class="block__house">
      <h3 class="block__house-name" :style="{ '--c': house.color ?? undefined }">
        {{ houseName(house) }}
      </h3>
      <div class="block__cards">
        <div v-for="(scheme, i) in house.schemes" :key="i" class="block__card-slot">
          <SchemeCard :item="scheme as unknown as Scheme" :locale="locales.current" />
        </div>
      </div>
    </section>
  </BlockShell>
</template>
