<script setup lang="ts">
// Botón base. El slot `icon` (opcional, patrón kontuan) coloca un icono a la
// izquierda; en contenedores estrechos el texto pasa a mini-etiqueta bajo el
// icono (ver _base-button.scss) y en anchos vuelve a la fila.
withDefaults(
  defineProps<{
    variant?:
      'primary' | 'secondary' | 'danger' | 'success' | 'info' | 'warning' | 'text' | 'text-danger'
    type?: 'button' | 'submit'
  }>(),
  { variant: 'primary', type: 'button' },
)
</script>

<template>
  <button
    :type="type"
    class="edc-button"
    :class="[`edc-button--${variant}`, { 'edc-button--has-icon': !!$slots.icon }]"
  >
    <span v-if="$slots.icon" class="edc-button__icon"><slot name="icon" /></span>
    <span v-if="$slots.default" class="edc-button__text"><slot /></span>
  </button>
</template>
