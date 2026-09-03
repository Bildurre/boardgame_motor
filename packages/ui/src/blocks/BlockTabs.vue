<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch, type Component } from 'vue'
import BlockFlow from './BlockFlow.vue'
import BlockShell from './BlockShell.vue'
import { groupByDirectChild, type PageBlock } from './blockTree'

// Pestañas (doc 03): contenedor cuyos bloques hijos son el contenido de
// cada pestaña — el hijo directo n.º N es la pestaña N (con sus propios
// descendientes dentro), en el orden del gestor. Solo se MONTA la pestaña
// activa: un índice de entidad por pestaña registra su barra derecha y su
// paginación únicamente mientras está a la vista. Enlace directo por el
// hash de la URL (#ancla del repetidor, o #tab-{id}-{n}); al cambiar de
// pestaña el hash se reescribe sin navegar (replaceState) y se escucha
// hashchange/popstate para seguir a los enlaces de la propia página.
interface TabSetting {
  label?: string
  icon?: string | null
  anchor?: string | null
}

const props = withDefaults(
  defineProps<{
    settings: {
      title?: string
      subtitle?: string
      tabs?: TabSetting[]
      [key: string]: unknown
    }
    data?: Record<string, unknown>
    /** Descendientes del bloque (BlockFlow los saca del flujo). */
    children?: PageBlock[]
    /** Registry de componentes de la app (para pintar los hijos). */
    registry?: Record<string, Component>
    /** Id del bloque (BlockFlow lo pone como atributo `id="block-{id}"`). */
    id?: string
  }>(),
  { data: () => ({}), children: () => [], registry: () => ({}), id: '' },
)

// Id del contenedor: el del atributo (BlockFlow) o, si no llega, el padre
// del primer descendiente (en preorden el primero es siempre hijo directo).
const blockId = computed(
  () =>
    Number(String(props.id).replace(/^block-/, '')) || Number(props.children[0]?.parent_id) || 0,
)

const tabs = computed<TabSetting[]>(() => props.settings.tabs ?? [])

// Contenido por pestaña: hijos directos con sus descendientes. Los hijos
// que sobren respecto a las pestañas declaradas no se pintan (el gestor
// del admin ya lo avisa).
const groups = computed(() => groupByDirectChild(blockId.value, props.children))

/** Ancla de la pestaña n (0-based): la del repetidor, saneada, o la genérica. */
function anchorOf(index: number): string {
  const custom = String(tabs.value[index]?.anchor ?? '')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9_-]+/g, '-')
    .replace(/^-+|-+$/g, '')
  return custom || `tab-${blockId.value}-${index + 1}`
}

const active = ref(0)

function indexForHash(hash: string): number {
  const wanted = hash.replace(/^#/, '')
  if (!wanted) return -1
  return tabs.value.findIndex((_, index) => anchorOf(index) === wanted)
}

function syncFromHash() {
  const index = indexForHash(window.location.hash)
  if (index >= 0) active.value = index
}

function select(index: number) {
  active.value = index
  // Sin navegar ni saltar: el hash es solo el enlace directo a la pestaña.
  const url = `${window.location.pathname}${window.location.search}#${anchorOf(index)}`
  window.history.replaceState(window.history.state, '', url)
}

onMounted(() => {
  syncFromHash()
  window.addEventListener('hashchange', syncFromHash)
  window.addEventListener('popstate', syncFromHash)
})
onBeforeUnmount(() => {
  window.removeEventListener('hashchange', syncFromHash)
  window.removeEventListener('popstate', syncFromHash)
})

// Si las pestañas cambian (otro locale, edición en vivo) y la activa
// desaparece, vuelve a la primera.
watch(tabs, (list) => {
  if (active.value >= list.length) active.value = 0
})

const panelId = (index: number) => `${anchorOf(index)}-panel`
</script>

<template>
  <!-- `id` es prop aquí (no cae solo al DOM): se vuelve a poner en la raíz
       para que el ancla #block-{id} del índice siga funcionando -->
  <BlockShell :id="id || undefined" :settings="settings" class="block--tabs">
    <h2 v-if="settings.title" class="block__title">{{ settings.title }}</h2>
    <p v-if="settings.subtitle" class="block__subtitle">{{ settings.subtitle }}</p>

    <div v-if="tabs.length" class="block__tabs" role="tablist">
      <button
        v-for="(tab, index) in tabs"
        :id="anchorOf(index)"
        :key="index"
        type="button"
        role="tab"
        class="block__tab"
        :class="{ 'is-active': index === active }"
        :aria-selected="index === active"
        :aria-controls="panelId(index)"
        :tabindex="index === active ? 0 : -1"
        @click="select(index)"
      >
        <img v-if="tab.icon" class="block__tab-icon" :src="tab.icon" alt="" aria-hidden="true" />
        <span class="block__tab-label">{{ tab.label }}</span>
      </button>
    </div>

    <!-- Solo la pestaña activa está montada (key: cada cambio remonta) -->
    <div
      v-if="tabs.length"
      :id="panelId(active)"
      :key="active"
      class="block__tab-panel"
      role="tabpanel"
      :aria-labelledby="anchorOf(active)"
    >
      <BlockFlow :blocks="groups[active] ?? []" :registry="registry" />
    </div>
  </BlockShell>
</template>
