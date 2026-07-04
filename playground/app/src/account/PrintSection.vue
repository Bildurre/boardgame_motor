<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { Download, FileText, Plus, Trash2, X } from '@lucide/vue'
import { useI18n } from 'vue-i18n'
import { BaseButton } from '@bgm/ui'
import { api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'

// Sección del panel de ESTE juego (doc 10 + doc 02): la colección temporal
// "para imprimir" — añadir personajes, ajustar copias, generar el PDF
// temporal y descargarlo. Ejemplo de punto de extensión del panel.
interface CollectionItem {
  id: number
  entity: string
  entity_id: number
  copies: number
  label: string | null
  preview: string | null
  missing: boolean
}

interface PickRow {
  id: number
  name: Record<string, string>
}

interface GeneratedPdf {
  id: number
  status: string
  url: string | null
  error: string | null
}

const { t } = useI18n()
const locales = useLocalesStore()

const items = ref<CollectionItem[]>([])
const characters = ref<PickRow[]>([])
const pdf = ref<GeneratedPdf | null>(null)
const generating = ref(false)
const error = ref<string | null>(null)

async function load() {
  try {
    const { data } = await api.get('/pdf-collection')
    items.value = data.data
  } catch {
    items.value = []
  }
  try {
    const { data } = await api.get('/characters')
    characters.value = data.data
  } catch {
    characters.value = []
  }
}

function name(row: PickRow): string {
  return row.name[locales.current] || Object.values(row.name)[0] || ''
}

function inCollection(row: PickRow): boolean {
  return items.value.some((i) => i.entity === 'character' && i.entity_id === row.id)
}

async function add(row: PickRow) {
  error.value = null
  try {
    const { data } = await api.post('/pdf-collection/items', { entity: 'character', id: row.id })
    items.value = data.data
  } catch {
    error.value = t('account.print.error')
  }
}

async function setCopies(item: CollectionItem, copies: number) {
  if (copies < 1 || copies > 99) return
  error.value = null
  try {
    const { data } = await api.post('/pdf-collection/items', {
      entity: item.entity,
      id: item.entity_id,
      copies,
    })
    items.value = data.data
  } catch {
    error.value = t('account.print.error')
  }
}

async function remove(item: CollectionItem) {
  error.value = null
  try {
    const { data } = await api.delete(`/pdf-collection/items/${item.id}`)
    items.value = data.data
  } catch {
    error.value = t('account.print.error')
  }
}

async function clear() {
  error.value = null
  try {
    await api.delete('/pdf-collection')
    items.value = []
  } catch {
    error.value = t('account.print.error')
  }
}

// Generar es asíncrono (cola, doc 02): se sondea el estado hasta ready/failed.
let poll: ReturnType<typeof setInterval> | null = null
onBeforeUnmount(() => {
  if (poll) clearInterval(poll)
})

async function generate() {
  error.value = null
  pdf.value = null
  generating.value = true
  try {
    const { data } = await api.post('/pdf-collection/generate', { locale: locales.current })
    pdf.value = data.data
    poll = setInterval(async () => {
      if (!pdf.value) return
      const { data: status } = await api.get(`/pdf-collection/pdfs/${pdf.value.id}`)
      pdf.value = status.data
      if (pdf.value?.status !== 'pending') {
        if (poll) clearInterval(poll)
        generating.value = false
        if (pdf.value?.status === 'failed') error.value = t('account.print.failed')
      }
    }, 1000)
  } catch {
    generating.value = false
    error.value = t('account.print.failed')
  }
}

onMounted(load)
</script>

<template>
  <div class="print-section">
    <h2>{{ t('account.sections.print') }}</h2>
    <p class="print-section__intro">{{ t('account.print.intro') }}</p>

    <h3>{{ t('account.print.collection') }}</h3>
    <p v-if="!items.length" class="print-section__empty">{{ t('account.print.empty') }}</p>
    <ul v-else class="print-section__items">
      <li v-for="item in items" :key="item.id" class="print-section__item">
        <img v-if="item.preview" class="print-section__thumb" :src="item.preview" alt="" />
        <span class="print-section__label">{{ item.label ?? '—' }}</span>
        <span class="print-section__copies">
          <input
            :value="item.copies"
            type="number"
            min="1"
            max="99"
            @change="(e) => setCopies(item, Number((e.target as HTMLInputElement).value))"
          />
          × {{ t('account.print.copies') }}
        </span>
        <button
          class="print-section__remove"
          type="button"
          :title="t('account.print.remove')"
          @click="remove(item)"
        >
          <X :size="16" />
        </button>
      </li>
    </ul>

    <div v-if="items.length" class="print-section__actions">
      <BaseButton :disabled="generating" @click="generate">
        <template #icon><FileText :size="16" /></template>
        {{ generating ? t('account.print.generating') : t('account.print.generate') }}
      </BaseButton>
      <BaseButton variant="secondary" @click="clear">
        <template #icon><Trash2 :size="16" /></template>
        {{ t('account.print.clear') }}
      </BaseButton>
      <a
        v-if="pdf?.status === 'ready' && pdf.url"
        class="print-section__download"
        :href="pdf.url"
        target="_blank"
        rel="noopener"
      >
        <Download :size="16" /> {{ t('account.print.download') }}
      </a>
    </div>
    <p v-if="error" class="error">{{ error }}</p>

    <h3>{{ t('account.print.pick') }}</h3>
    <ul class="print-section__pick">
      <li v-for="row in characters" :key="row.id">
        <span>{{ name(row) }}</span>
        <BaseButton
          v-if="!inCollection(row)"
          variant="secondary"
          :title="t('account.print.add')"
          @click="add(row)"
        >
          <template #icon><Plus :size="14" /></template>
          {{ t('account.print.add') }}
        </BaseButton>
      </li>
    </ul>
  </div>
</template>
