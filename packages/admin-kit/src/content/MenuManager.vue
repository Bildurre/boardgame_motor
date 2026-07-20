<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import type { AxiosInstance } from 'axios'
import {
  ArrowDown,
  ArrowUp,
  Compass,
  Eye,
  EyeOff,
  File,
  Folder,
  Plus,
  SquarePen,
  Trash2,
} from '@lucide/vue'
import {
  BaseButton,
  BaseSelect,
  EditModal,
  TranslatableInput,
  useConfirm,
  useToast,
} from '@edc-motor/ui'

// Configurador del menú de la web pública (doc 10 ampliado): mezcla páginas
// del CRM y "rutas" propias del juego bajo un árbol de UN nivel (los grupos
// son carpetas del admin; una página o ruta puede colgar de un grupo, un
// grupo no puede colgar de otro). Filas tipo PageBlocks (no cards):
// subir/bajar dentro de su nivel, interruptor de visibilidad, select de
// grupo y, en grupos, editar label + borrar. Agnóstico de i18n (DC-29):
// todos los textos por prop; `routeLabels` los pone el juego.

export interface MenuManagerLabels {
  newGroup: string
  newGroupTitle: string
  editGroupTitle: string
  groupLabel: string
  save: string
  cancel: string
  delete: string
  confirmDelete: string
  empty: string
  root: string
  hidden: string
  draft: string
  moveUp: string
  moveDown: string
  visible: string
  group: string
  error: string
}

const defaultLabels: MenuManagerLabels = {
  newGroup: 'Nuevo grupo',
  newGroupTitle: 'Nuevo grupo',
  editGroupTitle: 'Editar grupo',
  groupLabel: 'Nombre del grupo',
  save: 'Guardar',
  cancel: 'Cancelar',
  delete: 'Borrar',
  confirmDelete: '¿Borrar este grupo? Sus elementos pasarán a la raíz.',
  empty: 'El menú aún no tiene elementos.',
  root: '— Raíz —',
  hidden: 'Oculto',
  draft: 'Borrador',
  moveUp: 'Subir',
  moveDown: 'Bajar',
  visible: 'Visible',
  group: 'Grupo',
  error: 'No se ha podido completar la acción.',
}

const props = withDefaults(
  defineProps<{
    api: AxiosInstance
    locales: { code: string; name: string }[]
    /** Etiqueta visible de cada route_key que el juego ofrece al menú. */
    routeLabels?: Record<string, string>
    labels?: Partial<MenuManagerLabels>
  }>(),
  { routeLabels: () => ({}), labels: () => ({}) },
)

const L = reactive({ ...defaultLabels, ...props.labels }) as MenuManagerLabels

const toast = useToast()
const { confirm } = useConfirm()

interface MenuPageInfo {
  id: number
  title: Record<string, string>
  is_published: boolean
}

interface MenuNode {
  id: number
  type: 'page' | 'route' | 'group'
  is_visible: boolean
  order: number
  route_key: string | null
  label: Record<string, string> | null
  page: MenuPageInfo | null
  children: MenuNode[]
}

const tree = ref<MenuNode[]>([])
const busy = ref(false)

async function load() {
  try {
    const { data } = await props.api.get('/admin/menu')
    tree.value = data.data
  } catch {
    toast.danger(L.error)
  }
}

// Filas aplanadas para pintar (profundidad máxima 1: hijos de un grupo,
// indentados justo debajo de él — un grupo no puede colgar de otro).
interface Row {
  node: MenuNode
  depth: 0 | 1
  parentId: number | null
}
const rows = computed<Row[]>(() => {
  const out: Row[] = []
  for (const node of tree.value) {
    out.push({ node, depth: 0, parentId: null })
    if (node.type === 'group') {
      for (const child of node.children) out.push({ node: child, depth: 1, parentId: node.id })
    }
  }
  return out
})

const groups = computed(() => tree.value.filter((n) => n.type === 'group'))

function siblingIds(parentId: number | null): number[] {
  if (parentId === null) return tree.value.map((n) => n.id)
  return groups.value.find((g) => g.id === parentId)?.children.map((c) => c.id) ?? []
}

function siblingIndex(row: Row): number {
  return siblingIds(row.parentId).indexOf(row.node.id)
}

function canMoveUp(row: Row): boolean {
  return siblingIndex(row) > 0
}
function canMoveDown(row: Row): boolean {
  const ids = siblingIds(row.parentId)
  const idx = siblingIndex(row)
  return idx >= 0 && idx < ids.length - 1
}

/** Primer texto disponible (agnóstico de locale, patrón EntityRefSelect). */
function firstText(map: Record<string, string> | null | undefined): string {
  if (!map) return ''
  return Object.values(map).find(Boolean) ?? ''
}

function labelOf(node: MenuNode): string {
  if (node.type === 'page') return node.page ? firstText(node.page.title) : ''
  if (node.type === 'route') return props.routeLabels[node.route_key ?? ''] ?? node.route_key ?? ''
  return firstText(node.label)
}

async function move(row: Row, direction: 'up' | 'down') {
  const ids = siblingIds(row.parentId)
  const idx = ids.indexOf(row.node.id)
  const swapWith = direction === 'up' ? idx - 1 : idx + 1
  if (idx < 0 || swapWith < 0 || swapWith >= ids.length) return
  const next = [...ids]
  ;[next[idx], next[swapWith]] = [next[swapWith], next[idx]]
  busy.value = true
  try {
    await props.api.post('/admin/menu/reorder', { ids: next })
    await load()
  } catch {
    toast.danger(L.error)
  } finally {
    busy.value = false
  }
}

