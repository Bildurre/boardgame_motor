<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { ChevronDown } from '@lucide/vue'
import ImageUpload from './ImageUpload.vue'

// Imagen traducible (una URL por locale): mismo selector desplegable de
// locale que TranslatableInput, con un ImageUpload para el idioma activo.
// La subida la pone quien lo usa (prop `upload`): este componente solo
// gestiona el mapa locale => URL. En el render, el motor localiza el valor
// con fallback al locale por defecto (localizeSettings).
interface Locale {
  code: string
  name: string
}

const props = withDefaults(
  defineProps<{
    modelValue?: Record<string, string>
    locales: Locale[]
    label?: string
    required?: boolean
    /** Sube el fichero (con la URL a la que sustituye, para que el backend
     *  borre la anterior) y devuelve la URL pública que se guarda. */
    upload: (file: File, replaces?: string | null) => Promise<string>
    /** Borra el fichero al pulsar "quitar" (opcional). */
    removeFile?: (url: string) => void | Promise<void>
    error?: string
  }>(),
  { modelValue: () => ({}), required: false },
)

const emit = defineEmits<{ 'update:modelValue': [Record<string, string>] }>()

const codes = computed(() => props.locales.map((l) => l.code))
const active = ref(codes.value[0] ?? 'es')
const open = ref(false)
const dropdownRef = ref<HTMLElement>()
const uploading = ref(false)

const currentUrl = computed(() => props.modelValue?.[active.value] || null)
const filledCount = computed(() => codes.value.filter((c) => !!props.modelValue?.[c]).length)
const hasContent = (code: string) => !!props.modelValue?.[code]

function selectLocale(code: string) {
  active.value = code
  open.value = false
}

function setUrl(url: string | null) {
  const next = { ...props.modelValue }
  if (url) next[active.value] = url
  else delete next[active.value]
  emit('update:modelValue', next)
}

async function onFile(file: File | null) {
  if (!file) {
    onRemove()
    return
  }
  uploading.value = true
  try {
    setUrl(await props.upload(file, currentUrl.value))
  } finally {
    uploading.value = false
  }
}

function onRemove() {
  const url = currentUrl.value
  if (url && props.removeFile) Promise.resolve(props.removeFile(url)).catch(() => {})
  setUrl(null)
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
      :model-value="null"
      :current-url="currentUrl"
      :error="uploading ? undefined : error"
      @update:model-value="onFile"
      @remove="onRemove"
    />

    <p v-if="error" class="form-field__error">{{ error }}</p>
  </div>
</template>
