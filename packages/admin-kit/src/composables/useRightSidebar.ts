import { onBeforeUnmount, ref } from 'vue'

/**
 * Panel lateral derecho contextual, por vista (patrón kontuan).
 *
 * Espejo del modelo de la navegación izquierda:
 *   - escritorio: visible cuando la vista actual registra contenido;
 *     se puede plegar con `collapsed`.
 *   - móvil: oculto; entra como drawer cuando `mobileOpen` es true.
 *
 * La propiedad se rastrea con un token opaco para que el `unregister` de una
 * vista saliente nunca borre el panel cuando otra vista ya ha registrado el
 * suyo (en un cambio de ruta, el setup nuevo corre antes que el onUnmounted
 * viejo).
 */
const hasContent = ref(false)
const collapsed = ref(false)
const mobileOpen = ref(false)
const title = ref<string>('')
const handleFlashId = ref(0)

let ownerToken: symbol | null = null

export type RightSidebarToken = symbol

export function useRightSidebar() {
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

  /** Destello del asa cuando llega contenido nuevo con el panel oculto. */
  function flashHandle() {
    handleFlashId.value++
  }

  /** Muestra el panel esté donde esté (despliega en escritorio, abre en móvil). */
  function reveal() {
    collapsed.value = false
    mobileOpen.value = true
  }

  function register(panelTitle = '', token?: RightSidebarToken): RightSidebarToken {
    const owned = token ?? Symbol('right-sidebar-owner')
    ownerToken = owned
    hasContent.value = true
    title.value = panelTitle
    return owned
  }

  /** Limpia el panel solo si quien llama sigue siendo el dueño. */
  function unregister(token?: RightSidebarToken) {
    if (token !== undefined && token !== ownerToken) return
    ownerToken = null
    hasContent.value = false
    title.value = ''
    mobileOpen.value = false
  }

  function useRegister(panelTitle = ''): RightSidebarToken {
    const token = register(panelTitle)
    onBeforeUnmount(() => unregister(token))
    return token
  }

  return {
    hasContent,
    collapsed,
    mobileOpen,
    title,
    handleFlashId,
    toggleCollapsed,
    openMobile,
    closeMobile,
    toggleMobile,
    flashHandle,
    reveal,
    register,
    unregister,
    useRegister,
  }
}
