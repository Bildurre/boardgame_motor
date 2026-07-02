<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import { ChevronDown, ChevronRight, ImageOff, RefreshCw, Trash2 } from '@lucide/vue'
import { BaseButton, IconButton, useConfirm, useToast } from '@bgm/ui'

// Gestor de previews PNG (doc 01/08): estado por tipo, lotes (generar
// pendientes / regenerar todo / borrar todo), acciones por entidad y limpieza
// de huérfanos. Consume los endpoints /admin/previews del motor.
// Agnóstico de i18n (DC-29): textos por prop, defaults en castellano.

export interface PreviewManagerLabels {
  refresh: string
  generate: string
  regenerateAll: string
  deleteAll: string
  clean: string
  total: string
  complete: string
  pending: string
  empty: string
  loadMore: string
  itemRegenerate: string
  itemDelete: string
  confirmRegenerateAll: string
  confirmDeleteAll: string
  confirmDeleteItem: string
  confirmClean: string
  confirm: string
  cancel: string
  error: string
}

const defaultLabels: PreviewManagerLabels = {
  refresh: 'Actualizar',
  generate: 'Generar pendientes',
  regenerateAll: 'Regenerar todo',
  deleteAll: 'Borrar todo',
  clean: 'Limpiar huérfanos',
  total: 'Total',
  complete: 'Completas',
  pending: 'Pendientes',
  empty: 'No hay entidades.',
  loadMore: 'Cargar más',
  itemRegenerate: 'Regenerar',
  itemDelete: 'Borrar PNG',
  confirmRegenerateAll: '¿Regenerar TODAS las previews de {type}?',
  confirmDeleteAll: '¿Borrar TODAS las previews de {type}?',
  confirmDeleteItem: '¿Borrar los PNG de "{name}"?',
  confirmClean: '¿Eliminar los ficheros de preview huérfanos?',
  confirm: 'Confirmar',
  cancel: 'Cancelar',
  error: 'No se ha podido completar la acción.',
}

const props = withDefaults(
  defineProps<{
    api: AxiosInstance
    labels?: Partial<PreviewManagerLabels>
    /** Nombre traducido de cada tipo (clave del registro => etiqueta). */
    typeLabels?: Record<string, string>
  }>(),
  { labels: () => ({}), typeLabels: () => ({}) },
)

const L = reactive({ ...defaultLabels, ...props.labels }) as PreviewManagerLabels

const toast = useToast()
const { confirm } = useConfirm()

interface TypeStatus {
  key: string
  model: string
  total: number
  complete: number
  pending: number
}

interface PreviewItem {
  id: number
  label: string
  previews: Record<string, string | null>
}

const types = ref<TypeStatus[]>([])
const loading = ref(true)
const busy = ref(false)

/** Etiqueta traducida del tipo (fallback: nombre del modelo). */
function typeName(type: TypeStatus): string {
  return props.typeLabels[type.key] ?? type.model
}

// Estado de expansión + items por tipo. Cada ítem también es plegable: cerrado
// muestra solo nombre + estado por locale; abierto, las imágenes.
const open = reactive<Record<string, boolean>>({})
const openItems = reactive<Record<string, boolean>>({})
const items = reactive<Record<string, PreviewItem[]>>({})
const pages = reactive<Record<string, { current: number; last: number }>>({})

function toggleItem(key: string, id: number) {
  openItems[`${key}:${id}`] = !openItems[`${key}:${id}`]
}

async function loadStatus() {
  loading.value = true
  try {
    const { data } = await props.api.get('/admin/previews')
    types.value = data.data
  } catch {
    toast.danger(L.error)
  } finally {
    loading.value = false
  }
}

async function loadItems(key: string, page = 1) {
  try {
    const { data } = await props.api.get(`/admin/previews/${key}/items`, { params: { page } })
    items[key] = page === 1 ? data.data : [...(items[key] ?? []), ...data.data]
    pages[key] = { current: data.meta.current_page, last: data.meta.last_page }
  } catch {
    toast.danger(L.error)
  }
}

async function toggle(key: string) {
  open[key] = !open[key]
  if (open[key] && !items[key]) await loadItems(key)
}

async function refreshAll() {
  await loadStatus()
  for (const key of Object.keys(open)) {
    if (open[key]) await loadItems(key)
  }
}

/** Envuelve una acción: bloquea botones, toast con el mensaje del servidor. */
async function run(action: () => Promise<{ data: { message?: string } }>, after?: () => void) {
  busy.value = true
  try {
    const { data } = await action()
    if (data.message) toast.success(data.message)
    after?.()
  } catch {
    toast.danger(L.error)
  } finally {
    busy.value = false
  }
}

function generateType(type: TypeStatus) {
  run(() => props.api.post(`/admin/previews/${type.key}/generate`))
}

async function regenerateType(type: TypeStatus) {
  const ok = await confirm({
    message: L.confirmRegenerateAll.replace('{type}', typeName(type)),
    confirmLabel: L.confirm,
    cancelLabel: L.cancel,
    variant: 'primary',
  })
  if (!ok) return
  run(() => props.api.post(`/admin/previews/${type.key}/regenerate`))
}

