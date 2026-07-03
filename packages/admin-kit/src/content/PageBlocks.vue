<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import { GripVertical, Plus, SquarePen, Trash2 } from '@lucide/vue'
import { VueDraggable } from 'vue-draggable-plus'
import {
  BaseButton,
  BaseCheckbox,
  EditModal,
  IconButton,
  useConfirm,
  useToast,
  type RichIcon,
  type RichTextLabels,
} from '@bgm/ui'
import SchemaFields, { type FieldSchema } from './SchemaFields.vue'
import { useRightSidebar } from '../composables/useRightSidebar'

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
  indexable: string
  common: string
  confirmDelete: string
  error: string
  panelTitle: string
  panelEmpty: string
}

const defaultLabels: PageBlocksLabels = {
  add: 'Añadir bloque',
  edit: 'Editar bloque',
  delete: 'Borrar',
  save: 'Guardar',
  cancel: 'Cancelar',
  empty: 'La página aún no tiene bloques.',
  printable: 'Entra en el PDF de la página',
  indexable: 'Aparece en el índice',
  common: 'Ajustes comunes',
  confirmDelete: '¿Borrar este bloque?',
  error: 'No se ha podido completar la acción.',
  panelTitle: 'Bloque',
  panelEmpty: 'Selecciona un bloque para ver sus acciones.',
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

// Modal de edición (crear/editar comparten formulario).
const modalOpen = ref(false)
const editing = ref<BlockRow | null>(null)
const modalType = ref<BlockTypeSchema | null>(null)
const form = ref<Record<string, unknown>>({})
const formPrintable = ref(true)
const formIndexable = ref(true)

function typeName(key: string): string {
  const type = types.value.find((t) => t.key === key)
  const fallback = type?.name ?? key
  return props.translate?.(`blockTypes.${key}`, fallback) ?? fallback
}

/** Resumen del bloque en la lista: el primer texto traducible con valor. */
function summary(block: BlockRow): string {
  const type = types.value.find((t) => t.key === block.type)
  for (const field of type?.fields ?? []) {
    const value = block.settings?.[field.key]
    if (field.translatable && value && typeof value === 'object') {
      const text = Object.values(value as Record<string, string>).find(Boolean)
      if (text) return text.replace(/<[^>]*>/g, '').slice(0, 80)
    }
  }
  return ''
}

async function load() {
  try {
    const [palette, list] = await Promise.all([
      props.api.get('/admin/block-types'),
      props.api.get(`/admin/pages/${props.pageId}/blocks`),
    ])
    types.value = palette.data.data
    blocks.value = list.data.data
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
  modalOpen.value = true
}

function openEdit(block: BlockRow) {
  editing.value = block
  modalType.value = types.value.find((t) => t.key === block.type) ?? null
  form.value = { ...(block.settings ?? {}) }
  formPrintable.value = block.is_printable
  formIndexable.value = block.is_indexable
  modalOpen.value = true
}

async function save() {
  if (!modalType.value) return
  busy.value = true
  try {
    const payload = {
      type: modalType.value.key,
      settings: form.value,
      is_printable: formPrintable.value,
      is_indexable: formIndexable.value,
    }
    if (editing.value) {
      await props.api.put(`/admin/blocks/${editing.value.id}`, payload)
    } else {
      await props.api.post(`/admin/pages/${props.pageId}/blocks`, payload)
    }
    modalOpen.value = false
    await load()
  } catch {
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

/** El drag reordena en cliente; se persiste la lista de ids. */
async function persistOrder() {
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
        :class="{ 'is-active': selectedId === block.id }"
        @click="(e) => selectBlock(block, e)"
      >
        <span class="page-blocks__grip"><GripVertical :size="16" /></span>
        <span class="page-blocks__type">{{ typeName(block.type) }}</span>
        <span class="page-blocks__summary">{{ summary(block) }}</span>
        <span class="page-blocks__flags">
          <span v-if="block.is_printable" class="locale-chip is-ok">PDF</span>
          <span v-if="block.is_indexable" class="locale-chip">IDX</span>
        </span>
        <span class="page-blocks__buttons">
          <IconButton variant="info" :title="L.edit" @click="openEdit(block)"
            ><SquarePen :size="16"
          /></IconButton>
          <IconButton variant="danger" :title="L.delete" @click="remove(block)"
            ><Trash2 :size="16"
          /></IconButton>
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
        </details>
      </template>
    </EditModal>

    <!-- Acciones del bloque seleccionado, en el panel derecho (patrón kontuan) -->
    <Teleport defer to="#right-sidebar-target">
      <div class="manager-panel">
        <p v-if="!selected" class="manager-panel__empty">{{ L.panelEmpty }}</p>
        <template v-else>
          <p class="manager-panel__kicker">{{ typeName(selected.type) }}</p>
          <h3 class="manager-detail__title">{{ summary(selected) || typeName(selected.type) }}</h3>

          <!-- Acciones arriba del todo -->
          <div class="manager-detail__actions">
            <BaseButton :disabled="busy" @click="openEdit(selected)">
              <template #icon><SquarePen :size="14" /></template>
              {{ L.edit }}
            </BaseButton>
            <BaseButton variant="danger" :disabled="busy" @click="remove(selected)">
              <template #icon><Trash2 :size="14" /></template>
              {{ L.delete }}
            </BaseButton>
          </div>

          <!-- Acciones rápidas sin modal -->
          <BaseCheckbox
            :model-value="selected.is_printable"
            :label="L.printable"
            @update:model-value="(v) => toggleFlag('is_printable', v)"
          />
          <BaseCheckbox
            :model-value="selected.is_indexable"
            :label="L.indexable"
            @update:model-value="(v) => toggleFlag('is_indexable', v)"
          />
        </template>
      </div>
    </Teleport>
  </div>
</template>
