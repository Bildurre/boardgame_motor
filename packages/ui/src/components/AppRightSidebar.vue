<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted } from 'vue'
import { X } from '@lucide/vue'
import { useAppRightSidebar } from '../composables/useAppRightSidebar'

// Barra lateral derecha contextual de la web pública (misma mecánica que la
// RightSidebar del admin-kit): cada vista la activa con
// useAppRightSidebar().useRegister(titulo) y teletransporta su contenido
// (típicamente sus selects de filtros) a #app-right-sidebar-target. En ancho
// es una columna pegajosa junto al contenido; por debajo de
// OVERLAY_BREAKPOINT pasa a drawer superpuesto con cierre por click fuera y
// Escape. Se abre/cierra con el botón (Funnel) del header, que llama a
// useAppRightSidebar().toggle(). La monta App.vue una vez, dentro de
// .site-main; el cascarón fija --app-right-sidebar-top a la altura de su
// cabecera fija.
// Agnóstica de i18n (DC-29): textos por prop, defaults en castellano.

const props = withDefaults(
  defineProps<{
    closeLabel?: string
    /** Título por defecto si la vista registra sin título. */
    fallbackTitle?: string
  }>(),
  { closeLabel: 'Cerrar el panel', fallbackTitle: 'Filtros' },
)

const { hasContent, collapsed, mobileOpen, overlay, title, isOpen, toggle, closeMobile } =
  useAppRightSidebar()

// Por debajo de este ancho la barra se superpone (drawer) en vez de ocupar
// columna: coincide con el corte móvil del header público (900px).
const OVERLAY_BREAKPOINT = 900

function checkOverlay() {
  overlay.value = window.innerWidth < OVERLAY_BREAKPOINT
  if (!overlay.value) mobileOpen.value = false
}

// En el drawer, Escape cierra (el click fuera lo cubre el telón).
function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && overlay.value && mobileOpen.value) closeMobile()
}

onMounted(() => {
  checkOverlay()
  window.addEventListener('resize', checkOverlay)
  window.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', checkOverlay)
  window.removeEventListener('keydown', onKeydown)
})

const panelTitle = computed(() => title.value || props.fallbackTitle)

function handleClose(e: MouseEvent) {
  // Evita dejar el foco dentro de un subárbol inert.
  ;(e.currentTarget as HTMLElement | null)?.blur()
  toggle()
}
</script>

<template>
  <div
    v-if="overlay && hasContent && mobileOpen"
    class="app-right-sidebar-overlay"
    @click="closeMobile"
  />

  <aside
    v-show="hasContent"
    class="app-right-sidebar"
    :class="{
      'app-right-sidebar--collapsed': collapsed && !overlay,
      'app-right-sidebar--drawer': overlay,
      'app-right-sidebar--drawer-open': overlay && mobileOpen,
    }"
    :inert="!isOpen"
  >
    <div class="app-right-sidebar__header">
      <span class="app-right-sidebar__title">{{ panelTitle }}</span>
      <button
        type="button"
        class="app-right-sidebar__close"
        :aria-label="closeLabel"
        @click="handleClose"
      >
        <X :size="18" />
      </button>
    </div>

    <div class="app-right-sidebar__body">
      <div id="app-right-sidebar-target" />
    </div>
  </aside>
</template>
