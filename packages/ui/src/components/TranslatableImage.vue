<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { ChevronDown } from '@lucide/vue'
import ImageUpload from './ImageUpload.vue'
import { useFormLocaleField } from '../composables/useFormLocale'

// Imagen traducible (una por locale): mismo selector desplegable de locale
// que TranslatableInput, con un ImageUpload para el idioma activo. DIFERIDO:
// este componente NO sube nada — el mapa del v-model lleva la URL guardada
// (string) o el File pendiente por locale, y quien lo usa sube los File al
// GUARDAR. Quitar la imagen de un locale borra su clave del mapa (también
// diferido). En el render, el motor localiza el valor con fallback al locale
// por defecto (localizeSettings).
interface Locale {
  code: string
  name: string
}

const props = withDefaults(
  defineProps<{
    modelValue?: Record<string, string | File>
    locales: Locale[]
    label?: string
    required?: boolean
    error?: string
  }>(),
  { modelValue: () => ({}), required: false },
)

const emit = defineEmits<{ 'update:modelValue': [Record<string, string | File>] }>()

const codes = computed(() => props.locales.map((l) => l.code))
const active = ref(codes.value[0] ?? 'es')
const open = ref(false)
const dropdownRef = ref<HTMLElement>()

// Locale global del formulario (si el contenedor lo provee, p. ej. EditModal):
// una difusión cambia el tab activo; el selector propio sigue siendo local.
useFormLocaleField(
  computed(() => props.locales),
  (code) => {
    active.value = code
  },
)

const current = computed(() => props.modelValue?.[active.value] ?? null)
// La URL guardada y el File pendiente del locale activo, para el ImageUpload.
const currentFile = computed(() => (current.value instanceof File ? current.value : null))
const currentUrl = computed(() => (typeof current.value === 'string' ? current.value : null))
const filledCount = computed(() => codes.value.filter((c) => !!props.modelValue?.[c]).length)
const hasContent = (code: string) => !!props.modelValue?.[code]

function selectLocale(code: string) {
  active.value = code
  open.value = false
}

/** Deja el mapa en su estado final deseado: File pendiente, o sin clave. */
function setValue(value: string | File | null) {
  const next = { ...props.modelValue }
  if (value) next[active.value] = value
  else delete next[active.value]
  emit('update:modelValue', next)
}

function onFile(file: File | null) {
  setValue(file)
}

function onRemove() {
  setValue(null)
}

function onClickOutside(e: MouseEvent) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) open.value = false
}
onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))
</script>

<template>
  <div class="form-field" :class="{ 'form-field--error': error }">
    <div class="translatable-header">
      <span v-if="label" class="form-field__label">
        {{ label }}<span v-if="required" class="form-field__required">*</span>
      </span>
      <div ref="dropdownRef" class="translatable-selector">
        <button type="button" class="translatable-trigger" @click="open = !open">
          <span class="translatable-trigger__code">{{ active.toUpperCase() }}</span>
          <span class="translatable-trigger__count">{{ filledCount }}/{{ codes.length }}</span>
          <ChevronDown class="translatable-trigger__chevron" :size="12" />
        </button>
        <Transition name="dropdown">
          <div v-if="open" class="translatable-dropdown">
            <button
              v-for="loc in locales"
              :key="loc.code"
              type="button"
              :class="[
                'translatable-option',
                {
                  'translatable-option--active': loc.code === active,
                  'translatable-option--empty': !hasContent(loc.code),
                },
              ]"
              @click="selectLocale(loc.code)"
            >
              <span class="translatable-option__code">{{ loc.code.toUpperCase() }}</span>
              <span class="translatable-option__label">{{ loc.name }}</span>
              <span v-if="hasContent(loc.code)" class="translatable-option__filled" />
            </button>
          </div>
        </Transition>
      </div>
    </div>

    <ImageUpload
      :key="active"
      :model-value="currentFile"
      :current-url="currentUrl"
      :error="error"
      @update:model-value="onFile"
      @remove="onRemove"
    />

    <p v-if="error" class="form-field__error">{{ error }}</p>
  </div>
</template>
