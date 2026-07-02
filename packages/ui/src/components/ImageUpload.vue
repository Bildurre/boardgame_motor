<script setup lang="ts">
import { computed, ref } from 'vue'
import { Trash2 } from '@lucide/vue'

// Subida de imagen con arrastrar-y-soltar o clic (portado de kontuan).
// Agnóstico de i18n: los textos van por props.
const props = withDefaults(
  defineProps<{
    modelValue: File | null
    currentUrl?: string | null
    label?: string
    accept?: string
    maxSize?: number // MB
    error?: string
    dragText?: string
    hintText?: string
    tooLargeText?: string
    invalidTypeText?: string
  }>(),
  {
    currentUrl: null,
    label: '',
    accept: 'image/*',
    maxSize: 4,
    dragText: 'Arrastra una imagen o haz clic',
    hintText: '',
    tooLargeText: 'El archivo es demasiado grande.',
    invalidTypeText: 'Formato de archivo no válido.',
  },
)

const emit = defineEmits<{ 'update:modelValue': [File | null]; remove: [] }>()

const isDragging = ref(false)
const previewUrl = ref<string | null>(null)
const removed = ref(false)
const localError = ref<string | null>(null)
const fileInputRef = ref<HTMLInputElement>()

const displayUrl = computed(() => (removed.value ? null : previewUrl.value || props.currentUrl))
// El error externo (validación del servidor) manda sobre el interno.
const shownError = computed(() => props.error || localError.value)

function handleFile(file: File) {
  // Validación en cliente: tamaño y tipo, con feedback (no se ignora en silencio).
  if (file.size > props.maxSize * 1024 * 1024) {
    localError.value = props.tooLargeText
    return
  }
  if (!file.type.startsWith('image/')) {
    localError.value = props.invalidTypeText
    return
  }
  localError.value = null
  removed.value = false
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
  previewUrl.value = URL.createObjectURL(file)
  emit('update:modelValue', file)
}

function onFileChange(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files?.[0]) handleFile(input.files[0])
  if (input) input.value = ''
}

function onDrop(event: DragEvent) {
  isDragging.value = false
  if (event.dataTransfer?.files?.[0]) handleFile(event.dataTransfer.files[0])
}

function clear() {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = null
  }
  removed.value = true
  localError.value = null
  emit('update:modelValue', null)
  emit('remove')
}

function openDialog() {
  fileInputRef.value?.click()
}
</script>

<template>
  <div class="image-upload">
    <label v-if="label" class="image-upload__label">{{ label }}</label>
    <div
      class="image-upload__zone"
      :class="{
        'image-upload__zone--dragging': isDragging,
        'image-upload__zone--error': shownError,
        'image-upload__zone--has-image': displayUrl,
      }"
      @dragover.prevent="isDragging = true"
      @dragleave="isDragging = false"
      @drop.prevent="onDrop"
      @click="!displayUrl && openDialog()"
    >
      <input
        ref="fileInputRef"
        type="file"
        :accept="accept"
        class="image-upload__input"
        @change="onFileChange"
      />

      <template v-if="displayUrl">
        <img :src="displayUrl" class="image-upload__preview" alt="" />
        <button class="image-upload__remove" type="button" @click.stop="clear">
          <Trash2 :size="16" />
        </button>
      </template>

      <template v-else>
        <div class="image-upload__placeholder">
          <svg
            class="image-upload__icon"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
          >
            <rect x="3" y="3" width="18" height="18" rx="3" />
            <circle cx="8.5" cy="8.5" r="1.5" />
            <path d="m21 15-5-5L5 21" />
          </svg>
          <span class="image-upload__text">{{ dragText }}</span>
          <span v-if="hintText" class="image-upload__hint">{{ hintText }}</span>
        </div>
      </template>
    </div>
    <p v-if="shownError" class="image-upload__error">{{ shownError }}</p>
  </div>
</template>
