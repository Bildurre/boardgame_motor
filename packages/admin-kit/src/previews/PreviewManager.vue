<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import { ImageOff, RefreshCw, Trash2 } from '@lucide/vue'
import { BaseButton, useConfirm, useToast } from '@bgm/ui'
import ManagerCard from '../components/ManagerCard.vue'
import { useRightSidebar } from '../composables/useRightSidebar'

// Gestor de previews PNG (doc 01/08), mobile-first: tarjetas colapsables por
// tipo (rejilla de 1-2 columnas según el contenedor). Cada fila muestra el
// estado por locale con chips (saltan de fila en estrecho); el checkbox
// selecciona para las acciones EN BLOQUE del pie, y el clic en el nombre abre
// el DETALLE en el panel derecho (imágenes por idioma + acciones del
// elemento, patrón kontuan). Endpoints /admin/previews del motor.
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
  selectedCount: string
  regenerateSelected: string
  deleteSelected: string
  clearSelection: string
  detailTitle: string
  detailEmpty: string
  itemRegenerate: string
  itemDelete: string
  confirmRegenerateAll: string
  confirmDeleteAll: string
  confirmDeleteSelected: string
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
  selectedCount: '{count} seleccionadas',
  regenerateSelected: 'Regenerar selección',
  deleteSelected: 'Borrar selección',
  clearSelection: 'Quitar selección',
  detailTitle: 'Detalle',
  detailEmpty: 'Elige un elemento para ver sus imágenes por idioma.',
  itemRegenerate: 'Regenerar',
  itemDelete: 'Borrar PNG',
  confirmRegenerateAll: '¿Regenerar TODAS las previews de {type}?',
  confirmDeleteAll: '¿Borrar TODAS las previews de {type}?',
  confirmDeleteSelected: '¿Borrar los PNG de {count} entidades?',
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

// El detalle del elemento vive en el panel derecho del layout.
const sidebar = useRightSidebar()
sidebar.useRegister(L.detailTitle)

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

const open = reactive<Record<string, boolean>>({})
const items = reactive<Record<string, PreviewItem[]>>({})
const pages = reactive<Record<string, { current: number; last: number }>>({})
// Selección por tipo (checkbox): las acciones en bloque del pie operan sobre ella.
const selected = reactive<Record<string, number[]>>({})
// Elemento activo: su detalle se enseña en el panel derecho.
const active = ref<{ key: string; id: number } | null>(null)

const activeItem = computed<PreviewItem | null>(() => {
  if (!active.value) return null
  return (items[active.value.key] ?? []).find((i) => i.id === active.value!.id) ?? null
})

const activeTypeName = computed(() => {
  const type = types.value.find((t) => t.key === active.value?.key)
  return type ? typeName(type) : ''
})

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
    selected[key] ??= []
  } catch {
    toast.danger(L.error)
  }
}

function onToggle(key: string, isOpen: boolean) {
  if (isOpen && !items[key]) loadItems(key)
}

