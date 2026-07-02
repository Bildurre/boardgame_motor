<script setup lang="ts">
// Textarea de formulario (portado de kontuan).
const props = withDefaults(
  defineProps<{
    modelValue?: string
    label?: string
    placeholder?: string
    rows?: number
    error?: string
    hint?: string
    disabled?: boolean
    required?: boolean
    id?: string
  }>(),
  { modelValue: '', rows: 4, disabled: false, required: false },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
  keydown: [event: KeyboardEvent]
}>()

const textareaId = props.id || `textarea-${Math.random().toString(36).slice(2, 9)}`
</script>

<template>
  <div class="form-field" :class="{ 'form-field--error': error }">
    <label v-if="label" :for="textareaId" class="form-field__label">
      {{ label }}<span v-if="required" class="form-field__required">*</span>
    </label>
    <textarea
      :id="textareaId"
      :value="modelValue"
      :placeholder="placeholder"
      :rows="rows"
      :disabled="disabled"
      :required="required"
      class="form-field__textarea"
      @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
      @keydown="emit('keydown', $event)"
    />
    <p v-if="error" class="form-field__error">{{ error }}</p>
    <p v-else-if="hint" class="form-field__hint">{{ hint }}</p>
  </div>
</template>
