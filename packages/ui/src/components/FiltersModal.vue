<script setup lang="ts">
import { FunnelX, X } from '@lucide/vue'
import BaseModal from './BaseModal.vue'
import BaseButton from './BaseButton.vue'

// Modal de filtros de los index: BaseModal (overlay, Escape, click fuera,
// aria-modal) SIN semántica de guardar — los campos del slot aplican en vivo
// (v-model directo desde el consumidor). El cuerpo es un grid que suma
// columnas según el ancho del propio modal (container queries). El pie trae
// "Quitar filtros" (emit `clear`, solo con activeCount > 0) y "Cerrar".
// Agnóstico de i18n (DC-29): textos por prop, defaults en castellano.

withDefaults(
  defineProps<{
    modelValue: boolean
    title?: string
    size?: 'sm' | 'md' | 'lg'
    activeCount?: number
    clearLabel?: string
    closeLabel?: string
  }>(),
  {
    title: 'Filtros',
    size: 'md',
    activeCount: 0,
    clearLabel: 'Quitar filtros',
    closeLabel: 'Cerrar',
  },
)

const emit = defineEmits<{ 'update:modelValue': [boolean]; clear: [] }>()
</script>

<template>
  <BaseModal
    :model-value="modelValue"
    :title="title"
    :size="size"
    :close-label="closeLabel"
    @update:model-value="(v) => emit('update:modelValue', v)"
  >
    <div class="filters-modal">
      <div class="filters-modal__grid"><slot /></div>
    </div>

    <template #footer>
      <BaseButton
        v-if="activeCount > 0"
        variant="secondary"
        type="button"
        class="filters-modal__clear"
        @click="emit('clear')"
      >
        <template #icon><FunnelX :size="16" /></template>
        {{ clearLabel }}
      </BaseButton>
      <BaseButton variant="primary" type="button" @click="emit('update:modelValue', false)">
        <template #icon><X :size="16" /></template>
        {{ closeLabel }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
