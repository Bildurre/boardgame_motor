<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import { GripVertical, List, Plus, Printer, SquarePen, Trash2 } from '@lucide/vue'
import { VueDraggable } from 'vue-draggable-plus'
import {
  BaseButton,
  BaseCheckbox,
  BaseSelect,
  EditModal,
  useConfirm,
  useToast,
  type RichIcon,
  type RichTextLabels,
} from '@edc-motor/ui'
import SchemaFields, { type FieldSchema } from './SchemaFields.vue'
import { collectImageUrls, deleteContentImage, uploadPendingImages } from './deferredImages'
import { useRightSidebar } from '../composables/useRightSidebar'
import { useCardDeselect } from '../composables/useCardDeselect'

// Gestor de bloques de una página (doc 03): paleta de tipos (motor + juego),
// lista reordenable con drag (DC-17) y modal de edición GENERADO desde el
// esquema de campos del tipo. Añadir un tipo de bloque no toca este código.
// Agnóstico de i18n (DC-29): textos por prop; gancho translate para nombres.

export interface PageBlocksLabels {
  add: string
  edit: string
  delete: string
  save: string
  cancel: string
  empty: string
  printable: string
  printableShort: string
  indexable: string
  indexableShort: string
  yes: string
  no: string
  common: string
  confirmDelete: string
  error: string
  panelTitle: string
  panelEmpty: string
  panelContent: string
  parent: string
  parentNone: string
}

const defaultLabels: PageBlocksLabels = {
  add: 'Añadir bloque',
  edit: 'Editar bloque',
  delete: 'Borrar',
  save: 'Guardar',
  cancel: 'Cancelar',
  empty: 'La página aún no tiene bloques.',
  printable: 'Entra en el PDF de la página',
  printableShort: 'PDF',
  indexable: 'Aparece en el índice',
  indexableShort: 'Índice',
  yes: 'Sí',
  no: 'No',
  common: 'Ajustes comunes',
  confirmDelete: '¿Borrar este bloque?',
  error: 'No se ha podido completar la acción.',
  panelTitle: 'Bloque',
  panelEmpty: 'Selecciona un bloque para ver sus acciones.',
  panelContent: 'Contenido',
  parent: 'Bloque padre (índices indentados)',
  parentNone: '— Ninguno —',
}

const props = withDefaults(
  defineProps<{
    api: AxiosInstance
    pageId: number
    locales: { code: string; name: string }[]
    icons?: RichIcon[]
    richLabels?: Partial<RichTextLabels>
    labels?: Partial<PageBlocksLabels>
    /** Localiza nombres de tipos y etiquetas de campos: (clave, fallback) => texto. */
    translate?: (key: string, fallback: string) => string
  }>(),
  { icons: () => [], richLabels: () => ({}), labels: () => ({}), translate: undefined },
)

const L = reactive({ ...defaultLabels, ...props.labels }) as PageBlocksLabels

const toast = useToast()
const { confirm } = useConfirm()

// El detalle/acciones del bloque seleccionado viven en el panel derecho.
const sidebar = useRightSidebar()
sidebar.useRegister(L.panelTitle)

interface BlockTypeSchema {
  key: string
  name: string
  icon: string
  category: string
  fields: FieldSchema[]
  common: FieldSchema[]
}

interface BlockRow {
  id: number
  type: string
  order: number
  parent_id: number | null
  settings: Record<string, unknown>
  is_printable: boolean
  is_indexable: boolean
}

const types = ref<BlockTypeSchema[]>([])
const blocks = ref<BlockRow[]>([])
const selectedId = ref<number | null>(null)
const selected = computed(() => blocks.value.find((b) => b.id === selectedId.value) ?? null)

/** Toda la fila selecciona, salvo sus controles interiores y el grip. */
function selectBlock(block: BlockRow, event: MouseEvent) {
  const target = event.target as HTMLElement | null
  if (target?.closest('button, a, input, label, .page-blocks__grip')) return
  selectedId.value = block.id
  sidebar.reveal()
}

