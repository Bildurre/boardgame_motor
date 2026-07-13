import { onBeforeUnmount, watch, type Ref } from 'vue'

/**
 * Panel flotante de los dropdowns (BaseSelect, SearchSelect).
 *
 * Con `position: absolute`, dentro de un modal el panel queda recortado por
 * el `overflow` del cuerpo (.modal__body) y además le añade scroll fantasma:
 * el scrollbar cambia el ancho interior y dispara reflows (con container
 * queries, hasta cambia la rejilla). El arreglo: al abrir, el panel se
 * promociona a la TOP LAYER del navegador con el atributo `popover` — se
 * superpone a cualquier overflow/z-index sin tocar el layout — y, como la
 * top layer posiciona respecto al viewport, se ancla por coordenadas fijas
 * al trigger y se reancla en cada scroll/resize (sigue pegado al trigger
 * también al scrollear la página o el propio modal).
 *
 * Sin soporte de popover no hace nada: sigue actuando el CSS `absolute` de
 * siempre (correcto fuera de modales).
 */
export function useDropdownPanel(
  anchor: Ref<HTMLElement | null>,
  panel: Ref<HTMLElement | null>,
  open: Ref<boolean>,
) {
  const GAP = 4 // $space-1: aire entre trigger y panel, como el CSS absolute

  /** Ancla el panel bajo el trigger (o encima, si abajo no cabe y arriba sí). */
  function place() {
    const a = anchor.value
    const p = panel.value
    if (!a || !p) return
    const r = a.getBoundingClientRect()
    p.style.width = `${r.width}px`
    p.style.left = `${r.left}px`
    const height = p.offsetHeight
    const below = window.innerHeight - r.bottom - GAP
    const openUp = height > below && r.top > window.innerHeight - r.bottom
    p.style.top = openUp ? `${Math.max(r.top - GAP - height, GAP)}px` : `${r.bottom + GAP}px`
  }

  function show() {
    const p = panel.value
    if (!p || typeof p.showPopover !== 'function') return
    // Neutraliza los estilos UA de [popover] que el CSS del componente no pisa.
    p.style.position = 'fixed'
    p.style.margin = '0'
    p.style.inset = 'auto'
    if (!p.matches(':popover-open')) p.showPopover()
    place()
    window.addEventListener('scroll', place, { capture: true, passive: true })
    window.addEventListener('resize', place)
  }

  function hide() {
    window.removeEventListener('scroll', place, true)
    window.removeEventListener('resize', place)
    // El v-if del consumidor desmonta el panel (y eso ya lo saca de la top
    // layer); hidePopover solo si sigue vivo y abierto.
    const p = panel.value
    if (p?.isConnected && p.matches(':popover-open')) p.hidePopover()
  }

  // flush post: al abrir, el panel ya está en el DOM cuando se promociona
  // (y antes del nextTick del consumidor: el focus del buscador funciona).
  watch(open, (value) => (value ? show() : hide()), { flush: 'post' })
  onBeforeUnmount(hide)
}
