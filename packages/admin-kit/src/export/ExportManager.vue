<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import type { AxiosInstance } from 'axios'
import { FileJson } from '@lucide/vue'
import { BaseButton, BaseCheckbox, BaseSelect, useToast } from '@edc-motor/ui'

// Exportación a JSON (doc 12) de los modelos que el juego registra como
// exportables (Exports::register): formulario con el modelo, los idiomas y
// los campos a incluir, agrupados (datos, textos, relaciones…). Las
// relaciones salen DESPLEGADAS en el JSON (nombre y datos relevantes, no
// ids): lo decide cada modelo (exportItem). El fichero baja como descarga
// autenticada (blob con el token). Solo administradores (gate export-data).
// Agnóstico de i18n (DC-29): textos por prop, defaults en castellano; las
// etiquetas de modelos, grupos y campos las pone el juego (por clave).

export interface ExportManagerLabels {
  hint: string
  model: string
  locales: string
  fields: string
  all: string
  none: string
  export: string
  exporting: string
  needLocale: string
  needField: string
  done: string
  error: string
  loadError: string
  loading: string
}

const defaultLabels: ExportManagerLabels = {
  hint: 'Descarga en JSON el modelo elegido con los campos e idiomas que marques. Las relaciones salen desplegadas con su nombre y sus datos, no con su id. Con un solo idioma los textos van como cadena; con varios, como mapa por idioma.',
  model: 'Modelo',
  locales: 'Idiomas',
  fields: 'Campos',
  all: 'Todos',
  none: 'Ninguno',
  export: 'Exportar JSON',
  exporting: 'Exportando…',
  needLocale: 'Elige al menos un idioma.',
  needField: 'Elige al menos un campo.',
  done: 'JSON descargado.',
  error: 'No se pudo exportar.',
  loadError: 'No se han podido cargar los datos.',
  loading: 'Cargando…',
}

interface ExportField {
  key: string
  group: string
}
interface ExportModel {
  key: string
  fields: ExportField[]
}
interface ExportOptions {
  models: ExportModel[]
  locales: { code: string; name: string }[]
  default_locale: string
}

const props = withDefaults(
  defineProps<{
    api: AxiosInstance
    labels?: Partial<ExportManagerLabels>
    /** Nombre de cada modelo registrado, por clave (fallback: la clave). */
    modelLabels?: Record<string, string>
    /** Nombre de cada grupo de campos, por clave (fallback: la clave). */
    groupLabels?: Record<string, string>
    /** Etiqueta de cada campo, por modelo y clave (fallback: la clave). */
    fieldLabels?: Record<string, Record<string, string>>
  }>(),
  { labels: () => ({}), modelLabels: () => ({}), groupLabels: () => ({}), fieldLabels: () => ({}) },
)

const L = computed<ExportManagerLabels>(() => ({ ...defaultLabels, ...props.labels }))
const toast = useToast()

const options = ref<ExportOptions | null>(null)
const loading = ref(true)
const exporting = ref(false)

const model = ref('')
const locales = ref<string[]>([])
const fields = ref<string[]>([])

const modelOptions = computed(() =>
  (options.value?.models ?? []).map((m) => ({
    value: m.key,
    label: props.modelLabels[m.key] ?? m.key,
  })),
)

const currentFields = computed(
  () => options.value?.models.find((m) => m.key === model.value)?.fields ?? [],
)

// Campos por grupo, en el orden en que aparecen en el catálogo del modelo.
const groups = computed(() => {
  const order: string[] = []
  for (const field of currentFields.value) if (!order.includes(field.group)) order.push(field.group)
  return order.map((group) => ({
    key: group,
    label: props.groupLabels[group] ?? group,
    fields: currentFields.value.filter((f) => f.group === group),
  }))
})

function fieldLabel(key: string): string {
  return props.fieldLabels[model.value]?.[key] ?? key
}