// Click en la zona vacía del contenido (fuera de una fila o control):
// deselecciona, como en los index (patrón useCardDeselect).
useCardDeselect(() => (selectedId.value = null), '.page-blocks__item, .page-blocks__menu')

/** Acción rápida del panel: alterna un flag sin abrir el modal. */
async function toggleFlag(flag: 'is_printable' | 'is_indexable', value: boolean) {
  if (!selected.value) return
  try {
    await props.api.put(`/admin/blocks/${selected.value.id}`, { [flag]: value })
    selected.value[flag] = value
  } catch {
    toast.danger(L.error)
  }
}
const busy = ref(false)
const paletteOpen = ref(false)

// La paleta de "añadir bloque" se cierra con Escape y clicando fuera (los
// clicks dentro — el propio botón incluido — no la tocan).
function onDocumentClick(event: MouseEvent) {
  if (!paletteOpen.value) return
  const target = event.target instanceof Element ? event.target : null
  if (target?.closest('.page-blocks__palette')) return
  paletteOpen.value = false
}
function onDocumentKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && paletteOpen.value) paletteOpen.value = false
}
onMounted(() => {
  document.addEventListener('click', onDocumentClick)
  document.addEventListener('keydown', onDocumentKeydown)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onDocumentKeydown)
})

// Modal de edición (crear/editar comparten formulario).
const modalOpen = ref(false)
const editing = ref<BlockRow | null>(null)
const modalType = ref<BlockTypeSchema | null>(null)
const form = ref<Record<string, unknown>>({})
const formPrintable = ref(true)
const formIndexable = ref(true)
const formParent = ref<number | null>(null)

// Padres elegibles: bloques raíz de la página (un solo nivel de anidado),
// nunca uno mismo.
const parentOptions = computed(() => {
  const self = editing.value?.id
  return blocks.value
    .filter((b) => !b.parent_id && b.id !== self)
    .map((b) => ({ value: String(b.id), label: `${typeName(b.type)} — ${summary(b) || b.id}` }))
})

function typeName(key: string): string {
  const type = types.value.find((t) => t.key === key)
  const fallback = type?.name ?? key
  return props.translate?.(`blockTypes.${key}`, fallback) ?? fallback
}

/** Resumen del bloque en la lista: el primer texto traducible con valor. */
function summary(block: BlockRow): string {
  const type = types.value.find((t) => t.key === block.type)
  for (const field of type?.fields ?? []) {
    if (!['text', 'textarea', 'richtext'].includes(field.type)) continue
    const value = block.settings?.[field.key]
    if (field.translatable && value && typeof value === 'object') {
      const text = Object.values(value as Record<string, string>).find(Boolean)
      if (text) return text.replace(/<[^>]*>/g, '').slice(0, 80)
    }
  }
  return ''
}

/** URL de un campo imagen (traducible o no): la primera con valor. */
function imageUrl(field: FieldSchema, block: BlockRow): string {
  const raw = block.settings?.[field.key]
  if (raw && typeof raw === 'object') {
    return Object.values(raw as Record<string, string>).find(Boolean) ?? ''
  }
  return raw ? String(raw) : ''
}

/** Etiqueta de un campo del esquema (misma convención que SchemaFields). */
function fieldLabel(field: FieldSchema): string {
  return props.translate?.(`blockFields.${field.key}`, field.label) ?? field.label
}

