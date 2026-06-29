<script setup lang="ts">
import { useToast } from '../composables/useToast'
import BaseToast from './BaseToast.vue'

const { toasts, remove } = useToast()
</script>

<template>
  <Teleport to="body">
    <div class="toast-container">
      <TransitionGroup name="toast">
        <BaseToast
          v-for="toast in toasts"
          :key="toast.id"
          :variant="toast.variant"
          :message="toast.message"
          :duration="toast.duration"
          @close="remove(toast.id)"
        />
      </TransitionGroup>
    </div>
  </Teleport>
</template>