function hasLocale(code: string) {
  return locales.value.includes(code)
}
function toggleLocale(code: string, on: boolean) {
  locales.value = on
    ? [...new Set([...locales.value, code])]
    : locales.value.filter((c) => c !== code)
}

function hasField(key: string) {
  return fields.value.includes(key)
}
function toggleField(key: string, on: boolean) {
  fields.value = on ? [...new Set([...fields.value, key])] : fields.value.filter((k) => k !== key)
}
function selectAll() {
  fields.value = currentFields.value.map((f) => f.key)
}
function selectNone() {
  fields.value = []
}

// Al cambiar de modelo, todos sus campos marcados (lo normal es querer el
// JSON completo y quitar lo que sobre).
watch(model, selectAll)

async function load() {
  loading.value = true
  try {
    const { data } = await props.api.get('/admin/export/options')
    options.value = data.data
    locales.value = [data.data.default_locale]
    model.value = data.data.models[0]?.key ?? ''
    selectAll()
  } catch {
    toast.danger(L.value.loadError)
  } finally {
    loading.value = false
  }
}

async function exportJson() {
  if (!locales.value.length) {
    toast.danger(L.value.needLocale)
    return
  }
  if (!fields.value.length) {
    toast.danger(L.value.needField)
    return
  }
  exporting.value = true
  try {
    const { data, headers } = await props.api.post(
      `/admin/export/${model.value}`,
      { locales: locales.value, fields: fields.value },
      { responseType: 'blob' },
    )
    // Nombre del fichero: el del Content-Disposition (modelo-fecha.json).
    const match = /filename="([^"]+)"/.exec(String(headers['content-disposition'] ?? ''))
    const url = URL.createObjectURL(data)
    const link = document.createElement('a')
    link.href = url
    link.download = match?.[1] ?? `${model.value}.json`
    link.click()
    URL.revokeObjectURL(url)
    toast.success(L.value.done)
  } catch {
    toast.danger(L.value.error)
  } finally {
    exporting.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="export-manager">
    <p class="export-manager__hint">{{ L.hint }}</p>

    <p v-if="loading" class="export-manager__loading" role="status">{{ L.loading }}</p>

    <template v-else-if="options && options.models.length">
      <div class="export-manager__cards">
        <!-- Modelo + idiomas -->
        <section class="export-manager__card">
          <BaseSelect v-model="model" :label="L.model" :options="modelOptions" />

          <fieldset class="export-manager__fieldset">
            <legend>{{ L.locales }}</legend>
            <BaseCheckbox
              v-for="locale in options.locales"
              :key="locale.code"
              :model-value="hasLocale(locale.code)"
              :label="`${locale.name} (${locale.code.toUpperCase()})`"
              @update:model-value="(on: boolean) => toggleLocale(locale.code, on)"
            />
          </fieldset>
        </section>

        <!-- Campos, por grupo -->
        <section class="export-manager__card">
          <div class="export-manager__fields-head">
            <h2>{{ L.fields }}</h2>
            <div class="export-manager__quick">
              <button type="button" class="export-manager__link" @click="selectAll">
                {{ L.all }}
              </button>
              <button type="button" class="export-manager__link" @click="selectNone">
                {{ L.none }}
              </button>
            </div>
          </div>
          <fieldset v-for="group in groups" :key="group.key" class="export-manager__fieldset">
            <legend>{{ group.label }}</legend>
            <BaseCheckbox
              v-for="field in group.fields"
              :key="field.key"
              :model-value="hasField(field.key)"
              :label="fieldLabel(field.key)"
              @update:model-value="(on: boolean) => toggleField(field.key, on)"
            />
          </fieldset>
        </section>
      </div>

      <div class="export-manager__actions">
        <BaseButton :disabled="exporting" @click="exportJson">
          <template #icon><FileJson :size="16" /></template>
          {{ exporting ? L.exporting : L.export }}
        </BaseButton>
      </div>
    </template>
  </div>
</template>
