<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted } from 'vue'
import { Funnel, X } from '@lucide/vue'
import { useAppRightSidebar } from '../composables/useAppRightSidebar'

// Barra lateral derecha contextual de la web pública (misma mecánica que la
// RightSidebar del admin-kit): cada vista la activa con
// useAppRightSidebar().useRegister(titulo) y teletransporta su contenido
// (típicamente sus selects de filtros) a #app-right-sidebar-target.
//
// A diferencia del admin (marco fijo, scrollea el main), aquí el footer va
// al final del documento: la barra es SIEMPRE fija (por debajo de la
// cabecera fija del cascarón → bottom 0) para no scrollear con la página ni
// "acabarse" al llegar al pie; el cascarón fija --app-right-sidebar-top a la
// altura real de su cabecera por breakpoint. En ANCHO, desplegada, el
// cascarón le hace hueco al contenido (padding-right de .site-content /
// .app-footer sobre la clase `--docked`; la cabecera no lo necesita: la
// barra no la tapa); por debajo de OVERLAY_BREAKPOINT es un drawer
// superpuesto con telón (también bajo la cabecera, que sigue usable), click
// fuera y Escape. Se abre y se cierra con el ASA anclada a la propia barra
// (Funnel cerrada / X abierta), visible solo si la vista registró
// contenido; el cascarón puede ajustar su altura con
// --app-right-sidebar-handle-top (relativa al techo de la barra).
// La monta App.vue una vez, dentro de .site-main.
// Agnóstica de i18n (DC-29): textos por prop, defaults en castellano.

const props = withDefaults(
  defineProps<{
    /** Label del asa con la barra cerrada. */
    openLabel?: string
    /** Label del asa con la barra abierta. */
    closeLabel?: string
    /** Título por defecto si la vista registra sin título. */
    fallbackTitle?: string
  }>(),
  { openLabel: 'Abrir el panel', closeLabel: 'Cerrar el panel', fallbackTitle: 'Filtros' },
)

const { hasContent, collapsed, mobileOpen, overlay, title, isOpen, toggle, closeMobile } =
  useAppRightSidebar()

// Por debajo de este ancho la barra se superpone (drawer) en vez de hacer
// hueco: coincide con el corte móvil del header público (900px).
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
const handleLabel = computed(() => (isOpen.value ? props.closeLabel : props.openLabel))

// Desplegada en ANCHO: el cascarón le hace hueco (body:has en su scss).
// hasContent va en la clase porque v-show (display: none) no saca al aside
// de los selectores :has.
const docked = computed(() => hasContent.value && !overlay.value && !collapsed.value)
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
      'app-right-sidebar--open': hasContent && isOpen,
      'app-right-sidebar--docked': docked,
      'app-right-sidebar--drawer': overlay,
    }"
  >
    <!-- Asa anclada a la barra (viaja con ella al abrir/cerrar): fuera del
         panel inerte para poder abrirla con la barra cerrada. -->
    <button
      type="button"
      class="app-right-sidebar__handle"
      :aria-label="handleLabel"
      :title="handleLabel"
      :aria-expanded="isOpen"
      @click="toggle"
    >
      <X v-if="isOpen" :size="18" />
      <Funnel v-else :size="18" />
    </button>

    <div class="app-right-sidebar__panel" :inert="!isOpen">
      <div class="app-right-sidebar__header">
        <span class="app-right-sidebar__title">{{ panelTitle }}</span>
      </div>

      <div class="app-right-sidebar__body">
        <div id="app-right-sidebar-target" />
      </div>
    </div>
  </aside>
</template>
