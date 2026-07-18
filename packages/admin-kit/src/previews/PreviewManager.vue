<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import { Eraser, ImageOff, ImagePlus, RefreshCw, Trash2 } from '@lucide/vue'
import { BaseButton, SearchSelect, useConfirm, useToast } from '@edc-motor/ui'
import ManagerCard from '../components/ManagerCard.vue'
import { useRightSidebar } from '../composables/useRightSidebar'

// Gestor de previews PNG (doc 01/08), mobile-first. Una tarjeta FIJA por tipo
// (sin colapsar, sin listas interminables): estadísticas —total y generadas
// por idioma— y los botones "de todas" (generar faltantes / regenerar todo /
// borrar todo). Al seleccionar una tarjeta, el panel derecho (patrón kontuan)
// muestra un COMBOBOX (select desplegable con buscador) de sus elementos —
// las listas largas viven dentro del desplegable —; el elegido enseña sus
// imágenes por idioma y sus acciones (generar faltantes / regenerar /
// borrar). Endpoints /admin/previews del motor.
// Agnóstico de i18n (DC-29): textos por prop, defaults en castellano.

export interface PreviewManagerLabels {
  refresh: string
  clean: string
  total: string
  generateMissing: string
  regenerateAll: string
  deleteAll: string
  selectItem: string
  searchPlaceholder: string
  noResults: string
  loadMore: string
  panelEmpty: string
  detailTitle: string
  itemGenerateMissing: string
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
  clean: 'Limpiar huérfanos',
  total: 'Total',
  generateMissing: 'Generar faltantes',
  regenerateAll: 'Regenerar todo',
  deleteAll: 'Borrar todo',
  selectItem: 'Elige un elemento…',
  searchPlaceholder: 'Buscar…',
  noResults: 'Sin resultados.',
  loadMore: 'Cargar más',
  panelEmpty: 'Selecciona una tarjeta para gestionar sus elementos.',
  detailTitle: 'Elementos',
  itemGenerateMissing: 'Generar faltantes',
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

// El selector y el detalle viven en el panel derecho del layout.
const sidebar = useRightSidebar()
sidebar.useRegister(L.detailTitle)

interface TypeStatus {
  key: string
  model: string
  total: number
  complete: number
  pending: number
  locales: Record<string, number>
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

// Criterio de los selects del admin: sin un orden explícito, ALFABÉTICO.
// Vale también para las tarjetas, que hacen de selector de tipo.
const sortedTypes = computed(() =>
  [...types.value].sort((a, b) => typeName(a).localeCompare(typeName(b))),
)

// Tipo activo (tarjeta seleccionada) y su selector en el panel.
const activeType = ref<string | null>(null)
const search = ref('')
const items = ref<PreviewItem[]>([])
const page = ref({ current: 1, last: 1 })
const selectedId = ref<number | null>(null)

// Opciones del combobox del panel: el servidor manda por id; aquí salen
// ALFABÉTICAS (criterio general de los selects del admin sin orden propio).
const itemOptions = computed(() =>
  items.value
    .map((i) => ({ id: i.id, label: i.label }))
    .sort((a, b) => a.label.localeCompare(b.label)),
)

const activeStatus = computed(() => types.value.find((t) => t.key === activeType.value) ?? null)
const selectedItem = computed(() => items.value.find((i) => i.id === selectedId.value) ?? null)
const missingLocales = computed(() =>
  selectedItem.value
    ? Object.entries(selectedItem.value.previews)
        .filter(([, url]) => !url)
        .map(([locale]) => locale)
    : [],
)

async function loadStatus() {
  loading.value = true
  try {
    const { data } = await props.api.get('/admin/previews')
    types.value = data.data
    // El selector de tipo (las tarjetas) arranca con el PRIMERO seleccionado,
    // no vacío (sin abrir el panel: eso lo hace el click del usuario).
    if (!activeType.value && sortedTypes.value.length) activate(sortedTypes.value[0].key)
  } catch {
    toast.danger(L.error)
  } finally {
    loading.value = false
  }
}

async function loadItems(pageNumber = 1) {
  if (!activeType.value) return
  try {
    const { data } = await props.api.get(`/admin/previews/${activeType.value}/items`, {
      params: { page: pageNumber, q: search.value || undefined },
    })
    items.value = pageNumber === 1 ? data.data : [...items.value, ...data.data]
    page.value = { current: data.meta.current_page, last: data.meta.last_page }
    // El select de elemento arranca con el PRIMERO (alfabético) seleccionado,
    // no vacío; buscando o con algo ya elegido, no se toca.
    if (
      pageNumber === 1 &&
      !search.value &&
      selectedId.value === null &&
      itemOptions.value.length
    ) {
      selectedId.value = Number(itemOptions.value[0].id)
    }
  } catch {
    toast.danger(L.error)
  }
}

/** Activa un tipo: resetea búsqueda y selección y carga sus elementos. */
function activate(key: string) {
  activeType.value = key
  search.value = ''
  items.value = []
  selectedId.value = null
  loadItems()
}

function select(type: TypeStatus) {
  if (activeType.value !== type.key) activate(type.key)
  sidebar.reveal()
}

/** Búsqueda del combobox (debounce en el componente): consulta al servidor. */
function onSearch(q: string) {
  search.value = q
  loadItems()
}

async function refreshAll() {
  await loadStatus()
  if (activeType.value) await loadItems()
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

// --- Acciones "de todas" (tarjeta) ---

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

// --- Acciones del elemento elegido (panel) ---

function regenerateItem() {
  if (!activeType.value || !selectedId.value) return
  run(
    () => props.api.post(`/admin/previews/${activeType.value}/${selectedId.value}/regenerate`),
    () => refreshAll(),
  )
}

/** Encola solo los idiomas que faltan del elemento elegido. */
async function generateMissingItem() {
  if (!activeType.value || !selectedId.value) return
  busy.value = true
  let message: string | undefined
  try {
    for (const locale of missingLocales.value) {
      const { data } = await props.api.post(
        `/admin/previews/${activeType.value}/${selectedId.value}/regenerate`,
        { locale },
      )
      message = data.message ?? message
    }
    if (message) toast.success(message)
    await refreshAll()
  } catch {
    toast.danger(L.error)
  } finally {
    busy.value = false
  }
}

async function deleteItem() {
  if (!activeType.value || !selectedItem.value) return
  const ok = await confirm({
    message: L.confirmDeleteItem.replace('{name}', selectedItem.value.label),
    confirmLabel: L.itemDelete,
    cancelLabel: L.cancel,
    variant: 'danger',
  })
  if (!ok) return
  run(
    () => props.api.delete(`/admin/previews/${activeType.value}/${selectedId.value}`),
    () => refreshAll(),
  )
}

onMounted(loadStatus)
defineExpose({ refreshAll })
</script>

<template>
  <div class="preview-manager manager-container">
    <div class="manager-bar">
      <BaseButton variant="secondary" :disabled="loading || busy" @click="refreshAll">
        <template #icon><RefreshCw :size="16" /></template>
        {{ L.refresh }}
      </BaseButton>
      <BaseButton variant="danger" :disabled="busy" @click="cleanOrphans">
        <template #icon><Eraser :size="16" /></template>
        {{ L.clean }}
      </BaseButton>
    </div>

    <div class="manager-grid">
      <ManagerCard
        v-for="type in sortedTypes"
        :key="type.key"
        :title="typeName(type)"
        :active="activeType === type.key"
        @select="select(type)"
      >
        <!-- Como EntityCard: badges (generadas por idioma) arriba, meta (total) debajo -->
        <template #badges>
          <span
            v-for="(count, locale) in type.locales"
            :key="locale"
            :class="['chip', count === type.total ? 'is-ok' : 'is-missing']"
            >{{ String(locale).toUpperCase() }} {{ count }}/{{ type.total }}</span
          >
        </template>
        <template #meta>
          <span class="manager-stat"
            >{{ L.total }} <strong>{{ type.total }}</strong></span
          >
        </template>
      </ManagerCard>
    </div>

    <!-- Panel derecho: selector con buscador + detalle del elemento -->
    <Teleport defer to="#right-sidebar-target">
      <div class="manager-panel">
        <p v-if="!activeStatus" class="manager-panel__empty">{{ L.panelEmpty }}</p>
        <template v-else>
          <p class="manager-panel__kicker">{{ typeName(activeStatus) }}</p>

          <!-- Acciones PRIMERO; después, secciones separadas (patrón panel) -->
          <div class="manager-detail__actions">
            <BaseButton
              :disabled="busy || activeStatus.pending === 0"
              @click="generateType(activeStatus)"
            >
              <template #icon><ImagePlus :size="14" /></template>
              {{ L.generateMissing }}
            </BaseButton>
            <BaseButton variant="info" :disabled="busy" @click="regenerateType(activeStatus)">
              <template #icon><RefreshCw :size="14" /></template>
              {{ L.regenerateAll }}
            </BaseButton>
            <BaseButton variant="danger" :disabled="busy" @click="deleteType(activeStatus)">
              <template #icon><Trash2 :size="14" /></template>
              {{ L.deleteAll }}
            </BaseButton>
          </div>

          <hr class="manager-panel__divider" />

          <SearchSelect
            :model-value="selectedId"
            :options="itemOptions"
            :placeholder="L.selectItem"
            :search-placeholder="L.searchPlaceholder"
            :no-results="L.noResults"
            :load-more-label="L.loadMore"
            :can-load-more="page.current < page.last"
            @update:model-value="(id) => (selectedId = Number(id))"
            @search="onSearch"
            @load-more="loadItems(page.current + 1)"
          />

          <div v-if="selectedItem" class="manager-detail">
            <h3 class="manager-detail__title">{{ selectedItem.label }}</h3>

            <div class="manager-detail__figures">
              <figure
                v-for="(url, locale) in selectedItem.previews"
                :key="locale"
                :class="['manager-detail__figure', { 'is-missing': !url }]"
              >
                <img v-if="url" :src="url" :alt="`${selectedItem.label} ${locale}`" />
                <span v-else class="manager-detail__hole"><ImageOff :size="18" /></span>
                <figcaption>{{ String(locale).toUpperCase() }}</figcaption>
              </figure>
            </div>

            <div class="manager-detail__actions">
              <BaseButton
                v-if="missingLocales.length"
                :disabled="busy"
                @click="generateMissingItem"
              >
                <template #icon><ImagePlus :size="14" /></template>
                {{ L.itemGenerateMissing }}
              </BaseButton>
              <BaseButton variant="info" :disabled="busy" @click="regenerateItem">
                <template #icon><RefreshCw :size="14" /></template>
                {{ L.itemRegenerate }}
              </BaseButton>
              <BaseButton variant="danger" :disabled="busy" @click="deleteItem">
                <template #icon><Trash2 :size="14" /></template>
                {{ L.itemDelete }}
              </BaseButton>
            </div>
          </div>
        </template>
      </div>
    </Teleport>
  </div>
</template>
