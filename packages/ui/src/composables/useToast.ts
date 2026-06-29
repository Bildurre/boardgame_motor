import { reactive } from 'vue'

export interface Toast {
  id: number
  variant: 'success' | 'warning' | 'danger' | 'info'
  message: string
  duration: number
}

const toasts = reactive<Toast[]>([])
let nextId = 0

export function useToast() {
  function add(message: string, variant: Toast['variant'] = 'info', duration = 4000) {
    toasts.push({ id: nextId++, variant, message, duration })
  }
  function remove(id: number) {
    const i = toasts.findIndex((t) => t.id === id)
    if (i > -1) toasts.splice(i, 1)
  }
  const success = (m: string) => add(m, 'success')
  const warning = (m: string) => add(m, 'warning')
  const danger = (m: string) => add(m, 'danger')
  const info = (m: string) => add(m, 'info')
  return { toasts, add, remove, success, warning, danger, info }
}
