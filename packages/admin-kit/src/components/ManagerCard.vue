<script setup lang="ts">
import { ChevronDown, ChevronRight } from '@lucide/vue'

// Tarjeta colapsable compartida por los gestores del admin (previews y PDF):
// cabecera clicable, resumen siempre visible (slot meta), cuerpo al abrir y
// pie de acciones. Se coloca dentro de .manager-grid (1 o 2 columnas según
// el ancho del CONTENEDOR, no del viewport).

defineProps<{
  title: string
  /** Etiqueta pequeña junto al título (p. ej. el layout del export). */
  chip?: string
}>()

const open = defineModel<boolean>('open', { default: false })
</script>

<template>
  <section class="manager-card" :class="{ 'is-open': open }">
    <button type="button" class="manager-card__head" @click="open = !open">
      <component :is="open ? ChevronDown : ChevronRight" :size="18" class="manager-card__chevron" />
      <span class="manager-card__title">{{ title }}</span>
      <span v-if="chip" class="manager-card__chip">{{ chip }}</span>
    </button>

    <div v-if="$slots.meta" class="manager-card__meta">
      <slot name="meta" />
    </div>

    <div v-if="open" class="manager-card__body">
      <slot />
    </div>

    <footer v-if="open && $slots.actions" class="manager-card__actions">
      <slot name="actions" />
    </footer>
  </section>
</template>
