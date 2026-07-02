<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import { ChevronDown, ChevronRight, Download, FileWarning, RefreshCw, Trash2 } from '@lucide/vue'
import { BaseButton, IconButton, useConfirm, useToast } from '@bgm/ui'

// Gestor de PDF del juego (doc 02): pinta el CATÁLOGO de exports registrados
// (GET /admin/pdfs/exports) — colecciones globales y por entidad — y permite
// Generar / Regenerar / Descargar / Borrar cada PDF por idioma. Toda la
// gestión de PDF vive aquí (no en los detalles de las entidades).
// Agnóstico de i18n (DC-29): textos por prop, defaults en castellano.

export interface PdfManagerLabels {
  refresh: string
  generate: string
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
}

const defaultLabels: PdfManagerLabels = {
  refresh: 'Actualizar',
  generate: 'Generar',
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

// Filas por clave "type:sourceId|global"; expansión de fuentes.
const rows = reactive<Record<string, PdfRow[]>>({})
const open = reactive<Record<string, boolean>>({})

function keyOf(type: string, sourceId: number | null): string {
  return `${type}:${sourceId ?? 'global'}`
}

function typeName(exp: ExportInfo): string {
  return props.typeLabels[exp.type] ?? exp.type
}

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
    // Los globales cargan sus filas de inmediato; los por-entidad, al desplegar.
    await Promise.all(exports.value.filter((e) => e.global).map((e) => loadRows(e.type, null)))
  } catch {
    toast.danger(L.error)
  } finally {
    loading.value = false
  }
}

async function toggleSource(type: string, sourceId: number) {
  const key = keyOf(type, sourceId)
  open[key] = !open[key]
  if (open[key] && !rows[key]) await loadRows(type, sourceId)
}

async function refreshAll() {
  await loadCatalog()
  for (const key of Object.keys(open)) {
    if (open[key]) {
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
  if (sourceId !== null) open[keyOf(type, sourceId)] = true
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

function regenerate(pdf: PdfRow, type: string, sourceId: number | null) {
  run(() => props.api.post(`/admin/pdfs/${pdf.id}/regenerate`), type, sourceId)
}

async function del(pdf: PdfRow, type: string, sourceId: number | null) {
  const ok = await confirm({
    message: L.confirmDelete.replace('{name}', `${pdf.filename} (${pdf.locale.toUpperCase()})`),
    confirmLabel: L.delete,
    cancelLabel: L.cancel,
    variant: 'danger',
  })
  if (!ok) return
  run(() => props.api.delete(`/admin/pdfs/${pdf.id}`), type, sourceId)
}

function statusLabel(pdf: PdfRow): string {
  if (pdf.status === 'ready') return L.statusReady
  if (pdf.status === 'failed') return L.statusFailed
  return L.statusPending
}

onMounted(loadCatalog)
defineExpose({ refreshAll })
</script>

<template>
  <div class="pdf-manager">
    <div class="pdf-manager__bar">
      <BaseButton variant="secondary" :disabled="loading || busy" @click="refreshAll">
        <RefreshCw :size="16" /> {{ L.refresh }}
      </BaseButton>
    </div>

    <section v-for="exp in exports" :key="exp.type" class="pdf-manager__export">
      <header class="pdf-manager__head">
        <h2>{{ typeName(exp) }}</h2>
        <span class="pdf-manager__layout">{{ exp.layout }}</span>
        <BaseButton
          v-if="exp.global"
          class="pdf-manager__generate"
          :disabled="busy"
          @click="generate(exp.type, null)"
        >
          {{ L.generate }}
        </BaseButton>
      </header>

      <!-- Export global: filas por idioma directamente -->
      <template v-if="exp.global">
        <p
          v-if="rows[keyOf(exp.type, null)] && !rows[keyOf(exp.type, null)].length"
          class="pdf-manager__empty"
        >
          {{ L.empty }}
        </p>
        <ul v-else class="pdf-manager__list">
          <li v-for="pdf in rows[keyOf(exp.type, null)]" :key="pdf.id" class="pdf-row">
            <span class="pdf-row__locale">{{ pdf.locale.toUpperCase() }}</span>
            <span :class="['pdf-row__status', `pdf-row__status--${pdf.status}`]">
              {{ statusLabel(pdf) }}
            </span>
            <span
              v-if="pdf.status === 'failed' && pdf.error"
              class="pdf-row__error"
              :title="pdf.error"
            >
              <FileWarning :size="14" /> {{ pdf.error }}
            </span>
            <span class="pdf-row__buttons">
              <a
                v-if="pdf.url"
                class="icon-btn icon-btn--success"
                :href="pdf.url"
                target="_blank"
                rel="noopener"
                :title="L.download"
                ><Download :size="16"
              /></a>
              <IconButton
                variant="info"
                :title="L.regenerate"
                @click="regenerate(pdf, exp.type, null)"
                ><RefreshCw :size="16"
              /></IconButton>
              <IconButton variant="danger" :title="L.delete" @click="del(pdf, exp.type, null)"
                ><Trash2 :size="16"
              /></IconButton>
            </span>
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
                :is="open[keyOf(exp.type, source.id)] ? ChevronDown : ChevronRight"
                :size="16"
              />
              <span class="pdf-source__label">{{ source.label }}</span>
            </button>
            <BaseButton :disabled="busy" @click="generate(exp.type, source.id)">
              {{ L.generate }}
            </BaseButton>
          </div>

          <template v-if="open[keyOf(exp.type, source.id)]">
            <p
              v-if="rows[keyOf(exp.type, source.id)] && !rows[keyOf(exp.type, source.id)].length"
              class="pdf-manager__empty"
            >
              {{ L.empty }}
            </p>
            <ul v-else class="pdf-manager__list">
              <li v-for="pdf in rows[keyOf(exp.type, source.id)]" :key="pdf.id" class="pdf-row">
                <span class="pdf-row__locale">{{ pdf.locale.toUpperCase() }}</span>
                <span :class="['pdf-row__status', `pdf-row__status--${pdf.status}`]">
                  {{ statusLabel(pdf) }}
                </span>
                <span
                  v-if="pdf.status === 'failed' && pdf.error"
                  class="pdf-row__error"
                  :title="pdf.error"
                >
                  <FileWarning :size="14" /> {{ pdf.error }}
                </span>
                <span class="pdf-row__buttons">
                  <a
                    v-if="pdf.url"
                    class="icon-btn icon-btn--success"
                    :href="pdf.url"
                    target="_blank"
                    rel="noopener"
                    :title="L.download"
                    ><Download :size="16"
                  /></a>
                  <IconButton
                    variant="info"
                    :title="L.regenerate"
                    @click="regenerate(pdf, exp.type, source.id)"
                    ><RefreshCw :size="16"
                  /></IconButton>
                  <IconButton
                    variant="danger"
                    :title="L.delete"
                    @click="del(pdf, exp.type, source.id)"
                    ><Trash2 :size="16"
                  /></IconButton>
                </span>
              </li>
            </ul>
          </template>
        </article>
      </div>
    </section>
  </div>
</template>