async function toggleVisible(row: Row) {
  busy.value = true
  try {
    await props.api.patch(`/admin/menu/${row.node.id}`, { is_visible: !row.node.is_visible })
    await load()
  } catch {
    toast.danger(L.error)
  } finally {
    busy.value = false
  }
}

async function setGroup(row: Row, value: string) {
  busy.value = true
  try {
    await props.api.patch(`/admin/menu/${row.node.id}`, {
      parent_id: value ? Number(value) : null,
    })
    await load()
  } catch {
    toast.danger(L.error)
  } finally {
    busy.value = false
  }
}

// Modal de grupo (crear / editar label), compartido.
const modalOpen = ref(false)
const editingGroup = ref<MenuNode | null>(null)
const groupLabelForm = ref<Record<string, string>>({})
const saving = ref(false)

function openCreateGroup() {
  editingGroup.value = null
  groupLabelForm.value = {}
  modalOpen.value = true
}

function openEditGroup(node: MenuNode) {
  editingGroup.value = node
  groupLabelForm.value = { ...(node.label ?? {}) }
  modalOpen.value = true
}

async function saveGroup() {
  saving.value = true
  try {
    if (editingGroup.value) {
      await props.api.patch(`/admin/menu/${editingGroup.value.id}`, { label: groupLabelForm.value })
    } else {
      await props.api.post('/admin/menu/groups', { label: groupLabelForm.value })
    }
    modalOpen.value = false
    await load()
  } catch {
    toast.danger(L.error)
  } finally {
    saving.value = false
  }
}

async function removeGroup(node: MenuNode) {
  const ok = await confirm({
    message: L.confirmDelete,
    confirmLabel: L.delete,
    cancelLabel: L.cancel,
    variant: 'danger',
  })
  if (!ok) return
  busy.value = true
  try {
    await props.api.delete(`/admin/menu/${node.id}`)
    await load()
  } catch {
    toast.danger(L.error)
  } finally {
    busy.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="menu-manager">
    <div class="menu-manager__bar">
      <BaseButton :disabled="busy" @click="openCreateGroup">
        <template #icon><Plus :size="16" /></template>
        {{ L.newGroup }}
      </BaseButton>
    </div>

    <p v-if="!rows.length" class="menu-manager__empty">{{ L.empty }}</p>

    <div class="menu-manager__list">
      <article
        v-for="row in rows"
        :key="row.node.id"
        class="menu-manager__item"
        :class="{ 'is-child': row.depth === 1, 'is-hidden': !row.node.is_visible }"
      >
        <span class="menu-manager__icon">
          <File v-if="row.node.type === 'page'" :size="16" />
          <Compass v-else-if="row.node.type === 'route'" :size="16" />
          <Folder v-else :size="16" />
        </span>

        <span class="menu-manager__label">{{ labelOf(row.node) }}</span>

        <span class="menu-manager__badges">
          <span v-if="!row.node.is_visible" class="chip is-missing">{{ L.hidden }}</span>
          <span
            v-if="row.node.type === 'page' && row.node.page && !row.node.page.is_published"
            class="chip is-missing"
          >
            {{ L.draft }}
          </span>
        </span>

        <span class="menu-manager__moves">
          <button
            type="button"
            class="menu-manager__move"
            :title="L.moveUp"
            :aria-label="L.moveUp"
            :disabled="busy || !canMoveUp(row)"
            @click="move(row, 'up')"
          >
            <ArrowUp :size="14" />
          </button>
          <button
            type="button"
            class="menu-manager__move"
            :title="L.moveDown"
            :aria-label="L.moveDown"
            :disabled="busy || !canMoveDown(row)"
            @click="move(row, 'down')"
          >
            <ArrowDown :size="14" />
          </button>
        </span>

        <button
          type="button"
          class="menu-manager__visibility"
          :class="row.node.is_visible ? 'is-on' : 'is-off'"
          :aria-pressed="row.node.is_visible"
          :title="L.visible"
          :disabled="busy"
          @click="toggleVisible(row)"
        >
          <Eye v-if="row.node.is_visible" :size="15" />
          <EyeOff v-else :size="15" />
        </button>

        <BaseSelect
          v-if="row.node.type !== 'group'"
          class="menu-manager__group-select"
          :model-value="row.parentId === null ? '' : String(row.parentId)"
          :options="[
            { value: '', label: L.root },
            ...groups.map((g) => ({ value: String(g.id), label: labelOf(g) })),
          ]"
          @update:model-value="(v: string) => setGroup(row, v)"
        />

        <span v-if="row.node.type === 'group'" class="menu-manager__group-actions">
          <button
            type="button"
            class="menu-manager__edit"
            :title="L.editGroupTitle"
            :aria-label="L.editGroupTitle"
            @click="openEditGroup(row.node)"
          >
            <SquarePen :size="15" />
          </button>
          <button
            type="button"
            class="menu-manager__delete"
            :title="L.delete"
            :aria-label="L.delete"
            @click="removeGroup(row.node)"
          >
            <Trash2 :size="15" />
          </button>
        </span>
      </article>
    </div>

    <EditModal
      v-model="modalOpen"
      :title="editingGroup ? L.editGroupTitle : L.newGroupTitle"
      :submit-label="L.save"
      :cancel-label="L.cancel"
      :loading="saving"
      @submit="saveGroup"
    >
      <TranslatableInput
        v-model="groupLabelForm"
        :locales="locales"
        :label="L.groupLabel"
        required
      />
    </EditModal>
  </div>
</template>
