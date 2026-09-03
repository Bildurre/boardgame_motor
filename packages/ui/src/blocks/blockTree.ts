// Flujo de bloques con CONTENEDORES (doc 03): el payload público llega
// plano y en preorden (`order`), con el `parent_id` de cada bloque. Anidar
// bajo un bloque normal solo afecta al índice (sangría), así que se sigue
// pintando en flujo; anidar bajo un CONTENEDOR (hoy solo `tabs`) saca a sus
// descendientes del flujo y se los entrega al contenedor, que decide cómo
// pintarlos (una pestaña por hijo directo). Helper puro: BlockFlow lo usa
// para la página y BlockTabs para cada pestaña.

/** Bloque tal cual viaja en el payload público de una página. */
export interface PageBlock {
  id: number
  parent_id?: number | null
  type?: string
  component: string
  settings: Record<string, unknown>
  data: Record<string, unknown>
}

export interface FlowEntry {
  block: PageBlock
  /** Descendientes (en preorden) si el bloque es un contenedor. */
  children?: PageBlock[]
}

/** Componentes que consumen a sus descendientes. */
export const CONTAINER_COMPONENTS = ['tabs']

/**
 * Reparte una lista en preorden: los bloques de un contenedor van dentro
 * de su entrada (todos sus descendientes, no solo los hijos directos); el
 * resto queda en flujo, sea hijo de quien sea.
 */
export function containerFlow(blocks: PageBlock[]): FlowEntry[] {
  const entries: FlowEntry[] = []
  const claimed = new Set<number>()

  blocks.forEach((block, index) => {
    if (claimed.has(block.id)) return
    if (!CONTAINER_COMPONENTS.includes(block.component)) {
      entries.push({ block })
      return
    }

    // Descendientes: en preorden son contiguos, pero se comprueba por la
    // cadena de padres (subiendo hasta el contenedor) para no depender de
    // que el orden esté perfecto.
    const inside = new Set<number>([block.id])
    const children: PageBlock[] = []
    for (const candidate of blocks.slice(index + 1)) {
      if (claimed.has(candidate.id)) continue
      if (candidate.parent_id != null && inside.has(candidate.parent_id)) {
        inside.add(candidate.id)
        children.push(candidate)
        claimed.add(candidate.id)
      }
    }
    entries.push({ block, children })
  })

  return entries
}

/** Los hijos DIRECTOS de un contenedor, cada uno con sus descendientes. */
export function groupByDirectChild(containerId: number, descendants: PageBlock[]): PageBlock[][] {
  const groups: PageBlock[][] = []
  const groupOf = new Map<number, number>()

  for (const block of descendants) {
    if (block.parent_id === containerId) {
      groupOf.set(block.id, groups.length)
      groups.push([block])
      continue
    }
    const owner = block.parent_id != null ? groupOf.get(block.parent_id) : undefined
    if (owner === undefined) continue // fuera del árbol (no debería pasar)
    groupOf.set(block.id, owner)
    groups[owner]!.push(block)
  }

  return groups
}
