<script setup lang="ts">
import BaseModal from './BaseModal.vue'
import { Save, X } from '@lucide/vue'
import BaseButton from './BaseButton.vue'
import FormLocaleSwitch from './FormLocaleSwitch.vue'
import { provideFormLocale } from '../composables/useFormLocale'

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
    /** Texto accesible del selector de locale global (DC-29). */
    localeSwitchLabel?: string
  }>(),
  {
    size: 'md',
    loading: false,
    submitLabel: 'Guardar',
    cancelLabel: 'Cancelar',
    submitVariant: 'primary',
    localeSwitchLabel: undefined,
  },
)

const emit = defineEmits<{ 'update:modelValue': [boolean]; submit: [] }>()

// Locale global del formulario: los campos traducibles del slot (Translatable*
// del ui, también dentro de SchemaFields/PageBlocks) se suscriben SOLOS por
// inject; el selector de la cabecera solo se pinta si hay alguno.
provideFormLocale()

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
    <!-- Cabecera propia: título + selector de locale global (si hay campos
         traducibles); el botón de cerrar lo sigue poniendo BaseModal. -->
    <template #header>
      <div class="edit-modal__header">
        <h3 class="modal__title">{{ title }}</h3>
        <FormLocaleSwitch :title="localeSwitchLabel" />
      </div>
    </template>

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