/** Valor legible de un campo para el panel: texto plano, truncado por CSS. */
function fieldValue(field: FieldSchema, block: BlockRow): string {
  const raw = block.settings?.[field.key]
  if (raw === null || raw === undefined || raw === '') return ''
  if (field.translatable && typeof raw === 'object') {
    const text = Object.values(raw as Record<string, string>).find(Boolean) ?? ''
    return text
      .replace(/<[^>]*>/g, ' ')
      .replace(/\s+/g, ' ')
      .trim()
  }
  if (field.type === 'boolean') return raw ? '✓' : '✗'
  if (field.type === 'select' && field.options) {
    const text = field.options[String(raw)] ?? String(raw)
    return props.translate?.(`blockOptions.${field.key}.${String(raw)}`, text) ?? text
  }
  // Anidados del DSL: un resumen, no el volcado del objeto.
  if (field.type === 'repeater' && Array.isArray(raw)) {
    return `× ${raw.length}`
  }
  if (field.type === 'group' && typeof raw === 'object') {
    return `{ ${Object.keys(raw as object).join(', ')} }`
  }
  if (field.type === 'entity') return `#${String(raw)}`
  return String(raw)
}

/** Campos del bloque seleccionado con valor (la imagen se pinta aparte). */
const selectedFields = computed(() => {
  if (!selected.value) return []
  const type = types.value.find((t) => t.key === selected.value?.type)
  return (type?.fields ?? [])
    .map((field) => ({
      field,
      value:
        field.type === 'image'
          ? imageUrl(field, selected.value!)
          : fieldValue(field, selected.value!),
    }))
    .filter((entry) => entry.value)
})

/** Hijos justo debajo de su padre (en su orden relativo): anidado visible. */
function arrange(list: BlockRow[]): BlockRow[] {
  const parents = list.filter((b) => !b.parent_id)
  const out: BlockRow[] = []
  for (const parent of parents) {
    out.push(parent, ...list.filter((b) => b.parent_id === parent.id))
  }
  // Huérfanos (padre borrado en otra sesión): al final, sin perderse.
  out.push(...list.filter((b) => !out.includes(b)))
  return out
}

async function load() {
  try {
    const [palette, list] = await Promise.all([
      props.api.get('/admin/block-types'),
      props.api.get(`/admin/pages/${props.pageId}/blocks`),
    ])
    types.value = palette.data.data
    blocks.value = arrange(list.data.data)
  } catch {
    toast.danger(L.error)
  }
}

function openCreate(type: BlockTypeSchema) {
  paletteOpen.value = false
  editing.value = null
  modalType.value = type
  const defaults: Record<string, unknown> = {}
  for (const field of [...type.fields, ...type.common]) {
    if (field.default !== null && field.default !== undefined) defaults[field.key] = field.default
  }
  form.value = defaults
  formPrintable.value = true
  formIndexable.value = true
  formParent.value = null
  modalOpen.value = true
}

function openEdit(block: BlockRow) {
  editing.value = block
  modalType.value = types.value.find((t) => t.key === block.type) ?? null
  form.value = { ...(block.settings ?? {}) }
  formPrintable.value = block.is_printable
  formIndexable.value = block.is_indexable
  formParent.value = block.parent_id
  modalOpen.value = true
}

// Guardado DIFERIDO de las imágenes del bloque: los inputs dejan el File en
// `form` y NADA viaja hasta aquí — se suben los pendientes, se persiste el
// bloque con las URLs resueltas y solo entonces se borran del disco las
// imágenes que el bloque ya no referencia. Si algo falla, las subidas nuevas
// se deshacen (el form conserva los File para reintentar) y cancelar el
// modal no deja rastro en el servidor.
async function save() {
  if (!modalType.value) return
  busy.value = true
  const fields = [...modalType.value.fields, ...modalType.value.common]
  const uploaded: string[] = []
  try {
    const settings = await uploadPendingImages(props.api, fields, form.value, uploaded)
    const payload = {
      type: modalType.value.key,
      settings,
      is_printable: formPrintable.value,
      is_indexable: formIndexable.value,
      parent_id: formParent.value,
    }
    if (editing.value) {
      await props.api.put(`/admin/blocks/${editing.value.id}`, payload)
    } else {
      await props.api.post(`/admin/pages/${props.pageId}/blocks`, payload)
    }
    // Guardado en firme: fuera del disco lo que el bloque ya no referencia
    // (sustituidas y quitadas), robusto ante filas de repeater reordenadas.
    const kept = new Set(collectImageUrls(fields, settings))
    const before = collectImageUrls(fields, editing.value?.settings ?? {})
    await Promise.all(
      before.filter((url) => !kept.has(url)).map((url) => deleteContentImage(props.api, url)),
    )
    modalOpen.value = false
    await load()
  } catch {
    await Promise.all(uploaded.map((url) => deleteContentImage(props.api, url)))
    toast.danger(L.error)
  } finally {
    busy.value = false
  }
}

