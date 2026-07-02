<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue'
import { X } from '@lucide/vue'

const props = withDefaults(
  defineProps<{
    variant?: 'success' | 'warning' | 'danger' | 'info'
    message: string
    duration?: number
    closeLabel?: string
  }>(),
  { variant: 'info', duration: 4000, closeLabel: 'Cerrar' },
)
const emit = defineEmits<{ close: [] }>()

let timer: ReturnType<typeof setTimeout> | undefined

onMounted(() => {
  if (props.duration > 0) timer = setTimeout(() => emit('close'), props.duration)
})
onBeforeUnmount(() => clearTimeout(timer))
</script>

<template>
  <div :class="['toast', `toast--${variant}`]" role="alert">
    <span class="toast__message">{{ message }}</span>
    <button class="toast__close" :aria-label="closeLabel" @click="emit('close')">
      <X :size="16" />
    </button>
  </div>
</template>
