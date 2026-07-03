<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { ChevronLeft, ChevronRight } from '@lucide/vue'
import { useRightSidebar } from '../composables/useRightSidebar'

// Panel lateral derecho contextual (portado de kontuan, DC-28): cada vista lo
// activa con useRightSidebar().useRegister(titulo) y teletransporta su
// contenido a #right-sidebar-target. Escritorio: columna plegable junto al
// contenido; móvil: drawer con asa flotante. Lo monta AdminLayout una vez.
// Agnóstico de i18n (DC-29): textos por prop, defaults en castellano.

const props = withDefaults(
  defineProps<{
    showLabel?: string
    hideLabel?: string
    /** Título por defecto si la vista registra sin título. */
    fallbackTitle?: string
  }>(),
  { showLabel: 'Mostrar panel', hideLabel: 'Ocultar panel', fallbackTitle: 'Detalle' },
)

const {
  hasContent,
  collapsed,
  mobileOpen,
  title,
  handleFlashId,
  toggleCollapsed,
  toggleMobile,
  closeMobile,
} = useRightSidebar()

// Umbral móvil (igual que el sidebar izquierdo: bp-md = 768).
const MOBILE_BREAKPOINT = 768

const isMobile = ref(false)
const flashing = ref(false)
let flashTimer: ReturnType<typeof setTimeout> | null = null

function checkMobile() {
  isMobile.value = window.innerWidth < MOBILE_BREAKPOINT
  if (!isMobile.value) mobileOpen.value = false
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
  if (flashTimer) clearTimeout(flashTimer)
})

const isVisible = computed(() => (isMobile.value ? mobileOpen.value : !collapsed.value))
const panelTitle = computed(() => title.value || props.fallbackTitle)

// Con el panel oculto, un destello en el asa avisa de contenido nuevo.
watch(handleFlashId, () => {
  if (isVisible.value) return
  flashing.value = false
  if (flashTimer) clearTimeout(flashTimer)
  requestAnimationFrame(() => {
    flashing.value = true
    flashTimer = setTimeout(() => {
      flashing.value = false
    }, 1100)
  })
})

function handleToggle(e: MouseEvent) {
  // Evita dejar el foco dentro de un subárbol inert/aria-hidden.
  ;(e.currentTarget as HTMLElement | null)?.blur()
  if (isMobile.value) toggleMobile()
  else toggleCollapsed()
}
</script>

<template>
  <div
    v-if="isMobile && hasContent && mobileOpen"
    class="right-sidebar-overlay"
    @click="closeMobile"
  />

  <button
    v-if="hasContent && !isVisible"
    type="button"
    class="right-sidebar-handle"
    :class="{ 'right-sidebar-handle--flash': flashing }"
    :aria-label="showLabel"
    @click="handleToggle"
  >
    <ChevronLeft :size="16" />
  </button>

  <aside
    v-show="hasContent"
    class="right-sidebar"
    :class="{
      'right-sidebar--collapsed': collapsed && !isMobile,
      'right-sidebar--mobile-open': mobileOpen,
    }"
    :inert="!isVisible"
  >
    <div class="right-sidebar__header">
      <button
        type="button"
        class="right-sidebar__toggle"
        :aria-label="hideLabel"
        @click="handleToggle"
      >
        <ChevronRight :size="16" />
      </button>
      <span class="right-sidebar__title">{{ panelTitle }}</span>
    </div>

    <div class="right-sidebar__body">
      <div id="right-sidebar-target" />
    </div>
  </aside>
</template>
