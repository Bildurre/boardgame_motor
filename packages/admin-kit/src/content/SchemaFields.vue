<script setup lang="ts">
import type { AxiosInstance } from 'axios'
import {
  BaseCheckbox,
  BaseInput,
  BaseSelect,
  BaseTextarea,
  ImageUpload,
  NumericInput,
  PaletteColorPicker,
  RichTextInput,
  TranslatableImage,
  TranslatableInput,
  type RichIcon,
  type RichTextLabels,
  type SelectOption,
} from '@bgm/ui'

// Renderer del DSL de campos (DC-08): pinta el formulario de un bloque a
// partir del esquema serializado del BlockType (GET /admin/block-types).
// Añadir un tipo de bloque NO toca este componente.
// Agnóstico de i18n (DC-29): etiquetas del esquema (castellano) con gancho
// `translate` para que el juego las localice por convención.

export interface FieldSchema {
  key: string
  type: string
  label: string
  translatable: boolean
  required: boolean
  default: unknown
  options: Record<string, string> | null
  min: number | null
  max: number | null
}

const props = withDefaults(
  defineProps<{
    fields: FieldSchema[]
    modelValue: Record<string, unknown>
    locales: { code: string; name: string }[]
    /** Cliente de la API del admin (sube las imágenes de contenido). */
    api: AxiosInstance
    /** Iconos del juego para el WYSIWYG. */
    icons?: RichIcon[]
    richLabels?: Partial<RichTextLabels>
    /** Localiza etiquetas: (clave, fallback) => texto. */
    translate?: (key: string, fallback: string) => string
  }>(),
  { icons: () => [], richLabels: () => ({}), translate: undefined },
)

const emit = defineEmits<{ 'update:modelValue': [value: Record<string, unknown>] }>()

function label(field: FieldSchema): string {
  return props.translate?.(`blockFields.${field.key}`, field.label) ?? field.label
}

function set(key: string, value: unknown) {
  emit('update:modelValue', { ...props.modelValue, [key]: value })
}

function translations(field: FieldSchema): Record<string, string> {
  const value = props.modelValue[field.key]
  // Guarda ante datos antiguos guardados como cadena única (no traducible).
  if (!value || typeof value !== 'object') return {}
  return value as Record<string, string>
}

function selectOptions(field: FieldSchema): SelectOption[] {
  return Object.entries(field.options ?? {}).map(([value, text]) => ({
    value,
    label: props.translate?.(`blockOptions.${field.key}.${value}`, text) ?? text,
  }))
}

/** Sube la imagen al momento; en settings queda la URL pública. */
async function upload(file: File): Promise<string> {
  const form = new FormData()
  form.append('image', file)
  const { data } = await props.api.post('/admin/content/uploads', form)
  return data.url
}

async function uploadImage(field: FieldSchema, file: File | null) {
  set(field.key, file ? await upload(file) : null)
}
</script>

<template>
  <div class="schema-fields">
    <template v-for="field in fields" :key="field.key">
      <!-- Traducibles: text / textarea / richtext van al TranslatableInput -->
      <TranslatableInput
        v-if="field.translatable && ['text', 'textarea', 'richtext'].includes(field.type)"
        :model-value="translations(field)"
        :locales="locales"
        :label="label(field)"
        :required="field.required"
        :type="
          field.type === 'richtext' ? 'wysiwyg' : field.type === 'textarea' ? 'textarea' : 'text'
        "
        :icons="icons"
        :rich-labels="richLabels"
        @update:model-value="(v) => set(field.key, v)"
      />

      <BaseInput
        v-else-if="field.type === 'text'"
        :model-value="(modelValue[field.key] as string) ?? ''"
        :label="label(field)"
        :required="field.required"
        @update:model-value="(v) => set(field.key, v)"
      />

      <BaseTextarea
        v-else-if="field.type === 'textarea'"
        :model-value="(modelValue[field.key] as string) ?? ''"
        :label="label(field)"
        @update:model-value="(v: string) => set(field.key, v)"
      />

      <RichTextInput
        v-else-if="field.type === 'richtext'"
        :model-value="(modelValue[field.key] as string) ?? ''"
        :icons="icons"
        :labels="richLabels"
        @update:model-value="(v: string) => set(field.key, v)"
      />

      <NumericInput
        v-else-if="field.type === 'number'"
        :model-value="(modelValue[field.key] as number) ?? (field.default as number) ?? 0"
        :label="label(field)"
        :min="field.min ?? 0"
        :max="field.max ?? undefined"
        @update:model-value="(v) => set(field.key, v)"
      />

      <BaseCheckbox
        v-else-if="field.type === 'boolean'"
        :model-value="Boolean(modelValue[field.key] ?? field.default)"
        :label="label(field)"
        @update:model-value="(v) => set(field.key, v)"
      />

      <BaseSelect
        v-else-if="field.type === 'select'"
        :model-value="(modelValue[field.key] as string) ?? (field.default as string) ?? ''"
        :label="label(field)"
        :options="selectOptions(field)"
        @update:model-value="(v) => set(field.key, v)"
      />

      <div v-else-if="field.type === 'color'" class="schema-fields__color">
        <span class="form-field__label">{{ label(field) }}</span>
        <PaletteColorPicker
          :model-value="(modelValue[field.key] as string) ?? null"
          @update:model-value="(v) => set(field.key, v)"
        />
      </div>

      <!-- Imagen traducible: una URL por locale (fallback al default al renderizar) -->
      <TranslatableImage
        v-else-if="field.type === 'image' && field.translatable"
        :model-value="translations(field)"
        :locales="locales"
        :label="label(field)"
        :required="field.required"
        :upload="upload"
        @update:model-value="(v) => set(field.key, v)"
      />

      <div v-else-if="field.type === 'image'" class="schema-fields__image">
        <span class="form-field__label">{{ label(field) }}</span>
        <ImageUpload
          :model-value="null"
          :current-url="(modelValue[field.key] as string) ?? null"
          @update:model-value="(f: File | null) => uploadImage(field, f)"
          @remove="set(field.key, null)"
        />
      </div>
    </template>
  </div>
</template>