async function deleteType(type: TypeStatus) {
  const ok = await confirm({
    message: L.confirmDeleteAll.replace('{type}', typeName(type)),
    confirmLabel: L.deleteAll,
    cancelLabel: L.cancel,
    variant: 'danger',
  })
  if (!ok) return
  run(
    () => props.api.delete(`/admin/previews/${type.key}`),
    () => refreshAll(),
  )
}

async function cleanOrphans() {
  const ok = await confirm({
    message: L.confirmClean,
    confirmLabel: L.clean,
    cancelLabel: L.cancel,
    variant: 'danger',
  })
  if (!ok) return
  run(() => props.api.post('/admin/previews/clean'))
}

function regenerateItem(key: string, item: PreviewItem) {
  run(() => props.api.post(`/admin/previews/${key}/${item.id}/regenerate`))
}

async function deleteItem(key: string, item: PreviewItem) {
  const ok = await confirm({
    message: L.confirmDeleteItem.replace('{name}', item.label),
    confirmLabel: L.itemDelete,
    cancelLabel: L.cancel,
    variant: 'danger',
  })
  if (!ok) return
  run(
    () => props.api.delete(`/admin/previews/${key}/${item.id}`),
    async () => {
      await loadStatus()
      await loadItems(key)
    },
  )
}

onMounted(loadStatus)
defineExpose({ refreshAll })
</script>

<template>
  <div class="preview-manager">
    <div class="preview-manager__bar">
      <BaseButton variant="secondary" :disabled="loading || busy" @click="refreshAll">
        <RefreshCw :size="16" /> {{ L.refresh }}
      </BaseButton>
      <BaseButton variant="danger" :disabled="busy" @click="cleanOrphans">
        {{ L.clean }}
      </BaseButton>
    </div>

    <section v-for="type in types" :key="type.key" class="preview-manager__type">
      <header class="preview-manager__head">
        <button type="button" class="preview-manager__toggle" @click="toggle(type.key)">
          <component :is="open[type.key] ? ChevronDown : ChevronRight" :size="18" />
          <h2>{{ typeName(type) }}</h2>
        </button>

        <div class="preview-manager__stats">
          <span
            >{{ L.total }}: <strong>{{ type.total }}</strong></span
          >
          <span class="ok"
            >{{ L.complete }}: <strong>{{ type.complete }}</strong></span
          >
          <span :class="{ warn: type.pending > 0 }">
            {{ L.pending }}: <strong>{{ type.pending }}</strong>
          </span>
        </div>

        <div class="preview-manager__actions">
          <BaseButton :disabled="busy || type.pending === 0" @click="generateType(type)">
            {{ L.generate }}
          </BaseButton>
          <BaseButton variant="secondary" :disabled="busy" @click="regenerateType(type)">
            {{ L.regenerateAll }}
          </BaseButton>
          <BaseButton variant="danger" :disabled="busy" @click="deleteType(type)">
            {{ L.deleteAll }}
          </BaseButton>
        </div>
      </header>

      <div v-if="open[type.key]" class="preview-manager__items">
        <p v-if="items[type.key] && !items[type.key].length" class="preview-manager__empty">
          <ImageOff :size="16" /> {{ L.empty }}
        </p>

        <article v-for="item in items[type.key]" :key="item.id" class="preview-item">
          <div class="preview-item__head">
            <button
              type="button"
              class="preview-item__toggle"
              @click="toggleItem(type.key, item.id)"
            >
              <component
                :is="openItems[`${type.key}:${item.id}`] ? ChevronDown : ChevronRight"
                :size="16"
              />
              <span class="preview-item__label">{{ item.label }}</span>
            </button>

            <!-- Estado por locale de un vistazo, sin cargar las imágenes -->
            <span class="preview-item__chips">
              <span
                v-for="(url, locale) in item.previews"
                :key="locale"
                :class="['preview-item__chip', url ? 'is-ok' : 'is-missing']"
                >{{ String(locale).toUpperCase() }}</span
              >
            </span>

            <span class="preview-item__buttons">
              <IconButton
                variant="info"
                :title="L.itemRegenerate"
                @click="regenerateItem(type.key, item)"
                ><RefreshCw :size="16"
              /></IconButton>
              <IconButton variant="danger" :title="L.itemDelete" @click="deleteItem(type.key, item)"
                ><Trash2 :size="16"
              /></IconButton>
            </span>
          </div>
          <div v-if="openItems[`${type.key}:${item.id}`]" class="preview-item__locales">
            <figure
              v-for="(url, locale) in item.previews"
              :key="locale"
              :class="['preview-item__locale', { 'preview-item__locale--missing': !url }]"
            >
              <img v-if="url" :src="url" :alt="`${item.label} ${locale}`" />
              <span v-else class="preview-item__hole"><ImageOff :size="18" /></span>
              <figcaption>{{ String(locale).toUpperCase() }}</figcaption>
            </figure>
          </div>
        </article>

        <div
          v-if="pages[type.key] && pages[type.key].current < pages[type.key].last"
          class="preview-manager__more"
        >
          <BaseButton variant="secondary" @click="loadItems(type.key, pages[type.key].current + 1)">
            {{ L.loadMore }}
          </BaseButton>
        </div>
      </div>
    </section>
  </div>
</template>
