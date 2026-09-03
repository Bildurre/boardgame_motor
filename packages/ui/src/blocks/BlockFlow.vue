<script setup lang="ts">
import { computed, type Component } from 'vue'
import { containerFlow, type PageBlock } from './blockTree'

// Pinta una lista de bloques públicos con su componente del registry de la
// app (clave = component), sacando del flujo a los descendientes de los
// CONTENEDORES (pestañas), que los reciben en `children` junto con el
// propio registry para pintarlos por dentro (este mismo componente, en
// recursión). Cada bloque lleva su ancla `#block-{id}` (la del índice).
const props = defineProps<{
  blocks: PageBlock[]
  registry: Record<string, Component>
}>()

const entries = computed(() =>
  containerFlow(props.blocks).filter((entry) => props.registry[entry.block.component]),
)
</script>

<template>
  <component
    :is="registry[entry.block.component]"
    v-for="entry in entries"
    :id="`block-${entry.block.id}`"
    :key="entry.block.id"
    :settings="entry.block.settings"
    :data="entry.block.data"
    v-bind="entry.children ? { children: entry.children, registry } : {}"
  />
</template>
