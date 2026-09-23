<script setup lang="ts">
// Botón base. El slot `icon` (opcional, patrón kontuan) coloca un icono a la
// izquierda; en contenedores estrechos el texto pasa a mini-etiqueta bajo el
// icono (ver _base-button.scss) y en anchos vuelve a la fila.
//
// Con `href` se renderiza como ENLACE (<a>) con el mismo aspecto: para las
// acciones que navegan (abrir el detalle de un elemento) y así el usuario
// puede abrirlas en otra pestaña (clic derecho, botón central, ctrl+clic).
// Con vue-router: <RouterLink :to custom v-slot="{ href, navigate }">
// <BaseButton :href="href" @click="navigate">…</BaseButton></RouterLink>.
withDefaults(
  defineProps<{
    variant?:
      'primary' | 'secondary' | 'danger' | 'success' | 'info' | 'warning' | 'text' | 'text-danger'
    type?: 'button' | 'submit'
    /** URL del enlace: el botón pasa a ser un <a> (sin `type`). */
    href?: string
  }>(),
  { variant: 'primary', type: 'button', href: undefined },
)
</script>

<template>
  <component
    :is="href ? 'a' : 'button'"
    :type="href ? undefined : type"
    :href="href"
    class="edc-button"
    :class="[`edc-button--${variant}`, { 'edc-button--has-icon': !!$slots.icon }]"
  >
    <span v-if="$slots.icon" class="edc-button__icon"><slot name="icon" /></span>
    <span v-if="$slots.default" class="edc-button__text"><slot /></span>
  </component>
</template>
