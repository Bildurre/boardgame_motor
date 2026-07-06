<script setup lang="ts">
import { BlockShell } from '@edc-motor/ui'
import { SchemeCard, type Scheme } from '@playground/shared'
import { useLocalesStore } from '@/stores/locales'

// Bloque con-datos de ESTE juego: la casa elegida en el admin (campo
// entity del DSL), con su color, descripción y (opcional) sus argucias.
interface HouseData {
  id: number
  name: Record<string, string>
  color: string | null
  image: string | null
  description: Record<string, string>
  slug: Record<string, string>
}

const props = defineProps<{
  settings: Record<string, unknown>
  data: { house?: HouseData | null; schemes?: Record<string, unknown>[] }
}>()

const locales = useLocalesStore()

function tr(obj: Record<string, string> | null | undefined) {
  if (!obj) return ''
  return obj[locales.current] || Object.values(obj)[0] || ''
}

function slug(): string {
  const map = props.data.house?.slug ?? {}
  return map[locales.current] || Object.values(map)[0] || ''
}
</script>

<!-- eslint-disable vue/no-v-html -- HTML saneado en servidor (DC-09) -->
<template>
  <BlockShell :settings="settings" class="block--featured-house">
    <template v-if="data.house">
      <h2 v-if="settings.title" class="block__title">{{ settings.title }}</h2>
      <div class="block__house-feature" :style="{ '--c': data.house.color ?? undefined }">
        <img v-if="data.house.image" class="block__house-shield" :src="data.house.image" alt="" />
        <div class="block__house-body">
          <h3>
            <RouterLink
              :to="{
                name: 'entity-detail',
                params: { locale: locales.current, section: 'casas', slug: slug() },
              }"
              >{{ tr(data.house.name) }}</RouterLink
            >
          </h3>
          <div v-if="settings.intro" class="rich-content" v-html="settings.intro" />
          <div
            v-else-if="tr(data.house.description)"
            class="rich-content"
            v-html="tr(data.house.description)"
          />
        </div>
      </div>
      <div v-if="data.schemes?.length" class="block__cards">
        <div v-for="(scheme, i) in data.schemes" :key="i" class="block__card-slot">
          <SchemeCard :item="scheme as unknown as Scheme" :locale="locales.current" />
        </div>
      </div>
    </template>
  </BlockShell>
</template>
