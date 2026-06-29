<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { ChevronDown } from '@lucide/vue'

interface Locale { code: string; name: string }

// Selector de idioma de contenido. Controlado por v-model (código de locale).
// Adaptado de kontuan pero sin vue-i18n: la lista de locales se pasa por props
// (la sirve la API del motor).
const props = defineProps<{
  modelValue: string
  locales: Locale[]
}>()

const emit = defineEmits<{ 'update:modelValue': [code: string] }>()

const open = ref(false)
const dropdownRef = ref<HTMLElement>()

function selectLocale(code: string) {
  emit('update:modelValue', code)
  open.value = false
}

function handleClickOutside(e: MouseEvent) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside))
</script>

<template>
  <div ref="dropdownRef" class="locale-selector">
    <button type="button" class="locale-trigger" @click="open = !open">
      {{ props.modelValue.toUpperCase() }}
      <ChevronDown class="locale-chevron" :size="14" />
    </button>
    <Transition name="dropdown">
      <div v-if="open" class="locale-dropdown">
        <button
          v-for="loc in locales"
          :key="loc.code"
          type="button"
          :class="['locale-option', { active: props.modelValue === loc.code }]"
          @click="selectLocale(loc.code)"
        >
          <span class="locale-code">{{ loc.code.toUpperCase() }}</span>
          <span class="locale-label">{{ loc.name }}</span>
        </button>
      </div>
    </Transition>
  </div>
</template>
