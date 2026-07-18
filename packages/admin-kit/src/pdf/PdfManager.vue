<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import { Download, FilePlus, RefreshCw, Trash2 } from '@lucide/vue'
import { BaseButton, IconButton, SearchSelect, useConfirm, useToast } from '@edc-motor/ui'
import ManagerCard from '../components/ManagerCard.vue'
import { useRightSidebar } from '../composables/useRightSidebar'

// Gestor de PDF del juego (doc 02), mobile-first. Una tarjeta FIJA por export
// del catálogo (GET /admin/pdfs/exports) con las MISMAS estadísticas que las
// previews: total de piezas y listas por idioma. Las acciones "de todas" son
// las de las previews — generar faltantes (tarjeta y panel), regenerar todo
// y borrar todo (panel) — y el panel añade, en los exports por entidad, un
// COMBOBOX (select con buscador) de la entidad dueña con sus PDF por idioma.
// Toda la gestión de PDF vive aquí (no en los detalles de las entidades).
// Agnóstico de i18n (DC-29): textos por prop, defaults en castellano.

export interface PdfManagerLabels {
  refresh: string
  generate: string
  generateMissing: string
  regenerateAll: string
  deleteAll: string
  download: string
  regenerate: string
  delete: string
  confirmDelete: string
  confirmRegenerateAll: string
  confirmDeleteAll: string
  confirm: string
  cancel: string
  empty: string
  error: string
  statusPending: string
  statusReady: string
  statusFailed: string
  detailTitle: string
  panelEmpty: string
  selectSource: string
  searchPlaceholder: string
  noResults: string
  generatedAt: string
  total: string
}

const defaultLabels: PdfManagerLabels = {
  refresh: 'Actualizar',
  generate: 'Generar',
  generateMissing: 'Generar faltantes',
  regenerateAll: 'Regenerar todo',
  deleteAll: 'Borrar todo',
  download: 'Descargar',
  regenerate: 'Regenerar',
  delete: 'Borrar',
  confirmDelete: '¿Borrar el PDF de "{name}"?',
  confirmRegenerateAll: '¿Regenerar TODOS los PDF de {type}?',
  confirmDeleteAll: '¿Borrar TODOS los PDF de {type}?',
  confirm: 'Confirmar',
  cancel: 'Cancelar',
  empty: 'Aún no hay PDF generados.',
  error: 'No se ha podido completar la acción.',
  statusPending: 'En cola…',
  statusReady: 'Listo',
  statusFailed: 'Error',
  detailTitle: 'PDF',
  panelEmpty: 'Selecciona una tarjeta para gestionar sus PDF.',
  selectSource: 'Elige un elemento…',
  searchPlaceholder: 'Buscar…',
  noResults: 'Sin resultados.',
  generatedAt: 'Generado',
  total: 'Total',
}

const props = withDefaults(
  defineProps<{
    api: AxiosInstance
    labels?: Partial<PdfManagerLabels>
    /** Nombre traducido de cada export (type => etiqueta). */
    typeLabels?: Record<string, string>
  }>(),
  { labels: () => ({}), typeLabels: () => ({}) },
)

const L = reactive({ ...defaultLabels, ...props.labels }) as PdfManagerLabels

const toast = useToast()
const { confirm } = useConfirm()

// El panel derecho enseña los PDF del export seleccionado.
const sidebar = useRightSidebar()
sidebar.useRegister(L.detailTitle)

interface ExportInfo {
  type: string
  global: boolean
  layout: string
  sources: { id: number; label: string }[]
  stats: { total: number; locales: Record<string, number> }
}

interface PdfRow {
  id: number
  locale: string
  status: 'pending' | 'ready' | 'failed'
  error: string | null
  url: string | null
  filename: string
  generated_at: string | null
}

const exports = ref<ExportInfo[]>([])
const loading = ref(true)
const busy = ref(false)

// Filas por clave "type:sourceId|global".
const rows = reactive<Record<string, PdfRow[]>>({})
// Export activo (tarjeta) + entidad dueña elegida en el selector del panel.
const activeType = ref<string | null>(null)
const sourceSearch = ref('')
const selectedSourceId = ref<number | null>(null)

function keyOf(type: string, sourceId: number | null): string {
  return `${type}:${sourceId ?? 'global'}`
}

function typeName(exp: ExportInfo): string {
  return props.typeLabels[exp.type] ?? exp.type
}

// Criterio de los selects del admin: sin un orden explícito, ALFABÉTICO.
// Vale también para las tarjetas, que hacen de selector de export.
const sortedExports = computed(() =>
  [...exports.value].sort((a, b) => typeName(a).localeCompare(typeName(b))),
)

/** Quedan piezas por generar (algún idioma por debajo del total). */
function hasMissing(exp: ExportInfo): boolean {
  return Object.values(exp.stats.locales).some((ready) => ready < exp.stats.total)
}

const activeExport = computed(() => exports.value.find((e) => e.type === activeType.value) ?? null)

