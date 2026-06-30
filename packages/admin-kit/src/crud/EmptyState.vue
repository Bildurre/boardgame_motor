<script setup lang="ts">
// Estado vacío de un listado (portado de kontuan).
defineProps<{ title: string; description?: string }>()
</script>

<template>
  <div class="empty-state">
    <div v-if="$slots.icon" class="empty-state__icon"><slot name="icon" /></div>
    <h3 class="empty-state__title">{{ title }}</h3>
    <p v-if="description" class="empty-state__description">{{ description }}</p>
    <div v-if="$slots.action" class="empty-state__action"><slot name="action" /></div>
  </div>
</template>
