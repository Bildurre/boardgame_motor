<script setup lang="ts">
import { onMounted, onBeforeUnmount, watch } from 'vue'
import { X } from '@lucide/vue'

const props = withDefaults(
  defineProps<{ modelValue: boolean; title?: string; size?: 'sm' | 'md' | 'lg' }>(),
  { size: 'md' },
)
const emit = defineEmits<{ 'update:modelValue': [boolean] }>()

function close() { emit('update:modelValue', false) }
function onKeydown(e: KeyboardEvent) { if (e.key === 'Escape' && props.modelValue) close() }

onMounted(() => window.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))
watch(() => props.modelValue, (open) => {
  if (typeof document !== 'undefined') document.body.style.overflow = open ? 'hidden' : ''
})
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="modal-overlay" @click.self="close">
        <div class="modal" :class="`modal--${props.size}`" role="dialog" aria-modal="true">
          <div v-if="title || $slots.header" class="modal__header">
            <slot name="header"><h3 class="modal__title">{{ title }}</h3></slot>
            <button class="modal__close" aria-label="Cerrar" @click="close"><X :size="18" /></button>
          </div>
          <div class="modal__body"><slot /></div>
          <div v-if="$slots.footer" class="modal__footer"><slot name="footer" /></div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
