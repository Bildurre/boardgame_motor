<script setup lang="ts">
import BaseModal from './BaseModal.vue'
import { Save, X } from '@lucide/vue'
import BaseButton from './BaseButton.vue'

// Modal de formulario (portado de kontuan): BaseModal + pie con Cancelar/Guardar.
// Agnóstico de i18n: las etiquetas se pasan por props (la app las traduce).
const props = withDefaults(
  defineProps<{
    modelValue: boolean
    title: string
    size?: 'sm' | 'md' | 'lg'
    loading?: boolean
    submitLabel?: string
    cancelLabel?: string
    submitVariant?: 'primary' | 'secondary' | 'danger' | 'success'
  }>(),
  {
    size: 'md',
    loading: false,
    submitLabel: 'Guardar',
    cancelLabel: 'Cancelar',
    submitVariant: 'primary',
  },
)

const emit = defineEmits<{ 'update:modelValue': [boolean]; submit: [] }>()

function close() {
  if (props.loading) return
  emit('update:modelValue', false)
}
</script>

<template>
  <BaseModal
    :model-value="modelValue"
    :title="title"
    :size="size"
    @update:model-value="(v) => !loading && emit('update:modelValue', v)"
  >
    <form class="edit-modal__form" @submit.prevent="emit('submit')">
      <div class="edit-modal__body"><slot /></div>
    </form>

    <template #footer>
      <BaseButton variant="secondary" type="button" @click="close">
        <template #icon><X :size="16" /></template>
        {{ cancelLabel }}
      </BaseButton>
      <BaseButton :variant="submitVariant" type="button" @click="emit('submit')">
        <template #icon><Save :size="16" /></template>
        {{ loading ? '…' : submitLabel }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
