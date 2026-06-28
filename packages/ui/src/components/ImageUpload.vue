<script setup lang="ts">
import { computed, ref } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue: File | null
    currentUrl?: string | null
    label?: string
  }>(),
  { currentUrl: null, label: '' },
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
        <span v-else class="img-up__ph">Sin imagen</span>
      </div>
      <div class="img-up__actions">
        <label class="img-up__btn">
          Elegir imagen
          <input type="file" accept="image/*" hidden @change="onChange" />
        </label>
        <button v-if="modelValue || currentUrl" type="button" class="img-up__clear" @click="clear">Quitar</button>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.img-up {
  display: flex;
  flex-direction: column;
  gap: $space-2;

  &__label { font-size: 0.85rem; color: $color-text-muted; }
  &__body { display: flex; gap: $space-4; align-items: center; }

  &__preview {
    width: 72px;
    height: 72px;
    border: 1px solid $color-border;
    border-radius: $radius-md;
    background: $color-bg;
    display: grid;
    place-items: center;
    overflow: hidden;
    img { width: 100%; height: 100%; object-fit: cover; }
  }
  &__ph { font-size: 0.7rem; color: $color-text-muted; }

  &__actions { display: flex; flex-direction: column; gap: $space-2; align-items: flex-start; }
  &__btn {
    font-size: 0.85rem;
    padding: 0.4rem 0.75rem;
    border: 1px solid $color-border;
    border-radius: $radius-md;
    color: $color-text;
    cursor: pointer;
    &:hover { border-color: $color-accent; }
  }
  &__clear {
    font: inherit; font-size: 0.8rem; background: none; border: none;
    color: $color-text-muted; cursor: pointer; padding: 0;
    &:hover { color: #ff6b6b; }
  }
}
</style>