function show(key: string, item: PreviewItem) {
  active.value = { key, id: item.id }
  sidebar.reveal() // en móvil entra el drawer; en escritorio se despliega
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

/** Acción secuencial sobre varios ids; un solo toast al final. */
async function runMany(
  key: string,
  ids: number[],
  request: (id: number) => Promise<{ data: { message?: string } }>,
) {
  busy.value = true
  let message: string | undefined
  try {
    for (const id of ids) {
      const { data } = await request(id)
      message = data.message ?? message
    }
    if (message) toast.success(message)
    await loadStatus()
    await loadItems(key)
  } catch {
    toast.danger(L.error)
  } finally {
    busy.value = false
  }
}

function regenerateSelected(key: string) {
  runMany(key, [...(selected[key] ?? [])], (id) =>
    props.api.post(`/admin/previews/${key}/${id}/regenerate`),
  ).then(() => (selected[key] = []))
}

async function deleteSelected(key: string) {
  const ok = await confirm({
    message: L.confirmDeleteSelected.replace('{count}', String(selected[key]?.length ?? 0)),
    confirmLabel: L.deleteSelected,
    cancelLabel: L.cancel,
    variant: 'danger',
  })
  if (!ok) return
  await runMany(key, [...(selected[key] ?? [])], (id) =>
    props.api.delete(`/admin/previews/${key}/${id}`),
  )
  selected[key] = []
}

function regenerateItem() {
  if (!active.value) return
  const { key, id } = active.value
  runMany(key, [id], (i) => props.api.post(`/admin/previews/${key}/${i}/regenerate`))
}

async function deleteItem() {
  if (!active.value || !activeItem.value) return
  const { key, id } = active.value
  const ok = await confirm({
    message: L.confirmDeleteItem.replace('{name}', activeItem.value.label),
    confirmLabel: L.itemDelete,
    cancelLabel: L.cancel,
    variant: 'danger',
  })
  if (!ok) return
  runMany(key, [id], (i) => props.api.delete(`/admin/previews/${key}/${i}`))
}

onMounted(loadStatus)
defineExpose({ refreshAll })
</script>

<template>
  <div class="preview-manager manager-container">
    <div class="manager-bar">
      <BaseButton variant="secondary" :disabled="loading || busy" @click="refreshAll">
        <RefreshCw :size="16" /> {{ L.refresh }}
      </BaseButton>
      <BaseButton variant="danger" :disabled="busy" @click="cleanOrphans">
        {{ L.clean }}
      </BaseButton>
    </div>

    <div class="manager-grid">
      <ManagerCard
        v-for="type in types"
        :key="type.key"
        v-model:open="open[type.key]"
        :title="typeName(type)"
        @update:open="(v: boolean) => onToggle(type.key, v)"
      >
        <!-- Resumen siempre visible, también con la tarjeta cerrada -->
        <template #meta>
          <span class="manager-stat"
            >{{ L.total }} <strong>{{ type.total }}</strong></span
          >
          <span class="manager-stat is-ok"
            >{{ L.complete }} <strong>{{ type.complete }}</strong></span
          >
          <span class="manager-stat" :class="{ 'is-warn': type.pending > 0 }">
            {{ L.pending }} <strong>{{ type.pending }}</strong>
          </span>
        </template>

        <p v-if="items[type.key] && !items[type.key].length" class="preview-manager__empty">
          <ImageOff :size="16" /> {{ L.empty }}
        </p>

        <ul v-else class="preview-list">
          <li v-for="item in items[type.key]" :key="item.id">
            <div
              class="preview-item"
              :class="{ 'is-active': active?.key === type.key && active?.id === item.id }"
            >
              <input v-model="selected[type.key]" type="checkbox" :value="item.id" />
              <button type="button" class="preview-item__label" @click="show(type.key, item)">
                {{ item.label }}
              </button>
              <!-- Estado por locale; en estrecho, los chips saltan de fila -->
              <span class="preview-item__chips">
                <span
                  v-for="(url, locale) in item.previews"
                  :key="locale"
                  :class="['locale-chip', url ? 'is-ok' : 'is-missing']"
                  >{{ String(locale).toUpperCase() }}</span
                >
              </span>
            </div>
          </li>
        </ul>

        <div
          v-if="pages[type.key] && pages[type.key].current < pages[type.key].last"
          class="preview-manager__more"
        >
          <BaseButton variant="secondary" @click="loadItems(type.key, pages[type.key].current + 1)">
            {{ L.loadMore }}
          </BaseButton>
        </div>

        <template #actions>
          <!-- Con selección: acciones sobre el bloque; sin ella, lotes del tipo -->
          <template v-if="selected[type.key]?.length">
            <span class="preview-manager__count">
              {{ L.selectedCount.replace('{count}', String(selected[type.key].length)) }}
            </span>
            <BaseButton variant="secondary" :disabled="busy" @click="selected[type.key] = []">
              {{ L.clearSelection }}
            </BaseButton>
            <BaseButton :disabled="busy" @click="regenerateSelected(type.key)">
              {{ L.regenerateSelected }}
            </BaseButton>
            <BaseButton variant="danger" :disabled="busy" @click="deleteSelected(type.key)">
              {{ L.deleteSelected }}
            </BaseButton>
          </template>
          <template v-else>
            <BaseButton :disabled="busy || type.pending === 0" @click="generateType(type)">
              {{ L.generate }}
            </BaseButton>
            <BaseButton variant="secondary" :disabled="busy" @click="regenerateType(type)">
              {{ L.regenerateAll }}
            </BaseButton>
            <BaseButton variant="danger" :disabled="busy" @click="deleteType(type)">
              {{ L.deleteAll }}
            </BaseButton>
          </template>
        </template>
      </ManagerCard>
    </div>

    <!-- Detalle del elemento activo, en el panel derecho del layout -->
    <Teleport defer to="#right-sidebar-target">
      <div class="manager-detail">
        <p v-if="!activeItem" class="manager-detail__empty">{{ L.detailEmpty }}</p>
        <template v-else>
          <p class="manager-detail__kicker">{{ activeTypeName }}</p>
          <h3 class="manager-detail__title">{{ activeItem.label }}</h3>

          <div class="manager-detail__figures">
            <figure
              v-for="(url, locale) in activeItem.previews"
              :key="locale"
              :class="['manager-detail__figure', { 'is-missing': !url }]"
            >
              <img v-if="url" :src="url" :alt="`${activeItem.label} ${locale}`" />
              <span v-else class="manager-detail__hole"><ImageOff :size="18" /></span>
              <figcaption>{{ String(locale).toUpperCase() }}</figcaption>
            </figure>
          </div>

          <div class="manager-detail__actions">
            <BaseButton :disabled="busy" @click="regenerateItem">
              <RefreshCw :size="14" /> {{ L.itemRegenerate }}
            </BaseButton>
            <BaseButton variant="danger" :disabled="busy" @click="deleteItem">
              <Trash2 :size="14" /> {{ L.itemDelete }}
            </BaseButton>
          </div>
        </template>
      </div>
    </Teleport>
  </div>
</template>
