import { onBeforeUnmount, onMounted } from 'vue'

// Zonas que NUNCA deseleccionan: las propias cards y cualquier control
// interactivo (enlaces, botones, campos, labels…) o las migas de pan.
const IGNORE =
  'a, button, input, textarea, select, label, [contenteditable], ' +
  '.manager-card, .entity-card, .breadcrumbs'

/**
 * Deselección de la card activa clickando la zona "vacía" del cuerpo de la
 * vista (el contenido bajo las migas: huecos del grid, espacio bajo las
 * cards, alrededor del toolbar…). Complementa el patrón de los listados con
 * panel derecho: la card entera selecciona y el resto del contenedor
 * deselecciona (además del botón "volver a los filtros" del panel).
 *
 * Escucha en document y solo actúa dentro de `.main-content` (el área de
 * contenido del AdminLayout): los modales (teleport a body) y el panel
 * derecho quedan fuera. Ignora los clicks que nacen en una card o en un
 * control interactivo; `extraIgnore` añade selectores propios de la vista
 * (p. ej. sus items cuando no son .manager-card/.entity-card).
 */
export function useCardDeselect(onDeselect: () => void, extraIgnore = '') {
  function onClick(e: MouseEvent) {
    const target = e.target instanceof Element ? e.target : null
    if (!target || !target.closest('.main-content')) return
    if (target.closest(IGNORE)) return
    if (extraIgnore && target.closest(extraIgnore)) return
    onDeselect()
  }
  onMounted(() => document.addEventListener('click', onClick))
  onBeforeUnmount(() => document.removeEventListener('click', onClick))
}
