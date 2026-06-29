import { reactive } from 'vue'

export interface ConfirmOptions {
  title?: string
  message: string
  confirmLabel?: string
  cancelLabel?: string
  variant?: 'primary' | 'danger' | 'success'
}

interface ConfirmState extends Required<Omit<ConfirmOptions, 'message'>> {
  open: boolean
  message: string
  resolver: ((value: boolean) => void) | null
}

const state = reactive<ConfirmState>({
  open: false,
  title: '',
  message: '',
  confirmLabel: 'Confirmar',
  cancelLabel: 'Cancelar',
  variant: 'primary',
  resolver: null,
})

export function useConfirm() {
  /** Muestra un diálogo de confirmación y resuelve a true/false. */
  function confirm(options: ConfirmOptions): Promise<boolean> {
    return new Promise((resolve) => {
      state.open = true
      state.title = options.title ?? ''
      state.message = options.message
      state.confirmLabel = options.confirmLabel ?? 'Confirmar'
      state.cancelLabel = options.cancelLabel ?? 'Cancelar'
      state.variant = options.variant ?? 'primary'
      state.resolver = resolve
    })
  }
  function resolve(value: boolean) {
    state.open = false
    state.resolver?.(value)
    state.resolver = null
  }
  return { state, confirm, resolve }
}
