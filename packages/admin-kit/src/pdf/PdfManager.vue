<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import { Download, FilePlus, RefreshCw, Trash2 } from '@lucide/vue'
import { BaseButton, BaseInput, IconButton, useConfirm, useToast } from '@bgm/ui'
import ManagerCard from '../components/ManagerCard.vue'
import { useRightSidebar } from '../composables/useRightSidebar'

// Gestor de PDF del juego (doc 02), mobile-first. Una tarjeta FIJA por export
// del catálogo (GET /admin/pdfs/exports): los globales resumen el estado por
// idioma y generan con un botón; los por-entidad enseñan cuántas dueñas hay y
// generan todas. Al seleccionar una tarjeta, el panel derecho (patrón
// kontuan) muestra sus PDF por idioma con acciones — y, en los por-entidad,
// un SELECTOR CON BUSCADOR de la entidad dueña. Toda la gestión de PDF vive
// aquí (no en los detalles de las entidades).
// Agnóstico de i18n (DC-29): textos por prop, defaults en castellano.

export interface PdfManagerLabels {
  refresh: string
  generate: string
  generateAll: string
  download: string
  regenerate: string
  delete: string
  confirmDelete: string
  cancel: string
  empty: string
  error: string
  statusPending: string
  statusReady: string
  statusFailed: string
  detailTitle: string
  panelEmpty: string
  searchPlaceholder: string
  noResults: string
  generatedAt: string
  sourcesCount: string
}

