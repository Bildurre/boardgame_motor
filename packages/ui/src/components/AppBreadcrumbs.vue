<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, RouterLink, type RouteLocationRaw } from 'vue-router'
import { ChevronRight } from '@lucide/vue'

// Migas de pan. Adaptado de kontuan sin vue-i18n: cada ruta declara en su
// `meta.breadcrumbs` un array de { label, to? }. Se antepone una miga "home".
export interface Crumb {
  label: string
  to?: RouteLocationRaw
}

const props = withDefaults(
  defineProps<{
    home?: Crumb | null
    /**
     * Migas ya resueltas/traducidas. Si se pasan, mandan sobre
     * `route.meta.breadcrumbs` (útil para i18n desde la app).
     */
    crumbs?: Crumb[] | null
  }>(),
  { home: () => ({ label: 'Inicio', to: { name: 'dashboard' } }), crumbs: null },
)

const route = useRoute()

const items = computed<Crumb[]>(() => {
  const source = props.crumbs ?? (route.meta.breadcrumbs as Crumb[] | undefined) ?? []
  if (!source.length) return []
  const head = props.home ? [props.home] : []
  // La última miga nunca es navegable (es la página actual).
  return [...head, ...source].map((c, i, arr) =>
    i === arr.length - 1 ? { label: c.label } : c,
  )
})
</script>

<template>
  <nav v-if="items.length" class="breadcrumbs" aria-label="Breadcrumbs">
    <template v-for="(item, i) in items" :key="i">
      <ChevronRight v-if="i > 0" class="breadcrumb-separator" :size="14" />
      <RouterLink v-if="item.to" :to="item.to" class="breadcrumb-link">{{ item.label }}</RouterLink>
      <span v-else class="breadcrumb-current">{{ item.label }}</span>
    </template>
  </nav>
</template>
