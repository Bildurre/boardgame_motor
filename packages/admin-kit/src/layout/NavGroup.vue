<script setup lang="ts">
// Grupo plegable del menú lateral: cabecera-botón (icono + etiqueta + chevron)
// que despliega/pliega sus hijos (los nav-item van en el slot por defecto,
// igual que sueltos en el #nav del AdminLayout). El estado se persiste en
// localStorage por clave de grupo (por defecto plegado) y, con la ruta actual
// en un hijo (prop `active`), el grupo se resalta y se auto-despliega.
import { computed, inject, ref, useId, watch } from 'vue'
import { ChevronRight } from '@lucide/vue'
import { SIDEBAR_RAIL } from './keys'

const props = defineProps<{
  /** Etiqueta del grupo (ya traducida por la app). */
  label: string
  /** Clave del plegado en localStorage (`edc_admin_nav_<clave>`). */
  storageKey: string
  /**
   * La ruta actual es de un hijo: resalta la cabecera y auto-despliega. La
   * app lo calcula igual que el `active` de sus nav-item (p. ej. meta.nav).
   */
  active?: boolean
}>()

defineSlots<{ icon?: () => unknown; default?: () => unknown }>()

const STORAGE_PREFIX = 'edc_admin_nav_'

// Por defecto plegado; si el usuario lo dejó abierto, se respeta.
const open = ref(localStorage.getItem(STORAGE_PREFIX + props.storageKey) === '1')

// Auto-despliegue al entrar en una ruta hija (sin persistir: solo los toggles
// del usuario guardan preferencia).
watch(
  () => props.active,
  (active) => {
    if (active) open.value = true
  },
  { immediate: true },
)

// En el carril de iconos (sidebar colapsado en escritorio) los hijos se
// muestran siempre y el toggle no hace nada: plegar sin etiquetas ocultaría
// rutas. AdminLayout provee el estado; fuera de él, nunca es carril.
const rail = inject(
  SIDEBAR_RAIL,
  computed(() => false),
)
const expanded = computed(() => rail.value || open.value)

const panelId = useId()

function toggle() {
  if (rail.value) return
  open.value = !open.value
  localStorage.setItem(STORAGE_PREFIX + props.storageKey, open.value ? '1' : '0')
}
</script>

<template>
  <div class="nav-group" :class="{ 'nav-group--open': expanded, 'nav-group--active': active }">
    <button
      type="button"
      class="nav-item nav-group__toggle"
      :aria-expanded="expanded"
      :aria-controls="panelId"
      @click="toggle"
    >
      <slot name="icon" />
      <span class="nav-label">{{ label }}</span>
      <ChevronRight class="nav-group__chevron" :size="16" aria-hidden="true" />
    </button>
    <div :id="panelId" class="nav-group__panel">
      <div class="nav-group__children"><slot /></div>
    </div>
  </div>
</template>
