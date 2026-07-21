<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import { GripVertical, List, Plus, Printer, SquarePen, Trash2 } from '@lucide/vue'
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
// lista reordenable con drag & drop NATIVO (HTML5, sin dependencias) y modal
// de edición GENERADO desde el esquema de campos del tipo. Añadir un tipo de
// bloque no toca este código. Anidado en VARIOS niveles, sin límite (solo se
// prohíben los ciclos): el drag persiste al soltar (sin botón Guardar, a
// diferencia del MenuManager) — soltar entre filas reordena
// (`POST .../blocks/reorder`, la lista aplanada en preorden); soltar ENCIMA
// de cualquier fila anida el bloque bajo ella (con sus propios
// descendientes, que se mueven en bloque) vía `PUT /admin/blocks/{id}`,
// mismas reglas que el select "Bloque padre": nunca uno mismo ni un
// descendiente propio. Agnóstico de i18n (DC-29): textos por prop; gancho
// translate para nombres; `displayLocale` (idioma actual del admin) para
// los textos traducibles del resumen/campos.

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
  common: 'General',
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
    /** Idioma actual del admin (vue-i18n): textos traducibles en él, con
     *  fallback al primer valor no vacío (patrón `firstText` del AppHeader). */
    displayLocale: string
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

// --- Formulario del modal: General ARRIBA + alineaciones junto a su campo -
// `title_align`/`subtitle_align` son campos COMUNES del motor pero
// title/subtitle son del TIPO: se inyectan aquí junto a su campo (SchemaFields
// ya sabe emparejar un `<base>_align` con `<base>` con que ambos estén en la
// misma lista) y se retiran de la sección general (author_align→author,
// label_align→label y button_align→button_text ya viven en el propio tipo,
// SchemaFields los empareja sin ayuda). El `align` general no tiene campo
// objetivo: se queda en General, junto al color de fondo.
function commonField(key: string): FieldSchema | undefined {
  return modalType.value?.common.find((f) => f.key === key)
}

const typeFieldsWithAligns = computed<FieldSchema[]>(() => {
  if (!modalType.value) return []
  const injected = (['title_align', 'subtitle_align'] as const)
    .map((key) => commonField(key))
    .filter((f): f is FieldSchema => !!f)
  return [...modalType.value.fields, ...injected]
})

const generalWidthFields = computed<FieldSchema[]>(() => {
  const field = commonField('width')
  return field ? [field] : []
})
const generalBackgroundFields = computed<FieldSchema[]>(() => {
  const field = commonField('background')
  return field ? [field] : []
})
const generalAlignFields = computed<FieldSchema[]>(() => {
  const field = commonField('align')
  return field ? [field] : []
})

// Padres elegibles: CUALQUIER bloque de la página (sin límite de niveles),
// salvo uno mismo o uno de sus propios descendientes (crearía un ciclo).
// Etiqueta con prefijo de rayas según su profundidad, para ver la jerarquía
// en un <select> nativo (no admite sangría real por opción).
const parentOptions = computed(() => {
  const self = editing.value?.id
  return blocks.value
    .filter((b) => b.id !== self && !(self !== undefined && isDescendant(b.id, self)))
    .map((b) => ({
      value: String(b.id),
      label: `${'— '.repeat(depthOf(b))}${typeName(b.type)} — ${summary(b) || b.id}`,
    }))
})

function typeName(key: string): string {
  const type = types.value.find((t) => t.key === key)
  const fallback = type?.name ?? key
  return props.translate?.(`blockTypes.${key}`, fallback) ?? fallback
}

/** Texto en `displayLocale`, con fallback al primer valor no vacío. */
function displayText(map: Record<string, string> | null | undefined): string {
  if (!map) return ''
  return map[props.displayLocale] || Object.values(map).find(Boolean) || ''
}

/** Resumen del bloque en la lista: el primer texto traducible con valor. */
function summary(block: BlockRow): string {
  const type = types.value.find((t) => t.key === block.type)
  for (const field of type?.fields ?? []) {
    if (!['text', 'textarea', 'richtext'].includes(field.type)) continue
    const value = block.settings?.[field.key]
    if (field.translatable && value && typeof value === 'object') {
      const text = displayText(value as Record<string, string>)
      if (text) return text.replace(/<[^>]*>/g, '').slice(0, 80)
    }
  }
  return ''
}

