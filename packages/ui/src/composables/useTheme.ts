import { ref, watch, onMounted } from 'vue'

// Modo de tema. 'system' sigue la preferencia del SO. Copiado de kontuan.
export type ThemeMode = 'light' | 'dark' | 'system'

const themeMode = ref<ThemeMode>((localStorage.getItem('theme') as ThemeMode) || 'dark')

function getSystemTheme(): 'light' | 'dark' {
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

function applyTheme(mode: ThemeMode) {
  const resolved = mode === 'system' ? getSystemTheme() : mode
  document.documentElement.setAttribute('data-theme', resolved)
}

export function useTheme() {
  function setTheme(mode: ThemeMode) {
    themeMode.value = mode
    localStorage.setItem('theme', mode)
    applyTheme(mode)
  }

  watch(themeMode, (mode) => applyTheme(mode))

  onMounted(() => {
    applyTheme(themeMode.value)

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
      if (themeMode.value === 'system') {
        applyTheme('system')
      }
    })
  })

  return { themeMode, setTheme }
}
