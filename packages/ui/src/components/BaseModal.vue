<script setup lang="ts">
import { onMounted, onBeforeUnmount, watch, ref } from 'vue'
import { X } from '@lucide/vue'

// Pila de modales abiertos compartida entre instancias: el bloqueo del scroll
// del body dura mientras haya ALGÚN modal abierto, y Escape solo cierra el de
// arriba (no todos a la vez cuando hay modal + confirmación superpuestos).
const openStack: symbol[] = []

const props = withDefaults(
  defineProps<{
    modelValue: boolean
    title?: string
    size?: 'sm' | 'md' | 'lg' | 'wide'
    closeLabel?: string
  }>(),
  { size: 'md', closeLabel: 'Cerrar' },
)
const emit = defineEmits<{ 'update:modelValue': [boolean] }>()

const stackId = Symbol('modal')

function pushOpen() {
  openStack.push(stackId)
  document.body.style.overflow = 'hidden'
}

function popOpen() {
  const i = openStack.indexOf(stackId)
  if (i > -1) openStack.splice(i, 1)
  if (openStack.length === 0) document.body.style.overflow = ''
}

function close() {
  emit('update:modelValue', false)
}

// Cierre por overlay robusto: solo si el pointer bajó Y subió sobre el propio
// overlay. Evita el bug de que, al pulsar un botón interno que se desmonta
// (quitar imagen, borrar contenido…), el click se re-dirija al overlay y cierre.
const downOnOverlay = ref(false)
function onOverlayDown(e: MouseEvent) {
  downOnOverlay.value = e.target === e.currentTarget
}
function onOverlayClick(e: MouseEvent) {
  if (downOnOverlay.value && e.target === e.currentTarget) close()
  downOnOverlay.value = false
}
function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && props.modelValue && openStack[openStack.length - 1] === stackId) {
    close()
  }
}

onMounted(() => {
  window.addEventListener('keydown', onKeydown)
  if (props.modelValue) pushOpen()
})
onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown)
  // Si se desmonta con el modal abierto (navegación atrás…), libera el scroll.
  popOpen()
})
watch(
  () => props.modelValue,
  (open) => {
    if (typeof document === 'undefined') return
    if (open) pushOpen()
    else popOpen()
  },
)
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="modelValue"
        class="modal-overlay"
        @mousedown="onOverlayDown"
        @click="onOverlayClick"
      >
        <div class="modal" :class="`modal--${props.size}`" role="dialog" aria-modal="true">
          <div v-if="title || $slots.header" class="modal__header">
            <slot name="header"
              ><h3 class="modal__title">{{ title }}</h3></slot
            >
            <button class="modal__close" :aria-label="closeLabel" @click="close">
              <X :size="18" />
            </button>
          </div>
          <div class="modal__body"><slot /></div>
          <div v-if="$slots.footer" class="modal__footer"><slot name="footer" /></div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