/**
 * Fuentes del combobox del panel: filtradas por el buscador (en cliente) y
 * ALFABÉTICAS (criterio general de los selects del admin sin orden propio).
 */
const filteredSources = computed(() => {
  if (!activeExport.value) return []
  const q = sourceSearch.value.trim().toLowerCase()
  const sources = q
    ? activeExport.value.sources.filter((s) => s.label.toLowerCase().includes(q))
    : activeExport.value.sources
  return [...sources].sort((a, b) => a.label.localeCompare(b.label))
})

/** Filas visibles en el panel: las del export global o las de la dueña elegida. */
const panelRows = computed<PdfRow[] | null>(() => {
  if (!activeExport.value) return null
  if (activeExport.value.global) return rows[keyOf(activeExport.value.type, null)] ?? null
  if (selectedSourceId.value === null) return null
  return rows[keyOf(activeExport.value.type, selectedSourceId.value)] ?? null
})

async function loadRows(type: string, sourceId: number | null) {
  try {
    const { data } = await props.api.get('/admin/pdfs', {
      params: { type, source_id: sourceId ?? undefined },
    })
    rows[keyOf(type, sourceId)] = data.data
  } catch {
    toast.danger(L.error)
  }
}

async function loadCatalog() {
  loading.value = true
  try {
    const { data } = await props.api.get('/admin/pdfs/exports')
    exports.value = data.data
    // Los globales cargan sus filas ya (el panel las pinta directamente).
    await Promise.all(exports.value.filter((e) => e.global).map((e) => loadRows(e.type, null)))
    // El selector de export (las tarjetas) arranca con el PRIMERO seleccionado,
    // no vacío (sin abrir el panel: eso lo hace el click del usuario).
    if (!activeType.value && sortedExports.value.length) activate(sortedExports.value[0])
  } catch {
    toast.danger(L.error)
  } finally {
    loading.value = false
  }
}

/**
 * Activa un export: resetea el buscador y, en los exports por entidad, deja
 * el select de la dueña con el PRIMER elemento (alfabético) seleccionado —
 * nunca vacío — cargando sus filas.
 */
function activate(exp: ExportInfo) {
  activeType.value = exp.type
  sourceSearch.value = ''
  selectedSourceId.value = null
  const first = exp.global
    ? null
    : [...exp.sources].sort((a, b) => a.label.localeCompare(b.label))[0]
  if (first) selectSource(first.id)
}

function select(exp: ExportInfo) {
  if (activeType.value !== exp.type) activate(exp)
  sidebar.reveal()
}

function selectSource(id: number) {
  selectedSourceId.value = id
  if (activeExport.value && !rows[keyOf(activeExport.value.type, id)]) {
    loadRows(activeExport.value.type, id)
  }
}

async function refreshAll() {
  await loadCatalog()
  if (activeExport.value && !activeExport.value.global && selectedSourceId.value !== null) {
    await loadRows(activeExport.value.type, selectedSourceId.value)
  }
}

/** Envuelve una acción: bloquea botones, toast y refresco del catálogo. */
async function run(action: () => Promise<{ data: { message?: string } }>) {
  busy.value = true
  try {
    const { data } = await action()
    if (data.message) toast.success(data.message)
    await refreshAll()
  } catch {
    toast.danger(L.error)
  } finally {
    busy.value = false
  }
}

// --- Acciones "de todas" del export (espejo de las previews) ---

function generateMissing(exp: ExportInfo) {
  run(() => props.api.post('/admin/pdfs/generate-missing', { type: exp.type }))
}

async function regenerateAll(exp: ExportInfo) {
  const ok = await confirm({
    message: L.confirmRegenerateAll.replace('{type}', typeName(exp)),
    confirmLabel: L.confirm,
    cancelLabel: L.cancel,
    variant: 'primary',
  })
  if (!ok) return
  run(() => props.api.post('/admin/pdfs/regenerate-all', { type: exp.type }))
}

async function deleteAll(exp: ExportInfo) {
  const ok = await confirm({
    message: L.confirmDeleteAll.replace('{type}', typeName(exp)),
    confirmLabel: L.deleteAll,
    cancelLabel: L.cancel,
    variant: 'danger',
  })
  if (!ok) return
  run(() => props.api.delete('/admin/pdfs', { params: { type: exp.type } }))
}

// --- Acciones del elemento del panel ---

function generate(type: string, sourceId: number | null) {
  run(() =>
    props.api.post('/admin/pdfs/generate', {
      type,
      source_id: sourceId ?? undefined,
    }),
  )
}

function regenerate(pdf: PdfRow) {
  run(() => props.api.post(`/admin/pdfs/${pdf.id}/regenerate`))
}

async function del(pdf: PdfRow) {
  const ok = await confirm({
    message: L.confirmDelete.replace('{name}', `${pdf.filename} (${pdf.locale.toUpperCase()})`),
    confirmLabel: L.delete,
    cancelLabel: L.cancel,
    variant: 'danger',
  })
  if (!ok) return
  run(() => props.api.delete(`/admin/pdfs/${pdf.id}`))
}

