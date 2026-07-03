<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import { ChevronDown, ChevronRight, Download, RefreshCw, Trash2 } from '@lucide/vue'
import { BaseButton, useConfirm, useToast } from '@bgm/ui'
import ManagerCard from '../components/ManagerCard.vue'
import { useRightSidebar } from '../composables/useRightSidebar'

// Gestor de PDF del juego (doc 02), mobile-first: pinta el CATÁLOGO de
// exports registrados (GET /admin/pdfs/exports) como tarjetas colapsables
// (rejilla de 1-2 columnas según el contenedor). Los globales resumen el
// estado por idioma en la cabecera; los por-entidad listan sus dueñas. El
// clic en una fila abre su DETALLE en el panel derecho (fichero, fecha,
// error completo y acciones, patrón kontuan). Toda la gestión de PDF vive
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
  detailEmpty: string
  generatedAt: string
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
  detailTitle: 'Detalle',
  detailEmpty: 'Elige un PDF para ver su detalle.',
  generatedAt: 'Generado',
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

// El detalle del PDF vive en el panel derecho del layout.
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

// Filas por clave "type:sourceId|global"; expansión de tarjetas y fuentes.
const rows = reactive<Record<string, PdfRow[]>>({})
const open = reactive<Record<string, boolean>>({})
const openSource = reactive<Record<string, boolean>>({})
// PDF activo: su detalle se enseña en el panel derecho.
const active = ref<{ key: string; id: number } | null>(null)

function keyOf(type: string, sourceId: number | null): string {
  return `${type}:${sourceId ?? 'global'}`
}

function typeName(exp: ExportInfo): string {
  return props.typeLabels[exp.type] ?? exp.type
}

const activeRow = computed<PdfRow | null>(() => {
  if (!active.value) return null
  return (rows[active.value.key] ?? []).find((r) => r.id === active.value!.id) ?? null
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
    // Los globales cargan sus filas ya (alimentan el resumen de la cabecera);
    // los por-entidad, al desplegar cada fuente.
    await Promise.all(exports.value.filter((e) => e.global).map((e) => loadRows(e.type, null)))
  } catch {
    toast.danger(L.error)
  } finally {
    loading.value = false
  }
}

async function toggleSource(type: string, sourceId: number) {
  const key = keyOf(type, sourceId)
  openSource[key] = !openSource[key]
  if (openSource[key] && !rows[key]) await loadRows(type, sourceId)
}

function show(key: string, pdf: PdfRow) {
  active.value = { key, id: pdf.id }
  sidebar.reveal()
}

async function refreshAll() {
  await loadCatalog()
  for (const key of Object.keys(openSource)) {
    if (openSource[key]) {
      const [type, source] = key.split(':')
      await loadRows(type, Number(source))
    }
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
  if (sourceId !== null) openSource[keyOf(type, sourceId)] = true
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
      if (openSource[keyOf(exp.type, source.id)]) await loadRows(exp.type, source.id)
    }
    if (message) toast.success(message)
  } catch {
    toast.danger(L.error)
  } finally {
    busy.value = false
  }
}

function regenerate(pdf: PdfRow, key: string) {
  const [type, source] = key.split(':')
  run(
    () => props.api.post(`/admin/pdfs/${pdf.id}/regenerate`),
    type,
    source === 'global' ? null : Number(source),
  )
}

async function del(pdf: PdfRow, key: string) {
  const ok = await confirm({
    message: L.confirmDelete.replace('{name}', `${pdf.filename} (${pdf.locale.toUpperCase()})`),
    confirmLabel: L.delete,
    cancelLabel: L.cancel,
    variant: 'danger',
  })
  if (!ok) return
  if (active.value?.key === key && active.value.id === pdf.id) active.value = null
  const [type, source] = key.split(':')
  run(
    () => props.api.delete(`/admin/pdfs/${pdf.id}`),
    type,
    source === 'global' ? null : Number(source),
  )
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
  return iso ? new Date(iso).toLocaleString() : '—'
}

onMounted(loadCatalog)
defineExpose({ refreshAll })
</script>

