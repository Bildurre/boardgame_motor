<script setup lang="ts">
import { inject, ref, watch } from 'vue'
import { FormLocaleKey } from '../composables/useFormLocale'

// Selector COMPACTO de locale global de un formulario: botones segmentados
// con los códigos (unión de los locales de los campos traducibles suscritos
// vía provide/inject, ver useFormLocale). Al pulsar uno cambia el tab activo
// de TODOS los campos a la vez; los tabs individuales siguen funcionando por
// su cuenta (por eso el resaltado marca la ÚLTIMA elección global, no un
// estado que los campos puedan desmentir). Solo se pinta si el formulario
// contiene campos traducibles. Agnóstico de i18n (DC-29): texto por prop.

withDefaults(defineProps<{ title?: string }>(), { title: 'Idioma de todos los campos' })

const context = inject(FormLocaleKey, null)

// Última elección global (para el resaltado); se reinicia si cambia el set
// de locales (formularios que se rellenan en diferido).
const current = ref<string | null>(null)
if (context) {
  watch(context.locales, () => {
    if (current.value && !context.locales.value.some((l) => l.code === current.value)) {
      current.value = null
    }
  })
}

function setAll(code: string) {
  if (!context) return
  current.value = code
  context.setAll(code)
}
</script>

<template>
  <div
    v-if="context && context.hasFields.value && context.locales.value.length > 1"
    class="form-locale-switch"
    role="group"
    :title="title"
    :aria-label="title"
  >
    <button
      v-for="locale in context.locales.value"
      :key="locale.code"
      type="button"
      class="form-locale-switch__option"
      :class="{ 'is-active': locale.code === current }"
      :title="locale.name"
      @click="setAll(locale.code)"
    >
      {{ locale.code.toUpperCase() }}
    </button>
  </div>
</template>
