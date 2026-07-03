<script setup lang="ts">
import { PanelRight } from '@lucide/vue'

// Tarjeta de los gestores del admin (previews y PDF): NO colapsa. Muestra el
// título (+ chip opcional), el resumen (slot meta) y las acciones "de todas"
// en el pie (slot actions). TODA la tarjeta selecciona (excepto los botones,
// enlaces e inputs interiores): su detalle —selector de elementos, etc.— se
// abre en el panel derecho del layout.
// Se coloca dentro de .manager-grid (1-2 columnas según el contenedor).

defineProps<{
  title: string
  /** Etiqueta pequeña junto al título (p. ej. el layout del export). */
  chip?: string
  /** Tarjeta activa: su contenido manda en el panel derecho. */
  active?: boolean
}>()

const emit = defineEmits<{ select: [] }>()

/** Selecciona salvo que el clic naciera en un control interior. */
function onClick(event: MouseEvent) {
  const target = event.target as HTMLElement | null
  if (target?.closest('button, a, input, select, textarea, label')) return
  emit('select')
}
</script>

<template>
  <section
    class="manager-card"
    :class="{ 'is-active': active }"
    role="button"
    tabindex="0"
    @click="onClick"
    @keydown.enter.self="emit('select')"
    @keydown.space.self.prevent="emit('select')"
  >
    <div class="manager-card__head">
      <span class="manager-card__title">{{ title }}</span>
      <span v-if="chip" class="manager-card__chip">{{ chip }}</span>
      <PanelRight :size="16" class="manager-card__hint" />
    </div>

    <div v-if="$slots.meta" class="manager-card__meta">
      <slot name="meta" />
    </div>

    <div v-if="$slots.default" class="manager-card__body">
      <slot />
    </div>

    <footer v-if="$slots.actions" class="manager-card__actions">
      <slot name="actions" />
    </footer>
  </section>
</template>
