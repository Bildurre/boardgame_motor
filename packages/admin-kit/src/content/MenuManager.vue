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
  GripVertical,
  RotateCcw,
  Save,
} from '@lucide/vue'
import { BaseButton, useToast } from '@edc-motor/ui'

// Configurador del menú de la web pública (doc 10 ampliado, rediseño sin
// grupos): "si quieres un grupo, haz una página" — la jerarquía SIEMPRE es
// la del CRM (una página con hijas hace de desplegable); una ruta también
// puede colgar de una página raíz. El gestor trabaja sobre una copia LOCAL
// del árbol (flechas, drag & drop nativo y el interruptor de visibilidad
// solo mutan el estado local) y no persiste NADA hasta pulsar "Guardar",
// que manda el árbol entero con `PUT /admin/menu`. "Descartar" recarga del
// servidor. Agnóstico de i18n (DC-29): textos por prop; `routeLabels` los
// pone el juego y `displayLocale` el idioma actual del admin (con fallback
// al primer valor no vacío, patrón `firstText` del AppHeader público).

export interface MenuManagerLabels {
  empty: string
  hidden: string
  draft: string
  moveUp: string
  moveDown: string
  visible: string
  save: string
  discard: string
  unsaved: string
  saved: string
  error: string
}

const defaultLabels: MenuManagerLabels = {
  empty: 'El menú aún no tiene elementos.',
  hidden: 'Oculto',
  draft: 'Borrador',
  moveUp: 'Subir',
  moveDown: 'Bajar',
  visible: 'Visible',
  save: 'Guardar',
  discard: 'Descartar',
  unsaved: 'Cambios sin guardar',
  saved: 'Menú guardado.',
  error: 'No se ha podido completar la acción.',
}

const props = withDefaults(
  defineProps<{
    api: AxiosInstance
    /** Etiqueta visible de cada route_key que el juego ofrece al menú. */
    routeLabels?: Record<string, string>
    /** Idioma actual del admin (vue-i18n): pinta los títulos de página en él. */
    displayLocale: string
    labels?: Partial<MenuManagerLabels>
  }>(),
  { routeLabels: () => ({}), labels: () => ({}) },
)

const L = reactive({ ...defaultLabels, ...props.labels }) as MenuManagerLabels

const toast = useToast()

interface MenuPageInfo {
  id: number
  title: Record<string, string>
  is_published: boolean
}

interface MenuNode {
  id: number
  type: 'page' | 'route'
  is_visible: boolean
  order: number
  route_key: string | null
  page: MenuPageInfo | null
  children: MenuNode[]
}

const tree = ref<MenuNode[]>([])
const busy = ref(false)
const saving = ref(false)
// Foto del último estado GUARDADO (servidor): compara contra el árbol local
// para saber si hay cambios sin guardar (badge + aviso al salir).
const savedSnapshot = ref('')

/** Aplana el árbol (padre seguido de sus hijas) al formato del PUT. */
function flatten(
  nodes: MenuNode[],
  parentId: number | null,
): { id: number; parent_id: number | null; is_visible: boolean }[] {
  const out: { id: number; parent_id: number | null; is_visible: boolean }[] = []
  for (const node of nodes) {
    out.push({ id: node.id, parent_id: parentId, is_visible: node.is_visible })
    out.push(...flatten(node.children, node.id))
  }
  return out
}

const isDirty = computed(() => JSON.stringify(flatten(tree.value, null)) !== savedSnapshot.value)

async function load() {
  busy.value = true
  try {
    const { data } = await props.api.get('/admin/menu')
    tree.value = data.data
    savedSnapshot.value = JSON.stringify(flatten(tree.value, null))
  } catch {
    toast.danger(L.error)
  } finally {
    busy.value = false
  }
}

async function save() {
  saving.value = true
  try {
    const { data } = await props.api.put('/admin/menu', { items: flatten(tree.value, null) })
    tree.value = data.data
    savedSnapshot.value = JSON.stringify(flatten(tree.value, null))
    toast.success(L.saved)
  } catch {
    toast.danger(L.error)
  } finally {
    saving.value = false
  }
}

function discard() {
  load()
}

