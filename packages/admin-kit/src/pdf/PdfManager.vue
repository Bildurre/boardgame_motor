<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import { Download, FileWarning, RefreshCw, Trash2 } from '@lucide/vue'
import { BaseButton, IconButton, useConfirm, useToast } from '@bgm/ui'

// Gestor de PDF de un export (doc 02): lista los PDF por idioma con su
// estado, y Generar / Regenerar / Descargar / Borrar con un clic. Se monta
// una vez por export (p. ej. en el detalle de la entidad dueña).
// Agnóstico de i18n (DC-29): textos por prop, defaults en castellano.

export interface PdfManagerLabels {
  title: string
  generate: string
  refresh: string
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
  title: 'PDF',
  generate: 'Generar (todos los idiomas)',
  refresh: 'Actualizar',
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
    /** Type del export (clave del PdfExportRegistry). */
    type: string
    /** Id de la entidad dueña; omítelo en exports globales. */
    sourceId?: number | null
    labels?: Partial<PdfManagerLabels>
  }>(),
  { sourceId: null, labels: () => ({}) },
)

const L = reactive({ ...defaultLabels, ...props.labels }) as PdfManagerLabels

const toast = useToast()
const { confirm } = useConfirm()

interface PdfRow {
  id: number
  locale: string
  status: 'pending' | 'ready' | 'failed'
  error: string | null
  url: string | null
  filename: string
  generated_at: string | null
}

const pdfs = ref<PdfRow[]>([])
const loading = ref(true)
const busy = ref(false)

async function load() {
  loading.value = true
  try {
    const { data } = await props.api.get('/admin/pdfs', {
      params: { type: props.type, source_id: props.sourceId ?? undefined },
    })
    pdfs.value = data.data
  } catch {
    toast.danger(L.error)
  } finally {
    loading.value = false
  }
}

async function run(action: () => Promise<{ data: { message?: string } }>) {
  busy.value = true
  try {
    const { data } = await action()
    if (data.message) toast.success(data.message)
    await load()
  } catch {
    toast.danger(L.error)
  } finally {
    busy.value = false
  }
}

function generate() {
  run(() =>
    props.api.post('/admin/pdfs/generate', {
      type: props.type,
      source_id: props.sourceId ?? undefined,
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

onMounted(load)
defineExpose({ load })
</script>

<template>
  <section class="pdf-manager">
    <div class="pdf-manager__bar">
      <h2>{{ L.title }}</h2>
      <div class="pdf-manager__actions">
        <BaseButton variant="secondary" :disabled="loading || busy" @click="load">
          <RefreshCw :size="16" /> {{ L.refresh }}
        </BaseButton>
        <BaseButton :disabled="busy" @click="generate">{{ L.generate }}</BaseButton>
      </div>
    </div>

    <p v-if="!loading && !pdfs.length" class="pdf-manager__empty">{{ L.empty }}</p>

    <ul v-else class="pdf-manager__list">
      <li v-for="pdf in pdfs" :key="pdf.id" class="pdf-row">
        <span class="pdf-row__locale">{{ pdf.locale.toUpperCase() }}</span>

        <span :class="['pdf-row__status', `pdf-row__status--${pdf.status}`]">
          {{ statusLabel(pdf) }}
        </span>
        <span v-if="pdf.status === 'failed' && pdf.error" class="pdf-row__error" :title="pdf.error">
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
          <IconButton variant="info" :title="L.regenerate" @click="regenerate(pdf)"
            ><RefreshCw :size="16"
          /></IconButton>
          <IconButton variant="danger" :title="L.delete" @click="del(pdf)"
            ><Trash2 :size="16"
          /></IconButton>
        </span>
      </li>
    </ul>
  </section>
</template>