const defaultLabels: PdfManagerLabels = {
  refresh: 'Actualizar',
  generate: 'Generar',
  generateAll: 'Generar todo',
  download: 'Descargar',
  regenerate: 'Regenerar',
  delete: 'Borrar',
  confirmDelete: '¿Borrar el PDF de "{name}"?',
  cancel: 'Cancelar',
  empty: 'Aún no hay PDF generados.',
  error: 'No se ha podido completar la acción.',
  statusPending: 'En cola…',
  statusReady: 'Listo',
  statusFailed: 'Error',
  detailTitle: 'PDF',
  panelEmpty: 'Selecciona una tarjeta para gestionar sus PDF.',
  searchPlaceholder: 'Buscar…',
  noResults: 'Sin resultados.',
  generatedAt: 'Generado',
  sourcesCount: '{count} elementos',
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

const activeExport = computed(() => exports.value.find((e) => e.type === activeType.value) ?? null)

/** Fuentes filtradas por el buscador del panel (filtro en cliente). */
const filteredSources = computed(() => {
  if (!activeExport.value) return []
  const q = sourceSearch.value.trim().toLowerCase()
  return q
    ? activeExport.value.sources.filter((s) => s.label.toLowerCase().includes(q))
    : activeExport.value.sources
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
    // Los globales cargan sus filas ya (alimentan el resumen de la tarjeta).
    await Promise.all(exports.value.filter((e) => e.global).map((e) => loadRows(e.type, null)))
  } catch {
    toast.danger(L.error)
  } finally {
    loading.value = false
  }
}

function select(exp: ExportInfo) {
  if (activeType.value !== exp.type) {
    activeType.value = exp.type
    sourceSearch.value = ''
    selectedSourceId.value = null
  }
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

async function run(
  action: () => Promise<{ data: { message?: string } }>,
  type: string,
  sourceId: number | null,
) {
  busy.value = true
  try {
    const { data } = await action()
    if (data.message) toast.success(data.message)
    await loadRows(type, sourceId)
  } catch {
    toast.danger(L.error)
  } finally {
    busy.value = false
  }
}

function generate(type: string, sourceId: number | null) {
  run(
    () =>
      props.api.post('/admin/pdfs/generate', {
        type,
        source_id: sourceId ?? undefined,
      }),
    type,
    sourceId,
  )
}

/** Genera el PDF de TODAS las entidades dueñas de un export por entidad. */
async function generateAllSources(exp: ExportInfo) {
  busy.value = true
  let message: string | undefined
  try {
    for (const source of exp.sources) {
      const { data } = await props.api.post('/admin/pdfs/generate', {
        type: exp.type,
        source_id: source.id,
      })
      message = data.message ?? message
      if (rows[keyOf(exp.type, source.id)]) await loadRows(exp.type, source.id)
    }
    if (message) toast.success(message)
  } catch {
    toast.danger(L.error)
  } finally {
    busy.value = false
  }
}

/** (type, sourceId) del contexto visible en el panel. */
function panelContext(): { type: string; sourceId: number | null } | null {
  if (!activeExport.value) return null
  return {
    type: activeExport.value.type,
    sourceId: activeExport.value.global ? null : selectedSourceId.value,
  }
}

function regenerate(pdf: PdfRow) {
  const ctx = panelContext()
  if (!ctx) return
  run(() => props.api.post(`/admin/pdfs/${pdf.id}/regenerate`), ctx.type, ctx.sourceId)
}

async function del(pdf: PdfRow) {
  const ctx = panelContext()
  if (!ctx) return
  const ok = await confirm({
    message: L.confirmDelete.replace('{name}', `${pdf.filename} (${pdf.locale.toUpperCase()})`),
    confirmLabel: L.delete,
    cancelLabel: L.cancel,
    variant: 'danger',
  })
  if (!ok) return
  run(() => props.api.delete(`/admin/pdfs/${pdf.id}`), ctx.type, ctx.sourceId)
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
        v-for="exp in exports"
        :key="exp.type"
        :title="typeName(exp)"
        :chip="exp.layout"
        :active="activeType === exp.type"
        @select="select(exp)"
      >
        <!-- Resumen: estado por idioma (globales) o nº de dueñas -->
        <template #meta>
          <template v-if="exp.global">
            <span
              v-for="pdf in rows[keyOf(exp.type, null)] ?? []"
              :key="pdf.id"
              :class="['locale-chip', statusClass(pdf)]"
              >{{ pdf.locale.toUpperCase() }}</span
            >
            <span
              v-if="rows[keyOf(exp.type, null)] && !rows[keyOf(exp.type, null)].length"
              class="manager-stat"
              >{{ L.empty }}</span
            >
          </template>
          <span v-else class="manager-stat">
            {{ L.sourcesCount.replace('{count}', String(exp.sources.length)) }}
          </span>
        </template>

        <template #actions>
          <BaseButton v-if="exp.global" :disabled="busy" @click="generate(exp.type, null)">
            <template #icon><FilePlus :size="16" /></template>
            {{ L.generate }}
          </BaseButton>
          <BaseButton
            v-else
            :disabled="busy || !exp.sources.length"
            @click="generateAllSources(exp)"
          >
            <template #icon><FilePlus :size="16" /></template>
            {{ L.generateAll }}
          </BaseButton>
        </template>
      </ManagerCard>
    </div>

    <!-- Panel derecho: PDF del export activo (con selector en los por-entidad) -->
    <Teleport defer to="#right-sidebar-target">
      <div class="manager-panel">
        <p v-if="!activeExport" class="manager-panel__empty">{{ L.panelEmpty }}</p>
        <template v-else>
          <p class="manager-panel__kicker">{{ typeName(activeExport) }}</p>

          <!-- Por entidad: selector con buscador de la entidad dueña -->
          <template v-if="!activeExport.global">
            <BaseInput
              v-model="sourceSearch"
              class="manager-panel__search"
              :placeholder="L.searchPlaceholder"
            />
            <p v-if="!filteredSources.length" class="manager-panel__empty">{{ L.noResults }}</p>
            <ul v-else class="manager-panel__list">
              <li v-for="source in filteredSources" :key="source.id">
                <button
                  type="button"
                  class="manager-panel__option"
                  :class="{ 'is-active': selectedSourceId === source.id }"
                  @click="selectSource(source.id)"
                >
                  {{ source.label }}
                </button>
              </li>
            </ul>
          </template>

          <div v-if="panelRows" class="manager-detail">
            <div v-if="!activeExport.global" class="manager-detail__actions">
              <BaseButton :disabled="busy" @click="generate(activeExport.type, selectedSourceId)">
                <FilePlus :size="14" /> {{ L.generate }}
              </BaseButton>
            </div>

            <p v-if="!panelRows.length" class="manager-panel__empty">{{ L.empty }}</p>

            <div v-for="pdf in panelRows" :key="pdf.id" class="pdf-entry">
              <div class="pdf-entry__head">
                <span class="pdf-entry__locale">{{ pdf.locale.toUpperCase() }}</span>
                <span :class="['locale-chip', statusClass(pdf)]">{{ statusLabel(pdf) }}</span>
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
