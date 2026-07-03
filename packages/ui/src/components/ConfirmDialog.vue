<script setup lang="ts">
import BaseModal from './BaseModal.vue'
import { Check, X } from '@lucide/vue'
import BaseButton from './BaseButton.vue'
import { useConfirm } from '../composables/useConfirm'

// Diálogo global de confirmación. Se monta una vez (junto a ToastContainer) y
// responde a useConfirm().confirm({...}). Sustituye al confirm() nativo.
const { state, resolve } = useConfirm()
</script>

<template>
  <BaseModal
    :model-value="state.open"
    :title="state.title || 'Confirmar'"
    size="sm"
    @update:model-value="
      (v: boolean) => {
        if (!v) resolve(false)
      }
    "
  >
    <p class="confirm__message">{{ state.message }}</p>
    <template #footer>
      <BaseButton variant="secondary" @click="resolve(false)">
        <template #icon><X :size="16" /></template>
        {{ state.cancelLabel }}
      </BaseButton>
      <BaseButton :variant="state.variant" @click="resolve(true)">
        <template #icon><Check :size="16" /></template>
        {{ state.confirmLabel }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
