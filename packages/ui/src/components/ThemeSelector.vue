<script setup lang="ts">
import { type Component } from 'vue'
import { Sun, Moon, Monitor } from '@lucide/vue'
import { useTheme, type ThemeMode } from '../composables/useTheme'

const { themeMode, setTheme } = useTheme()

const options: { value: ThemeMode; icon: Component }[] = [
  { value: 'light', icon: Sun },
  { value: 'dark', icon: Moon },
  { value: 'system', icon: Monitor },
]
</script>

<template>
  <div class="theme-selector">
    <button
      v-for="opt in options"
      :key="opt.value"
      type="button"
      :class="['theme-btn', { active: themeMode === opt.value }]"
      :title="opt.value"
      @click="setTheme(opt.value)"
    >
      <component :is="opt.icon" :size="14" />
    </button>
  </div>
</template>