// Filas aplanadas para pintar (profundidad máxima 1: hijas de una página
// raíz, indentadas justo debajo de ella — una ruta nunca es madre).
interface Row {
  node: MenuNode
  depth: 0 | 1
  parentId: number | null
}
const rows = computed<Row[]>(() => {
  const out: Row[] = []
  function walk(nodes: MenuNode[], depth: 0 | 1, parentId: number | null) {
    for (const node of nodes) {
      out.push({ node, depth, parentId })
      if (node.children.length) walk(node.children, 1, node.id)
    }
  }
  walk(tree.value, 0, null)
  return out
})

function listFor(parentId: number | null): MenuNode[] | null {
  if (parentId === null) return tree.value
  const parent = findNode(tree.value, parentId)
  return parent ? parent.children : null
}

function findNode(nodes: MenuNode[], id: number): MenuNode | null {
  for (const node of nodes) {
    if (node.id === id) return node
    const found = findNode(node.children, id)
    if (found) return found
  }
  return null
}

function siblingIndex(row: Row): number {
  return (listFor(row.parentId) ?? []).findIndex((n) => n.id === row.node.id)
}
function canMoveUp(row: Row): boolean {
  return siblingIndex(row) > 0
}
function canMoveDown(row: Row): boolean {
  const list = listFor(row.parentId)
  if (!list) return false
  const idx = siblingIndex(row)
  return idx >= 0 && idx < list.length - 1
}

/** Subir/bajar: solo reordena dentro del mismo nivel (no cambia de madre). */
function move(row: Row, direction: 'up' | 'down') {
  const list = listFor(row.parentId)
  if (!list) return
  const idx = list.findIndex((n) => n.id === row.node.id)
  const swapWith = direction === 'up' ? idx - 1 : idx + 1
  if (idx < 0 || swapWith < 0 || swapWith >= list.length) return
  ;[list[idx], list[swapWith]] = [list[swapWith], list[idx]]
}

function toggleVisible(row: Row) {
  row.node.is_visible = !row.node.is_visible
}

/** Primer texto disponible en `displayLocale`, con fallback al primero que haya. */
function displayText(map: Record<string, string> | null | undefined): string {
  if (!map) return ''
  return map[props.displayLocale] || Object.values(map).find(Boolean) || ''
}

function labelOf(node: MenuNode): string {
  if (node.type === 'page') return node.page ? displayText(node.page.title) : ''
  return props.routeLabels[node.route_key ?? ''] ?? node.route_key ?? ''
}

// --- Drag & drop nativo (HTML5, sin dependencias) --------------------------
// Fila arrastrable con asa (GripVertical): soltar en el tercio superior/
// inferior de otra fila la reordena como hermana (antes/después) en el
// nivel de la fila destino; soltar en el tercio central de una página RAÍZ
// la convierte en su madre (si es válido). Arrastrar una hija hasta el
// hueco entre filas raíz la saca a la raíz (es "antes/después" de una fila
// con depth 0). Restricciones de un nivel: una página CON hijas no puede
// pasar a ser hija de nadie; una ruta nunca es madre.
const dragged = ref<{ node: MenuNode; parentId: number | null } | null>(null)
const dropTarget = ref<{
  id: number
  parentId: number | null
  position: 'before' | 'after' | 'inside'
} | null>(null)

function canNestInside(row: Row): boolean {
  if (!dragged.value) return false
  if (row.node.type !== 'page' || row.depth !== 0) return false
  if (row.node.id === dragged.value.node.id) return false
  return dragged.value.node.children.length === 0
}

function canPlaceSibling(row: Row): boolean {
  if (!dragged.value) return false
  if (row.node.id === dragged.value.node.id) return false
  // Hija de nadie si el arrastrado tiene sus propias hijas (un solo nivel).
  if (row.depth === 1 && dragged.value.node.children.length > 0) return false
  return true
}

function onDragStart(row: Row, event: DragEvent) {
  dragged.value = { node: row.node, parentId: row.parentId }
  event.dataTransfer?.setData('text/plain', String(row.node.id))
  if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move'
}

function onDragOver(row: Row, event: DragEvent) {
  if (!dragged.value || dragged.value.node.id === row.node.id) {
    dropTarget.value = null
    return
  }
  const rect = (event.currentTarget as HTMLElement).getBoundingClientRect()
  const ratio = (event.clientY - rect.top) / rect.height
  const nestable = canNestInside(row)

  let position: 'before' | 'after' | 'inside'
  if (nestable && ratio > 0.3 && ratio < 0.7) {
    position = 'inside'
  } else {
    if (!canPlaceSibling(row)) {
      dropTarget.value = null
      return
    }
    position = ratio < 0.5 ? 'before' : 'after'
  }
  dropTarget.value = { id: row.node.id, parentId: row.parentId, position }
}

