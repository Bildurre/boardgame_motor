<script setup lang="ts">
import type { AxiosInstance } from 'axios'
import { ArrowDown, ArrowUp, Plus, X } from '@lucide/vue'
import {
  BaseButton,
  BaseCheckbox,
  BaseInput,
  BaseSelect,
  BaseTextarea,
  IconButton,
  ImageUpload,
  NumericInput,
  PaletteColorPicker,
  RichTextInput,
  TranslatableImage,
  TranslatableInput,
  type RichIcon,
  type RichTextLabels,
  type SelectOption,
} from '@edc-motor/ui'
import EntityRefSelect from './EntityRefSelect.vue'

// Renderer del DSL de campos (DC-08): pinta el formulario de un bloque a
// partir del esquema serializado del BlockType (GET /admin/block-types).
// Añadir un tipo de bloque NO toca este componente. Los tipos anidados
// (group y repeater) se pintan con RECURSIÓN sobre sí mismo; entity con un
// buscador alimentado por el endpoint de opciones del juego.
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
  /** Subcampos de group/repeater. */
  fields?: FieldSchema[] | null
  /** Para entity: endpoint de opciones del admin. */
  options_url?: string | null
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

// --- Anidados (group / repeater) ---

function groupValue(field: FieldSchema): Record<string, unknown> {
  const value = props.modelValue[field.key]
  return value && typeof value === 'object' && !Array.isArray(value)
    ? (value as Record<string, unknown>)
    : {}
}

function rows(field: FieldSchema): Record<string, unknown>[] {
  const value = props.modelValue[field.key]
  return Array.isArray(value) ? (value as Record<string, unknown>[]) : []
}

function setRow(field: FieldSchema, index: number, row: Record<string, unknown>) {
  const list = [...rows(field)]
  list[index] = row
  set(field.key, list)
}

function addRow(field: FieldSchema) {
  set(field.key, [...rows(field), {}])
}

function removeRow(field: FieldSchema, index: number) {
  set(
    field.key,
    rows(field).filter((_, i) => i !== index),
  )
}

function moveRow(field: FieldSchema, index: number, delta: number) {
  const list = [...rows(field)]
  const target = index + delta
  if (target < 0 || target >= list.length) return
  ;[list[index], list[target]] = [list[target], list[index]]
  set(field.key, list)
}

function addLabel(): string {
  return props.translate?.('blockEditor.addRow', 'Añadir') ?? 'Añadir'
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

      <!-- Grupo: objeto {subclave: valor} con los subcampos anidados -->
      <fieldset v-else-if="field.type === 'group'" class="schema-fields__group">
        <legend class="form-field__label">{{ label(field) }}</legend>
        <SchemaFields
          :fields="field.fields ?? []"
          :model-value="groupValue(field)"
          :locales="locales"
          :api="api"
          :icons="icons"
          :rich-labels="richLabels"
          :translate="translate"
          @update:model-value="(v) => set(field.key, v)"
        />
      </fieldset>

      <!-- Repeater: filas con los mismos subcampos (añadir/quitar/reordenar) -->
      <div v-else-if="field.type === 'repeater'" class="schema-fields__repeater">
        <span class="form-field__label">{{ label(field) }}</span>
        <fieldset v-for="(row, index) in rows(field)" :key="index" class="schema-fields__row">
          <div class="schema-fields__row-bar">
            <span class="schema-fields__row-index">{{ index + 1 }}</span>
            <IconButton
              v-if="index > 0"
              variant="neutral"
              :title="'↑'"
              @click="moveRow(field, index, -1)"
              ><ArrowUp :size="14"
            /></IconButton>
            <IconButton
              v-if="index < rows(field).length - 1"
              variant="neutral"
              :title="'↓'"
              @click="moveRow(field, index, 1)"
              ><ArrowDown :size="14"
            /></IconButton>
            <IconButton variant="danger" :title="'×'" @click="removeRow(field, index)"
              ><X :size="14"
            /></IconButton>
          </div>
          <SchemaFields
            :fields="field.fields ?? []"
            :model-value="row"
            :locales="locales"
            :api="api"
            :icons="icons"
            :rich-labels="richLabels"
            :translate="translate"
            @update:model-value="(v) => setRow(field, index, v)"
          />
        </fieldset>
        <BaseButton
          v-if="field.max === null || rows(field).length < field.max"
          variant="text"
          @click="addRow(field)"
        >
          <template #icon><Plus :size="14" /></template>
          {{ addLabel() }}
        </BaseButton>
      </div>

      <!-- Referencia a una entidad del juego: buscador sobre su endpoint -->
      <EntityRefSelect
        v-else-if="field.type === 'entity' && field.options_url"
        :model-value="(modelValue[field.key] as number) ?? null"
        :label="label(field)"
        :options-url="field.options_url"
        :api="api"
        @update:model-value="(v) => set(field.key, v)"
      />

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