function statusLabel(pdf: PdfRow): string {
  if (pdf.status === 'ready') return L.statusReady
  if (pdf.status === 'failed') return L.statusFailed
  return L.statusPending
}

function statusClass(pdf: PdfRow): string {
  if (pdf.status === 'ready') return 'is-ok'
  if (pdf.status === 'failed') return 'is-failed'
  return ''
}

function formatDate(iso: string | null): string {
  return iso ? new Date(iso).toLocaleString() : ''
}

onMounted(loadCatalog)
defineExpose({ refreshAll })
</script>

<template>
  <div class="pdf-manager manager-container">
    <div class="manager-bar">
      <BaseButton variant="secondary" :disabled="loading || busy" @click="refreshAll">
        <template #icon><RefreshCw :size="16" /></template>
        {{ L.refresh }}
      </BaseButton>
    </div>

    <div class="manager-grid">
      <ManagerCard
        v-for="exp in sortedExports"
        :key="exp.type"
        :title="typeName(exp)"
        :chip="exp.layout"
        :active="activeType === exp.type"
        @select="select(exp)"
      >
        <!-- Como EntityCard: badges (listos por idioma) arriba, meta (total) debajo -->
        <template #badges>
          <span
            v-for="(ready, locale) in exp.stats.locales"
            :key="locale"
            :class="['chip', ready === exp.stats.total ? 'is-ok' : 'is-missing']"
            >{{ String(locale).toUpperCase() }} {{ ready }}/{{ exp.stats.total }}</span
          >
        </template>
        <template #meta>
          <span class="manager-stat"
            >{{ L.total }} <strong>{{ exp.stats.total }}</strong></span
          >
        </template>
      </ManagerCard>
    </div>

    <!-- Panel derecho: acciones | separador | PDF del export activo -->
    <Teleport defer to="#right-sidebar-target">
      <div class="manager-panel">
        <p v-if="!activeExport" class="manager-panel__empty">{{ L.panelEmpty }}</p>
        <template v-else>
          <p class="manager-panel__kicker">{{ typeName(activeExport) }}</p>

          <!-- Acciones del export, PRIMERO (mismas que las previews) -->
          <div class="manager-detail__actions">
            <BaseButton
              :disabled="busy || !hasMissing(activeExport)"
              @click="generateMissing(activeExport)"
            >
              <template #icon><FilePlus :size="14" /></template>
              {{ L.generateMissing }}
            </BaseButton>
            <BaseButton variant="info" :disabled="busy" @click="regenerateAll(activeExport)">
              <template #icon><RefreshCw :size="14" /></template>
              {{ L.regenerateAll }}
            </BaseButton>
            <BaseButton variant="danger" :disabled="busy" @click="deleteAll(activeExport)">
              <template #icon><Trash2 :size="14" /></template>
              {{ L.deleteAll }}
            </BaseButton>
          </div>

          <hr class="manager-panel__divider" />

          <!-- Por entidad: combobox (con buscador) de la entidad dueña -->
          <SearchSelect
            v-if="!activeExport.global"
            :model-value="selectedSourceId"
            :options="filteredSources"
            :placeholder="L.selectSource"
            :search-placeholder="L.searchPlaceholder"
            :no-results="L.noResults"
            @update:model-value="(id) => selectSource(Number(id))"
            @search="(q) => (sourceSearch = q)"
          />

          <div v-if="panelRows" class="manager-detail">
            <div v-if="!activeExport.global" class="manager-detail__actions">
              <BaseButton :disabled="busy" @click="generate(activeExport.type, selectedSourceId)">
                <template #icon><FilePlus :size="14" /></template>
                {{ L.generate }}
              </BaseButton>
            </div>

            <p v-if="!panelRows.length" class="manager-panel__empty">{{ L.empty }}</p>

            <div v-for="pdf in panelRows" :key="pdf.id" class="pdf-entry">
              <div class="pdf-entry__head">
                <span class="pdf-entry__locale">{{ pdf.locale.toUpperCase() }}</span>
                <span :class="['chip', statusClass(pdf)]">{{ statusLabel(pdf) }}</span>
                <span class="pdf-entry__buttons">
                  <a
                    v-if="pdf.url"
                    class="icon-btn icon-btn--success"
                    :href="pdf.url"
                    target="_blank"
                    rel="noopener"
                    :title="L.download"
                    ><Download :size="16"
                  /></a>
                  <IconButton variant="info" :title="L.regenerate" @click="regenerate(pdf)"
                    ><RefreshCw :size="16"
                  /></IconButton>
                  <IconButton variant="danger" :title="L.delete" @click="del(pdf)"
                    ><Trash2 :size="16"
                  /></IconButton>
                </span>
              </div>
              <p v-if="pdf.generated_at" class="pdf-entry__meta">
                {{ L.generatedAt }}: {{ formatDate(pdf.generated_at) }}
              </p>
              <p v-if="pdf.status === 'failed' && pdf.error" class="pdf-entry__error">
                {{ pdf.error }}
              </p>
            </div>
          </div>
        </template>
      </div>
    </Teleport>
  </div>
</template>