async function remove(block: BlockRow) {
  const ok = await confirm({
    message: L.confirmDelete,
    confirmLabel: L.delete,
    cancelLabel: L.cancel,
    variant: 'danger',
  })
  if (!ok) return
  try {
    await props.api.delete(`/admin/blocks/${block.id}`)
    if (selectedId.value === block.id) selectedId.value = null
    await load()
  } catch {
    toast.danger(L.error)
  }
}

/** El drag reordena en cliente; los hijos se recolocan bajo su padre y se
 *  persiste la lista de ids resultante. */
async function persistOrder() {
  blocks.value = arrange(blocks.value)
  try {
    await props.api.post(`/admin/pages/${props.pageId}/blocks/reorder`, {
      ids: blocks.value.map((b) => b.id),
    })
  } catch {
    toast.danger(L.error)
    await load()
  }
}

const presentation = computed(() => types.value.filter((t) => t.category === 'presentation'))
const dataTypes = computed(() => types.value.filter((t) => t.category !== 'presentation'))

onMounted(load)
defineExpose({ reload: load })
</script>

<template>
  <div class="page-blocks">
    <div class="page-blocks__bar">
      <div class="page-blocks__palette">
        <BaseButton :disabled="busy" @click="paletteOpen = !paletteOpen">
          <template #icon><Plus :size="16" /></template>
          {{ L.add }}
        </BaseButton>
        <div v-if="paletteOpen" class="page-blocks__menu">
          <button
            v-for="type in [...presentation, ...dataTypes]"
            :key="type.key"
            type="button"
            class="page-blocks__menu-item"
            :class="{ 'is-data': type.category !== 'presentation' }"
            @click="openCreate(type)"
          >
            {{ typeName(type.key) }}
          </button>
        </div>
      </div>
    </div>

    <p v-if="!blocks.length" class="page-blocks__empty">{{ L.empty }}</p>

    <VueDraggable
      v-model="blocks"
      class="page-blocks__list"
      handle=".page-blocks__grip"
      :animation="150"
      @end="persistOrder"
    >
      <article
        v-for="block in blocks"
        :key="block.id"
        class="page-blocks__item"
        :class="{ 'is-active': selectedId === block.id, 'is-child': block.parent_id }"
        @click="(e) => selectBlock(block, e)"
      >
        <span class="page-blocks__grip"><GripVertical :size="16" /></span>
        <button
          type="button"
          class="page-blocks__edit"
          :title="L.edit"
          :aria-label="L.edit"
          @click="openEdit(block)"
        >
          <SquarePen :size="15" />
        </button>
        <span class="page-blocks__type">{{ typeName(block.type) }}</span>
        <span class="page-blocks__summary">{{ summary(block) }}</span>
        <span class="page-blocks__flags">
          <span v-if="block.is_printable" class="chip is-ok">{{ L.printableShort }}</span>
          <span v-if="block.is_indexable" class="chip">{{ L.indexableShort }}</span>
        </span>
      </article>
    </VueDraggable>

    <!-- Modal generado desde el esquema del tipo -->
    <EditModal
      v-model="modalOpen"
      :title="modalType ? typeName(modalType.key) : ''"
      :submit-label="L.save"
      :cancel-label="L.cancel"
      :loading="busy"
      @submit="save"
    >
      <template v-if="modalType">
        <SchemaFields
          v-model="form"
          :fields="modalType.fields"
          :locales="locales"
          :api="api"
          :icons="icons"
          :rich-labels="richLabels"
          :translate="translate"
        />

        <details class="page-blocks__common">
          <summary>{{ L.common }}</summary>
          <SchemaFields
            v-model="form"
            :fields="modalType.common"
            :locales="locales"
            :api="api"
            :translate="translate"
          />
          <BaseCheckbox v-model="formPrintable" :label="L.printable" />
          <BaseCheckbox v-model="formIndexable" :label="L.indexable" />
          <BaseSelect
            :model-value="formParent === null ? '' : String(formParent)"
            :label="L.parent"
            :options="[{ value: '', label: L.parentNone }, ...parentOptions]"
            @update:model-value="(v: string) => (formParent = v ? Number(v) : null)"
          />
        </details>
      </template>
    </EditModal>

    <!-- Acciones del bloque seleccionado, en el panel derecho (patrón kontuan) -->
    <Teleport defer to="#right-sidebar-target">
      <div class="manager-panel">
        <!-- Sin bloque seleccionado, la vista puede poner su propio panel
             (p. ej. las acciones de la página) -->
        <slot v-if="!selected" name="panel-default">
          <p class="manager-panel__empty">{{ L.panelEmpty }}</p>
        </slot>
        <template v-else>
          <p class="manager-panel__kicker">{{ L.panelTitle }}</p>

          <!-- Acciones PRIMERO (los interruptores arriba); después,
               secciones separadas (patrón panel) -->
          <div class="manager-detail__actions">
            <BaseButton
              variant="success"
              :class="selected.is_printable ? 'is-on' : 'is-off'"
              :aria-pressed="selected.is_printable"
              :disabled="busy"
              @click="toggleFlag('is_printable', !selected.is_printable)"
            >
              <template #icon><Printer :size="14" /></template>
              {{ L.printableShort }}
            </BaseButton>
            <BaseButton
              :class="selected.is_indexable ? 'is-on' : 'is-off'"
              :aria-pressed="selected.is_indexable"
              :disabled="busy"
              @click="toggleFlag('is_indexable', !selected.is_indexable)"
            >
              <template #icon><List :size="14" /></template>
              {{ L.indexableShort }}
            </BaseButton>
            <BaseButton variant="info" :disabled="busy" @click="openEdit(selected)">
              <template #icon><SquarePen :size="14" /></template>
              {{ L.edit }}
            </BaseButton>
            <BaseButton variant="danger" :disabled="busy" @click="remove(selected)">
              <template #icon><Trash2 :size="14" /></template>
              {{ L.delete }}
            </BaseButton>
          </div>

          <hr class="manager-panel__divider" />

          <!-- El título del panel es el TIPO del bloque (el contenido va en
               su sección, abajo) -->
          <h3 class="manager-detail__title">{{ typeName(selected.type) }}</h3>

          <!-- Estado de los interruptores, en texto -->
          <p class="manager-detail__meta">
            <strong>{{ L.printable }}</strong> {{ selected.is_printable ? L.yes : L.no }}
          </p>
          <p class="manager-detail__meta">
            <strong>{{ L.indexable }}</strong> {{ selected.is_indexable ? L.yes : L.no }}
          </p>

          <!-- Contenido: cada campo del bloque con su valor (truncado) -->
          <hr v-if="selectedFields.length" class="manager-panel__divider" />
          <div v-if="selectedFields.length" class="manager-detail">
            <p class="manager-panel__kicker">{{ L.panelContent }}</p>
            <dl class="manager-detail__fields">
              <div
                v-for="entry in selectedFields"
                :key="entry.field.key"
                class="manager-detail__field"
              >
                <dt>{{ fieldLabel(entry.field) }}</dt>
                <dd v-if="entry.field.type === 'image'">
                  <img
                    :src="entry.value"
                    :alt="fieldLabel(entry.field)"
                    class="manager-detail__thumb"
                  />
                </dd>
                <dd v-else>{{ entry.value }}</dd>
              </div>
            </dl>
          </div>
        </template>
      </div>
    </Teleport>
  </div>
</template>
