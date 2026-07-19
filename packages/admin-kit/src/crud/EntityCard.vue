<script setup lang="ts">
// Tarjeta de entidad para los listados (index). Mezcla kontuan + CDL:
// - kontuan: contenedor con borde/sombra, hover al accent, slots.
// - CDL: cabecera con título + acciones y divisoria, zona de contenido con
//   "badges" (chips de estado) y "meta" (datos secundarios).
// Opcionalmente una franja "media" arriba (p. ej. el emblema de la casa):
// solo para entidades con imagen/preview — sin imagen, sin franja.
import { SquarePen } from '@lucide/vue'

import { slotHasContent } from '../composables/slotHasContent'

withDefaults(
  defineProps<{
    title: string
    clickable?: boolean
    muted?: boolean
    /** Marca la tarjeta como seleccionada (panel derecho). */
    active?: boolean
    /** Botón de editar en la cabecera (entidades SIN vista single). */
    editable?: boolean
    /** Texto accesible del botón de editar (agnóstico de i18n, DC-29). */
    editLabel?: string
    /**
     * Color de acento de la entidad (p. ej. su facción, de los datos del
     * juego): tiñe el borde de la tarjeta. Sin la prop, borde del tema.
     */
    accentColor?: string
  }>(),
  { editLabel: 'Editar' },
)

defineEmits<{ view: []; edit: [] }>()

defineSlots<{
  media?: () => unknown
  actions?: () => unknown
  badges?: () => unknown
  meta?: () => unknown
  default?: () => unknown
}>()
</script>

<template>
  <div
    class="entity-card"
    :class="{
      'entity-card--clickable': clickable,
      'entity-card--muted': muted,
      'entity-card--accented': !!accentColor,
      'is-active': active,
    }"
    :style="accentColor ? { '--entity-card-accent': accentColor } : undefined"
    @click="clickable ? $emit('view') : undefined"
  >
    <div v-if="slotHasContent($slots.media)" class="entity-card__media"><slot name="media" /></div>

    <div class="entity-card__header">
      <h3 class="entity-card__title">{{ title }}</h3>
      <div v-if="$slots.actions || editable" class="entity-card__actions" @click.stop>
        <slot name="actions" />
        <button
          v-if="editable"
          type="button"
          class="entity-card__edit"
          :title="editLabel"
          :aria-label="editLabel"
          @click="$emit('edit')"
        >
          <SquarePen :size="14" />
        </button>
      </div>
    </div>

    <!-- slotHasContent y no $slots.x: con el template declarado pero todo
         v-if falso dentro, la card no debe pintar la parte inferior vacía -->
    <div
      v-if="slotHasContent($slots.badges) || slotHasContent($slots.meta) || slotHasContent($slots.default)"
      class="entity-card__content"
    >
      <div v-if="slotHasContent($slots.badges)" class="entity-card__badges"><slot name="badges" /></div>
      <div v-if="slotHasContent($slots.meta)" class="entity-card__meta"><slot name="meta" /></div>
      <slot />
    </div>
  </div>
</template>
