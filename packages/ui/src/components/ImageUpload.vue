<script setup lang="ts">
import { computed, ref } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue: File | null
    currentUrl?: string | null
    label?: string
    emptyText?: string
    chooseText?: string
    clearText?: string
  }>(),
  { currentUrl: null, label: '', emptyText: 'Sin imagen', chooseText: 'Elegir imagen', clearText: 'Quitar' },
)

const emit = defineEmits<{ 'update:modelValue': [File | null] }>()

const localUrl = ref<string | null>(null)
const preview = computed(() => localUrl.value || props.currentUrl || null)

function onChange(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0] ?? null
  emit('update:modelValue', file)
  localUrl.value = file ? URL.createObjectURL(file) : null
}

function clear() {
  emit('update:modelValue', null)
  localUrl.value = null
}
</script>

<template>
  <div class="img-up">
    <label v-if="label" class="img-up__label">{{ label }}</label>
    <div class="img-up__body">
      <div class="img-up__preview">
        <img v-if="preview" :src="preview" alt="" />
        <span v-else class="img-up__ph">{{ emptyText }}</span>
      </div>
      <div class="img-up__actions">
        <label class="img-up__btn">
          {{ chooseText }}
          <input type="file" accept="image/*" hidden @change="onChange" />
        </label>
        <button v-if="modelValue || currentUrl" type="button" class="img-up__clear" @click="clear">{{ clearText }}</button>
      </div>
    </div>
  </div>
</template>