function onDragLeave(row: Row, event: DragEvent) {
  const related = event.relatedTarget as Node | null
  const current = event.currentTarget as HTMLElement
  if (related && current.contains(related)) return
  if (dropTarget.value?.id === row.node.id) dropTarget.value = null
}

function onDrop(row: Row, event: DragEvent) {
  event.preventDefault()
  const target = dropTarget.value
  const source = dragged.value
  cleanupDrag()
  if (!target || !source || target.id !== row.node.id) return
  applyDrop(source, target)
}

function onDragEnd() {
  cleanupDrag()
}

function cleanupDrag() {
  dragged.value = null
  dropTarget.value = null
}

function applyDrop(
  source: { node: MenuNode; parentId: number | null },
  target: { id: number; parentId: number | null; position: 'before' | 'after' | 'inside' },
) {
  const sourceList = listFor(source.parentId)
  if (!sourceList) return
  const sourceIdx = sourceList.findIndex((n) => n.id === source.node.id)
  if (sourceIdx === -1) return
  sourceList.splice(sourceIdx, 1)

  if (target.position === 'inside') {
    const targetNode = findNode(tree.value, target.id)
    targetNode?.children.push(source.node)
    return
  }

  const destList = listFor(target.parentId)
  if (!destList) {
    // No debería pasar (el destino existe); por si acaso, no se pierde el nodo.
    sourceList.splice(sourceIdx, 0, source.node)
    return
  }
  let destIdx = destList.findIndex((n) => n.id === target.id)
  if (destIdx === -1) {
    destList.push(source.node)
    return
  }
  if (target.position === 'after') destIdx += 1
  destList.splice(destIdx, 0, source.node)
}

onMounted(load)

defineExpose({ isDirty })
</script>

<template>
  <div class="menu-manager">
    <div class="menu-manager__bar">
      <span v-if="isDirty" class="menu-manager__dirty">{{ L.unsaved }}</span>
      <BaseButton variant="secondary" :disabled="!isDirty || saving || busy" @click="discard">
        <template #icon><RotateCcw :size="16" /></template>
        {{ L.discard }}
      </BaseButton>
      <BaseButton :disabled="!isDirty || saving || busy" @click="save">
        <template #icon><Save :size="16" /></template>
        {{ L.save }}
      </BaseButton>
    </div>

    <p v-if="!busy && !rows.length" class="menu-manager__empty">{{ L.empty }}</p>

    <div class="menu-manager__list">
      <article
        v-for="row in rows"
        :key="row.node.id"
        class="menu-manager__item"
        draggable="true"
        :class="{
          'is-child': row.depth === 1,
          'is-hidden': !row.node.is_visible,
          'is-dragging': dragged?.node.id === row.node.id,
          'drop-before': dropTarget?.id === row.node.id && dropTarget.position === 'before',
          'drop-after': dropTarget?.id === row.node.id && dropTarget.position === 'after',
          'drop-inside': dropTarget?.id === row.node.id && dropTarget.position === 'inside',
        }"
        @dragstart="onDragStart(row, $event)"
        @dragover.prevent="onDragOver(row, $event)"
        @dragleave="onDragLeave(row, $event)"
        @drop="onDrop(row, $event)"
        @dragend="onDragEnd"
      >
        <span class="menu-manager__grip"><GripVertical :size="15" /></span>

        <span class="menu-manager__icon">
          <File v-if="row.node.type === 'page'" :size="16" />
          <Compass v-else :size="16" />
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
            :disabled="!canMoveUp(row)"
            @click="move(row, 'up')"
          >
            <ArrowUp :size="14" />
          </button>
          <button
            type="button"
            class="menu-manager__move"
            :title="L.moveDown"
            :aria-label="L.moveDown"
            :disabled="!canMoveDown(row)"
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
          @click="toggleVisible(row)"
        >
          <Eye v-if="row.node.is_visible" :size="15" />
          <EyeOff v-else :size="15" />
        </button>
      </article>
    </div>
  </div>
</template>
