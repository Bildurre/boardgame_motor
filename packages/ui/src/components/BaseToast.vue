<script setup lang="ts">
import { onMounted } from 'vue'
import { X } from '@lucide/vue'

const props = withDefaults(
  defineProps<{ variant?: 'success' | 'warning' | 'danger' | 'info'; message: string; duration?: number }>(),
  { variant: 'info', duration: 4000 },
)
const emit = defineEmits<{ close: [] }>()

onMounted(() => {
  if (props.duration > 0) setTimeout(() => emit('close'), props.duration)
})
</script>

<template>
  <div :class="['toast', `toast--${variant}`]" role="alert">
    <span class="toast__message">{{ message }}</span>
    <button class="toast__close" aria-label="Cerrar" @click="emit('close')"><X :size="16" /></button>
  </div>
</template>
