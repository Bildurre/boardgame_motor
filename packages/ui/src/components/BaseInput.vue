<script setup lang="ts">
// Input de formulario (portado de kontuan). Estilo compartido `.form-field`.
const props = withDefaults(
  defineProps<{
    modelValue?: string | number | null
    label?: string
    placeholder?: string
    type?:
      | 'text'
      | 'email'
      | 'password'
      | 'number'
      | 'tel'
      | 'url'
      | 'search'
      | 'date'
      | 'datetime-local'
      | 'time'
    error?: string
    hint?: string
    disabled?: boolean
    required?: boolean
    id?: string
  }>(),
  { modelValue: '', type: 'text', disabled: false, required: false },
)

// El DOM siempre entrega string: no se declara `number` para no mentir al
// consumidor (para números está NumericInput).
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const inputId = props.id || `input-${Math.random().toString(36).slice(2, 9)}`
</script>

<template>
  <div class="form-field" :class="{ 'form-field--error': error }">
    <label v-if="label" :for="inputId" class="form-field__label">
      {{ label }}<span v-if="required" class="form-field__required">*</span>
    </label>
    <input
      :id="inputId"
      :type="type"
      :value="modelValue ?? ''"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      class="form-field__input"
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <p v-if="error" class="form-field__error">{{ error }}</p>
    <p v-else-if="hint" class="form-field__hint">{{ hint }}</p>
  </div>
</template>
