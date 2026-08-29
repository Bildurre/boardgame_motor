import { onBeforeUnmount, watch, type Ref } from 'vue'

// Bloqueo del scroll de la PÁGINA mientras un drawer superpuesto está
// abierto (barra derecha en modo overlay, panel de navegación móvil…): sin
// esto, en el teléfono el gesto de scroll dentro del drawer «se escapa» al
// fondo y la página se mueve por debajo del telón. Contador global: varios
// drawers pueden bloquear a la vez y el scroll vuelve cuando el ÚLTIMO
// suelta. Se bloquea el <html> (overflow hidden), que es quien scrollea.
let locks = 0

function apply(): void {
  if (typeof document === 'undefined') return
  document.documentElement.style.overflow = locks > 0 ? 'hidden' : ''
}

/** Bloquea el scroll de fondo mientras `open` sea true (se libera solo al desmontar). */
export function useScrollLock(open: Ref<boolean>): void {
  let holding = false

  const set = (value: boolean) => {
    if (value === holding) return
    holding = value
    locks += value ? 1 : -1
    apply()
  }

  watch(open, set, { immediate: true })
  onBeforeUnmount(() => set(false))
}