<template>
  <div class="pdf-manager manager-container">
    <div class="manager-bar">
      <BaseButton variant="secondary" :disabled="loading || busy" @click="refreshAll">
        <RefreshCw :size="16" /> {{ L.refresh }}
      </BaseButton>
    </div>

    <div class="manager-grid">
      <ManagerCard
        v-for="exp in exports"
        :key="exp.type"
        v-model:open="open[exp.type]"
        :title="typeName(exp)"
        :chip="exp.layout"
      >
        <!-- Resumen en cabecera: estado por idioma (globales) o nº de dueñas -->
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
          <span v-else class="manager-stat"
            ><strong>{{ exp.sources.length }}</strong></span
          >
        </template>

        <!-- Export global: filas por idioma -->
        <template v-if="exp.global">
          <p v-if="!(rows[keyOf(exp.type, null)] ?? []).length" class="pdf-manager__empty">
            {{ L.empty }}
          </p>
          <ul v-else class="pdf-list">
            <li v-for="pdf in rows[keyOf(exp.type, null)]" :key="pdf.id">
              <button
                type="button"
                class="pdf-row"
                :class="{
                  'is-active': active?.key === keyOf(exp.type, null) && active?.id === pdf.id,
                }"
                @click="show(keyOf(exp.type, null), pdf)"
              >
                <span class="pdf-row__locale">{{ pdf.locale.toUpperCase() }}</span>
                <span :class="['locale-chip', statusClass(pdf)]">{{ statusLabel(pdf) }}</span>
              </button>
            </li>
          </ul>
        </template>

        <!-- Export por entidad: una fila desplegable por entidad dueña -->
        <div v-else class="pdf-manager__sources">
          <article v-for="source in exp.sources" :key="source.id" class="pdf-source">
            <div class="pdf-source__head">
              <button
                type="button"
                class="pdf-source__toggle"
                @click="toggleSource(exp.type, source.id)"
              >
                <component
                  :is="openSource[keyOf(exp.type, source.id)] ? ChevronDown : ChevronRight"
                  :size="16"
                />
                <span class="pdf-source__label">{{ source.label }}</span>
              </button>
              <BaseButton :disabled="busy" @click="generate(exp.type, source.id)">
                {{ L.generate }}
              </BaseButton>
            </div>

            <template v-if="openSource[keyOf(exp.type, source.id)]">
              <p v-if="!(rows[keyOf(exp.type, source.id)] ?? []).length" class="pdf-manager__empty">
                {{ L.empty }}
              </p>
              <ul v-else class="pdf-list">
                <li v-for="pdf in rows[keyOf(exp.type, source.id)]" :key="pdf.id">
                  <button
                    type="button"
                    class="pdf-row"
                    :class="{
                      'is-active':
                        active?.key === keyOf(exp.type, source.id) && active?.id === pdf.id,
                    }"
                    @click="show(keyOf(exp.type, source.id), pdf)"
                  >
                    <span class="pdf-row__locale">{{ pdf.locale.toUpperCase() }}</span>
                    <span :class="['locale-chip', statusClass(pdf)]">{{ statusLabel(pdf) }}</span>
                  </button>
                </li>
              </ul>
            </template>
          </article>
        </div>

        <template #actions>
          <BaseButton v-if="exp.global" :disabled="busy" @click="generate(exp.type, null)">
            {{ L.generate }}
          </BaseButton>
          <BaseButton
            v-else
            :disabled="busy || !exp.sources.length"
            @click="generateAllSources(exp)"
          >
            {{ L.generateAll }}
          </BaseButton>
        </template>
      </ManagerCard>
    </div>

    <!-- Detalle del PDF activo, en el panel derecho del layout -->
    <Teleport defer to="#right-sidebar-target">
      <div class="manager-detail">
        <p v-if="!activeRow" class="manager-detail__empty">{{ L.detailEmpty }}</p>
        <template v-else>
          <h3 class="manager-detail__title">{{ activeRow.filename }}</h3>
          <div class="manager-detail__row">
            <span class="locale-chip">{{ activeRow.locale.toUpperCase() }}</span>
            <span :class="['locale-chip', statusClass(activeRow)]">{{
              statusLabel(activeRow)
            }}</span>
          </div>
          <p class="manager-detail__meta">
            {{ L.generatedAt }}: {{ formatDate(activeRow.generated_at) }}
          </p>
          <p v-if="activeRow.error" class="manager-detail__error">{{ activeRow.error }}</p>

          <div class="manager-detail__actions">
            <a
              v-if="activeRow.url"
              class="bgm-button bgm-button--secondary"
              :href="activeRow.url"
              target="_blank"
              rel="noopener"
            >
              <Download :size="14" /> {{ L.download }}
            </a>
            <BaseButton :disabled="busy" @click="regenerate(activeRow, active!.key)">
              <RefreshCw :size="14" /> {{ L.regenerate }}
            </BaseButton>
            <BaseButton variant="danger" :disabled="busy" @click="del(activeRow, active!.key)">
              <Trash2 :size="14" /> {{ L.delete }}
            </BaseButton>
          </div>
        </template>
      </div>
    </Teleport>
  </div>
</template>
