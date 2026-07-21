<script setup lang="ts">
import { computed } from 'vue'
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
    /** Cliente de la API del admin (opciones de los campos `entity`). */
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

// Convenciones genéricas de agrupado (doc 03 ampliado):
//
// 1) Alineaciones junto a su campo: un select `<base>_align` cuyo campo
//    `<base>` (o `<base>_text`, caso de `button_align`/`button_text`) esté
//    en la MISMA lista se pinta junto a él, en una fila de dos columnas
//    (PageBlocks inyecta title_align/subtitle_align —comunes del motor—
//    junto a title/subtitle antes de pasar la lista aquí). El `align`
//    general del bloque no tiene campo objetivo ("align" no acaba en
//    "_align" con un prefijo no vacío) y se queda fuera de esta pareja.
// 2) Imagen a dos columnas: el campo `image` con alguno de sus ajustes
//    (`image_position`, `image_fit`, `image_columns`) en la misma lista se
//    agrupa con ellos — columna 1 la imagen, columna 2 los ajustes
//    apilados.
const IMAGE_SETTING_KEYS = ['image_position', 'image_fit', 'image_columns']

interface LayoutItem {
  field: FieldSchema
  /** Select `<base>_align` que viaja junto a `field` en la misma fila. */
  align?: FieldSchema
  /** Ajustes de imagen (image_position/fit/columns) que viajan con `field`. */
  imageSettings?: FieldSchema[]
}

const layout = computed<LayoutItem[]>(() => {
  const byKey = new Map(props.fields.map((f) => [f.key, f]))
  const alignOf = new Map<string, FieldSchema>() // clave del campo "base" => su _align
  const consumed = new Set<string>() // claves ya emparejadas con otro campo

  for (const field of props.fields) {
    if (field.type !== 'select' || !field.key.endsWith('_align')) continue
    const base = field.key.slice(0, -'_align'.length)
    if (!base) continue // el "align" general no tiene prefijo
    const baseKey = byKey.has(base) ? base : byKey.has(`${base}_text`) ? `${base}_text` : null
    if (!baseKey) continue
    alignOf.set(baseKey, field)
    consumed.add(field.key)
  }

  const imageSettingsOf = new Map<string, FieldSchema[]>()
  for (const field of props.fields) {
    if (field.key !== 'image' || field.type !== 'image') continue
    const settings = IMAGE_SETTING_KEYS.map((key) => byKey.get(key)).filter(
      (f): f is FieldSchema => !!f,
    )
    if (!settings.length) continue
    imageSettingsOf.set(field.key, settings)
    settings.forEach((f) => consumed.add(f.key))
  }

  return props.fields
    .filter((f) => !consumed.has(f.key))
    .map((f) => ({
      field: f,
      align: alignOf.get(f.key),
      imageSettings: imageSettingsOf.get(f.key),
    }))
})

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

// --- Imágenes DIFERIDAS: elegir fichero deja el File en el modelo (URL al
// guardar: PageBlocks los sube en el submit con uploadPendingImages) ---

/** File pendiente de un campo imagen no traducible (si lo hay). */
function imageFile(field: FieldSchema): File | null {
  const value = props.modelValue[field.key]
  return value instanceof File ? value : null
}

/** URL guardada de un campo imagen no traducible (si la hay). */
function imageCurrentUrl(field: FieldSchema): string | null {
  const value = props.modelValue[field.key]
  return typeof value === 'string' && value ? value : null
}

/** Mapa locale => URL guardada o File pendiente de una imagen traducible. */
function imageTranslations(field: FieldSchema): Record<string, string | File> {
  const value = props.modelValue[field.key]
  if (!value || typeof value !== 'object') return {}
  return value as Record<string, string | File>
}

function selectOptions(field: FieldSchema): SelectOption[] {
  return Object.entries(field.options ?? {}).map(([value, text]) => ({
    value,
    label: props.translate?.(`blockOptions.${field.key}.${value}`, text) ?? text,
  }))
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
    <template v-for="{ field, align, imageSettings } in layout" :key="field.key">
      <div
        class="schema-fields__field"
        :class="{
          'schema-fields__field--row': !!align,
          'schema-fields__field--image-group': !!imageSettings,
        }"
      >
        <div class="schema-fields__field-main">
          <!-- Traducibles: text / textarea / richtext van al TranslatableInput -->
          <TranslatableInput
            v-if="field.translatable && ['text', 'textarea', 'richtext'].includes(field.type)"
            :model-value="translations(field)"
            :locales="locales"
            :label="label(field)"
            :required="field.required"
            :type="
              field.type === 'richtext'
                ? 'wysiwyg'
                : field.type === 'textarea'
                  ? 'textarea'
                  : 'text'
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

          <!-- Imagen traducible: una por locale (fallback al default al renderizar).
           Diferida: el mapa lleva File pendientes hasta el guardar -->
          <TranslatableImage
            v-else-if="field.type === 'image' && field.translatable"
            :model-value="imageTranslations(field)"
            :locales="locales"
            :label="label(field)"
            :required="field.required"
            @update:model-value="(v) => set(field.key, v)"
          />

          <div v-else-if="field.type === 'image'" class="schema-fields__image">
            <span class="form-field__label">{{ label(field) }}</span>
            <ImageUpload
              :model-value="imageFile(field)"
              :current-url="imageCurrentUrl(field)"
              @update:model-value="(f: File | null) => set(field.key, f)"
              @remove="set(field.key, null)"
            />
          </div>
        </div>

        <!-- Alineación del campo (title_align/subtitle_align/author_align/…),
         en la misma fila que su campo (columna de ancho contenido). -->
        <BaseSelect
          v-if="align"
          class="schema-fields__field-align"
          :model-value="(modelValue[align.key] as string) ?? (align.default as string) ?? ''"
          :label="label(align)"
          :options="selectOptions(align)"
          @update:model-value="(v) => set(align!.key, v)"
        />

        <!-- Ajustes de la imagen (image_position/image_fit/image_columns),
         apilados en la segunda columna junto al input de imagen. -->
        <div v-if="imageSettings" class="schema-fields__field-image-settings">
          <BaseSelect
            v-for="setting in imageSettings"
            :key="setting.key"
            :model-value="(modelValue[setting.key] as string) ?? (setting.default as string) ?? ''"
            :label="label(setting)"
            :options="selectOptions(setting)"
            @update:model-value="(v) => set(setting.key, v)"
          />
        </div>
      </div>
    </template>
  </div>
</template>