/** URL de un campo imagen (traducible o no): la del locale actual. */
function imageUrl(field: FieldSchema, block: BlockRow): string {
  const raw = block.settings?.[field.key]
  if (raw && typeof raw === 'object') {
    return displayText(raw as Record<string, string>)
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
    const text = displayText(raw as Record<string, string>)
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

/** Preorden del árbol (sin límite de niveles): cada bloque, seguido AL
 *  MOMENTO de todos sus descendientes (recursivo), en su orden relativo. El
 *  reorder del servidor persiste exactamente este orden aplanado, así que
 *  `order` ya es un preorden — el índice público (IndexBlock) no necesita
 *  reordenar, solo calcular la profundidad. */
function arrange(list: BlockRow[]): BlockRow[] {
  const byParent = new Map<number | null, BlockRow[]>()
  for (const block of list) {
    const key = block.parent_id
    if (!byParent.has(key)) byParent.set(key, [])
    byParent.get(key)!.push(block)
  }
  const out: BlockRow[] = []
  function walk(parentId: number | null) {
    for (const block of byParent.get(parentId) ?? []) {
      out.push(block)
      walk(block.id)
    }
  }
  walk(null)
  // Huérfanos (padre borrado en otra sesión, o un ciclo residual): al final.
  out.push(...list.filter((b) => !out.includes(b)))
  return out
}

/** Profundidad real del bloque, subiendo por la cadena de padres. */
function depthOf(block: BlockRow): number {
  let depth = 0
  let current: BlockRow | undefined = block
  const seen = new Set<number>()
  while (current?.parent_id) {
    if (seen.has(current.id)) break
    seen.add(current.id)
    current = blocks.value.find((b) => b.id === current!.parent_id)
    if (current) depth++
  }
  return depth
}

/** ¿`candidateId` cuelga (a cualquier profundidad) de `ancestorId`? */
function isDescendant(candidateId: number, ancestorId: number): boolean {
  let current = blocks.value.find((b) => b.id === candidateId)
  const seen = new Set<number>()
  while (current?.parent_id) {
    if (current.parent_id === ancestorId) return true
    if (seen.has(current.id)) break
    seen.add(current.id)
    current = blocks.value.find((b) => b.id === current!.parent_id)
  }
  return false
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

/** Persiste el orden actual (la lista completa de ids, ya reagrupada). */
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

// --- Drag & drop nativo (HTML5, sin dependencias) --------------------------
// Fila arrastrable con asa (GripVertical): soltar en el tercio superior/
// inferior de otra fila reordena como hermana; soltar en el tercio central
// de CUALQUIER fila la convierte en su padre — sin límite de niveles, solo
// se prohíbe soltar sobre uno mismo o un descendiente propio (ciclo). Al
// mover un bloque se mueve con él TODO su subárbol (persiste al momento,
// sin botón Guardar).
const dragged = ref<BlockRow | null>(null)
const dropTarget = ref<{ id: number; position: 'before' | 'after' | 'inside' } | null>(null)

/** Único requisito para soltar sobre `target`: no ser el arrastrado ni un descendiente suyo. */
function canDropOn(target: BlockRow): boolean {
  if (!dragged.value) return false
  if (target.id === dragged.value.id) return false
  return !isDescendant(target.id, dragged.value.id)
}

function onDragStart(block: BlockRow, event: DragEvent) {
  dragged.value = block
  event.dataTransfer?.setData('text/plain', String(block.id))
  if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move'
}

function onDragOver(block: BlockRow, event: DragEvent) {
  if (!canDropOn(block)) {
    dropTarget.value = null
    return
  }
  const rect = (event.currentTarget as HTMLElement).getBoundingClientRect()
  const ratio = (event.clientY - rect.top) / rect.height
  const position: 'before' | 'after' | 'inside' =
    ratio > 0.3 && ratio < 0.7 ? 'inside' : ratio < 0.5 ? 'before' : 'after'
  dropTarget.value = { id: block.id, position }
}

function onDragLeave(block: BlockRow, event: DragEvent) {
  const related = event.relatedTarget as Node | null
  const current = event.currentTarget as HTMLElement
  if (related && current.contains(related)) return
  if (dropTarget.value?.id === block.id) dropTarget.value = null
}

function onDragEnd() {
  dragged.value = null
  dropTarget.value = null
}

async function onDrop(block: BlockRow, event: DragEvent) {
  event.preventDefault()
  const target = dropTarget.value
  const source = dragged.value
  dragged.value = null
  dropTarget.value = null
  if (!target || !source || target.id !== block.id) return
  if (source.id === target.id || isDescendant(target.id, source.id)) return

  const oldParentId = source.parent_id
  const sourceIdx = blocks.value.findIndex((b) => b.id === source.id)
  if (sourceIdx === -1) return
  const sourceDepth = depthOf(source)

  // Extrae el subárbol completo (el bloque + todos sus descendientes: la
  // tirada contigua que le sigue con más profundidad que él, ya que el
  // array se mantiene en preorden) para moverlo en bloque.
  let end = sourceIdx + 1
  while (end < blocks.value.length && depthOf(blocks.value[end]) > sourceDepth) end++
  const subtree = blocks.value.splice(sourceIdx, end - sourceIdx)

  let newParentId: number | null
  if (target.position === 'inside') {
    newParentId = target.id
    const targetBlock = blocks.value.find((b) => b.id === target.id)
    const targetDepth = targetBlock ? depthOf(targetBlock) : 0
    let insertAt = blocks.value.findIndex((b) => b.id === target.id) + 1
    while (insertAt < blocks.value.length && depthOf(blocks.value[insertAt]) > targetDepth) {
      insertAt++
    }
    subtree[0].parent_id = newParentId
    blocks.value.splice(insertAt, 0, ...subtree)
  } else {
    const targetIdx = blocks.value.findIndex((b) => b.id === target.id)
    newParentId = targetIdx >= 0 ? blocks.value[targetIdx].parent_id : null
    subtree[0].parent_id = newParentId
    const insertAt = target.position === 'after' ? targetIdx + 1 : targetIdx
    blocks.value.splice(insertAt < 0 ? blocks.value.length : insertAt, 0, ...subtree)
  }

  busy.value = true
  try {
    if (newParentId !== oldParentId) {
      await props.api.put(`/admin/blocks/${source.id}`, { parent_id: newParentId })
    }
    await persistOrder()
    await load()
  } catch {
    toast.danger(L.error)
    await load()
  } finally {
    busy.value = false
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

    <div class="page-blocks__list">
      <article
        v-for="block in blocks"
        :key="block.id"
        class="page-blocks__item"
        draggable="true"
        :style="{ '--depth': depthOf(block) }"
        :class="{
          'is-active': selectedId === block.id,
          'is-child': depthOf(block) > 0,
          'is-dragging': dragged?.id === block.id,
          'drop-before': dropTarget?.id === block.id && dropTarget.position === 'before',
          'drop-after': dropTarget?.id === block.id && dropTarget.position === 'after',
          'drop-inside': dropTarget?.id === block.id && dropTarget.position === 'inside',
        }"
        @click="(e) => selectBlock(block, e)"
        @dragstart="onDragStart(block, $event)"
        @dragover.prevent="onDragOver(block, $event)"
        @dragleave="onDragLeave(block, $event)"
        @drop="onDrop(block, $event)"
        @dragend="onDragEnd"
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
    </div>

    <!-- Modal generado desde el esquema del tipo: más ancho que el resto de
         formularios del admin (más columnas por la sección de imagen y las
         filas de alineación). -->
    <EditModal
      v-model="modalOpen"
      :title="modalType ? typeName(modalType.key) : ''"
      size="wide"
      :submit-label="L.save"
      :cancel-label="L.cancel"
      :loading="busy"
      @submit="save"
    >
      <template v-if="modalType">
        <!-- General ARRIBA del todo (antes iba al fondo, como "Ajustes
             comunes", y pasaba desapercibida): anchura + bloque padre;
             los interruptores; color de fondo + alineación general del
             bloque. Los campos del tipo van DESPUÉS. -->
        <div class="page-blocks__common">
          <span class="page-blocks__common-title">{{ L.common }}</span>

          <div class="page-blocks__common-row">
            <SchemaFields
              v-model="form"
              :fields="generalWidthFields"
              :locales="locales"
              :api="api"
              :translate="translate"
            />
            <BaseSelect
              :model-value="formParent === null ? '' : String(formParent)"
              :label="L.parent"
              :options="[{ value: '', label: L.parentNone }, ...parentOptions]"
              @update:model-value="(v: string) => (formParent = v ? Number(v) : null)"
            />
          </div>

          <div class="page-blocks__common-row">
            <BaseCheckbox v-model="formPrintable" :label="L.printable" />
            <BaseCheckbox v-model="formIndexable" :label="L.indexable" />
          </div>

          <div class="page-blocks__common-row">
            <SchemaFields
              v-model="form"
              :fields="generalBackgroundFields"
              :locales="locales"
              :api="api"
              :translate="translate"
            />
            <SchemaFields
              v-model="form"
              :fields="generalAlignFields"
              :locales="locales"
              :api="api"
              :translate="translate"
            />
          </div>
        </div>

        <SchemaFields
          v-model="form"
          :fields="typeFieldsWithAligns"
          :locales="locales"
          :api="api"
          :icons="icons"
          :rich-labels="richLabels"
          :translate="translate"
        />
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
