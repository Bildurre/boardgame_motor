import { ref, watch, onMounted, onUnmounted } from 'vue'

// Modo de tema. 'system' sigue la preferencia del SO. Copiado de kontuan.
export type ThemeMode = 'light' | 'dark' | 'system'

function storedTheme(): ThemeMode {
  // Guarda ante entornos sin window (prerender en build, DC-18).
  if (typeof window === 'undefined') return 'dark'
  return (localStorage.getItem('theme') as ThemeMode) || 'dark'
}

const themeMode = ref<ThemeMode>(storedTheme())

// Consentimiento de almacenamiento (banner de la web pública): si la app lo
// desactiva, el tema se aplica igual pero no se guarda en localStorage.
let persistTheme = true

export function setThemePersistence(enabled: boolean) {
  persistTheme = enabled
}

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
    if (persistTheme) localStorage.setItem('theme', mode)
    applyTheme(mode)
  }

  watch(themeMode, (mode) => applyTheme(mode))

  const media =
    typeof window !== 'undefined' ? window.matchMedia('(prefers-color-scheme: dark)') : null
  const onSystemChange = () => {
    if (themeMode.value === 'system') {
      applyTheme('system')
    }
  }

  onMounted(() => {
    applyTheme(themeMode.value)
    media?.addEventListener('change', onSystemChange)
  })

  onUnmounted(() => {
    media?.removeEventListener('change', onSystemChange)
  })

  return { themeMode, setTheme }
}
