import { computed, onBeforeUnmount, ref } from 'vue'

/**
 * Barra lateral derecha contextual de la web pública, por vista (misma
 * mecánica que el useRightSidebar del admin-kit, patrón kontuan).
 *
 * Estado singleton a nivel de módulo (como useToast/useConfirm: los juegos
 * consumen el paquete como fuente con `optimizeDeps.exclude` para que solo
 * exista una copia):
 *   - escritorio: columna junto al contenido cuando la vista actual registra
 *     contenido; se puede plegar con `collapsed`.
 *   - estrecho: oculta; entra como drawer superpuesto cuando `mobileOpen`.
 *
 * El botón del header (Funnel) llama a `toggle()`, que despliega/pliega según
 * el modo; `overlay` lo mantiene el componente AppRightSidebar con el ancho.
 *
 * La propiedad se rastrea con un token opaco para que el `unregister` de una
 * vista saliente nunca borre el contenido cuando otra vista ya ha registrado
 * el suyo (en un cambio de ruta, el setup nuevo corre antes que el
 * onUnmounted viejo).
 */
const hasContent = ref(false)
const collapsed = ref(false)
const mobileOpen = ref(false)
const overlay = ref(false)
const title = ref<string>('')

let ownerToken: symbol | null = null

export type AppRightSidebarToken = symbol

export function useAppRightSidebar() {
  /** Visible ahora mismo (columna desplegada o drawer abierto). */
  const isOpen = computed(() => (overlay.value ? mobileOpen.value : !collapsed.value))

  function toggleCollapsed() {
    collapsed.value = !collapsed.value
  }
  function openMobile() {
    mobileOpen.value = true
  }
  function closeMobile() {
    mobileOpen.value = false
  }
  function toggleMobile() {
    mobileOpen.value = !mobileOpen.value
  }

  /** Abre/cierra según el modo: el botón del header no distingue anchos. */
  function toggle() {
    if (overlay.value) toggleMobile()
    else toggleCollapsed()
  }

  /** Muestra la barra esté donde esté (despliega en ancho, abre en estrecho). */
  function reveal() {
    collapsed.value = false
    mobileOpen.value = true
  }

  function register(panelTitle = '', token?: AppRightSidebarToken): AppRightSidebarToken {
    const owned = token ?? Symbol('app-right-sidebar-owner')
    ownerToken = owned
    hasContent.value = true
    title.value = panelTitle
    return owned
  }

  /** Limpia el contenido solo si quien llama sigue siendo el dueño. */
  function unregister(token?: AppRightSidebarToken) {
    if (token !== undefined && token !== ownerToken) return
    ownerToken = null
    hasContent.value = false
    title.value = ''
    mobileOpen.value = false
  }

  function useRegister(panelTitle = ''): AppRightSidebarToken {
    const token = register(panelTitle)
    onBeforeUnmount(() => unregister(token))
    return token
  }

  return {
    hasContent,
    collapsed,
    mobileOpen,
    overlay,
    title,
    isOpen,
    toggleCollapsed,
    openMobile,
    closeMobile,
    toggleMobile,
    toggle,
    reveal,
    register,
    unregister,
    useRegister,
  }
}
