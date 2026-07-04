<script setup lang="ts">
import { computed, ref } from 'vue'
import { Type, X } from '@lucide/vue'

// Subida de fuente con arrastrar-y-soltar o clic (hermano de ImageUpload):
// TODA la zona abre el diálogo; el fichero elegido se muestra con nombre y
// tamaño. Controlado por v-model (File | null). Agnóstico de i18n (DC-29).
const props = withDefaults(
  defineProps<{
    modelValue: File | null
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
    label: '',
    accept: '.woff2,.woff,.ttf,.otf',
    maxSize: 4,
    dragText: 'Arrastra una fuente o haz clic',
    hintText: '',
    tooLargeText: 'El archivo es demasiado grande.',
    invalidTypeText: 'Formato de archivo no válido.',
  },
)

const emit = defineEmits<{ 'update:modelValue': [File | null] }>()

const isDragging = ref(false)
const localError = ref<string | null>(null)
const fileInputRef = ref<HTMLInputElement>()

const shownError = computed(() => props.error || localError.value)

const extensions = computed(() =>
  props.accept.split(',').map((ext) => ext.trim().toLowerCase().replace(/^\./, '')),
)

function sizeLabel(file: File): string {
  const kb = file.size / 1024
  return kb >= 1024 ? `${(kb / 1024).toFixed(1)} MB` : `${Math.max(1, Math.round(kb))} KB`
}

function handleFile(file: File) {
  const extension = file.name.split('.').pop()?.toLowerCase() ?? ''
  if (!extensions.value.includes(extension)) {
    localError.value = props.invalidTypeText
    return
  }
  if (file.size > props.maxSize * 1024 * 1024) {
    localError.value = props.tooLargeText
    return
  }
  localError.value = null
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
  localError.value = null
  emit('update:modelValue', null)
}

function openDialog() {
  fileInputRef.value?.click()
}
</script>

<template>
  <div class="font-upload">
    <span v-if="label" class="font-upload__label">{{ label }}</span>
    <div
      class="font-upload__zone"
      :class="{
        'font-upload__zone--dragging': isDragging,
        'font-upload__zone--error': shownError,
        'font-upload__zone--has-file': modelValue,
      }"
      @dragover.prevent="isDragging = true"
      @dragleave="isDragging = false"
      @drop.prevent="onDrop"
      @click="openDialog"
    >
      <input
        ref="fileInputRef"
        type="file"
        :accept="accept"
        class="font-upload__input"
        @change="onFileChange"
      />

      <Type class="font-upload__icon" :size="20" />

      <template v-if="modelValue">
        <span class="font-upload__name">{{ modelValue.name }}</span>
        <span class="font-upload__size">{{ sizeLabel(modelValue) }}</span>
        <button class="font-upload__remove" type="button" @click.stop="clear">
          <X :size="14" />
        </button>
      </template>

      <template v-else>
        <span class="font-upload__text">{{ dragText }}</span>
        <span v-if="hintText" class="font-upload__hint">{{ hintText }}</span>
      </template>
    </div>
    <p v-if="shownError" class="font-upload__error">{{ shownError }}</p>
  </div>
</template>
