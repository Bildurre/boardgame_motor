<script setup lang="ts">
import type { Component } from 'vue'

// Una tab CON icono lleva además title con su etiqueta: en estrecho la
// letra encoge y el texto se trunca con puntos suspensivos (ver
// _tabs.scss), y el title enseña el texto completo.
interface Tab {
  key: string
  label: string
  count?: number
  icon?: Component
}

defineProps<{ tabs: Tab[]; modelValue: string }>()
defineEmits<{ 'update:modelValue': [string] }>()
</script>

<template>
  <div class="tabs">
    <button
      v-for="tab in tabs"
      :key="tab.key"
      type="button"
      class="tabs__tab"
      :class="{ 'tabs__tab--active': modelValue === tab.key, 'tabs__tab--has-icon': !!tab.icon }"
      :title="tab.icon ? tab.label : undefined"
      @click="$emit('update:modelValue', tab.key)"
    >
      <component :is="tab.icon" v-if="tab.icon" class="tabs__icon" :size="16" />
      <span class="tabs__label">{{ tab.label }}</span>
      <span v-if="tab.count !== undefined" class="tabs__count">{{ tab.count }}</span>
    </button>
  </div>
</template>
