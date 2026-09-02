import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { applyAccents, readCache, writeCache } from '@edc-motor/ui'
import { api } from '@/lib/api'
import { useLocalesStore } from '@/stores/locales'

// Configuración de la web (doc 10, GET /api/site): título, logo, favicon,
// fuentes y los dos acentos (marca y acción), que pisan el 500 de cada
// escala del tema del ui (applyAccents); el resto de tonos los deriva el
// propio tema por color-mix.
export interface SiteFont {
  label: string
  stack: string
  /** Ficheros @font-face (vacío en las pilas del sistema). */
  files: { family: string; src: string; weight: string; style: string }[]
}

export interface SiteSettings {
  title: Record<string, string>
  description: Record<string, string>
  logo: Record<string, string>
  favicon: string | null
  /** Acento 1, marca. */
  accent_color: string
  /** Acento 2, acción. */
  accent_2_color: string
  font_headings: string
  font_body: string
  font_special: string
  footer_text: Record<string, string>
  fonts: Record<string, SiteFont>
  /** SVG de cada logo inlineado por la API (currentColor hereda el acento). */
  logo_inline: Record<string, string>
}

/** CSS @font-face de un catálogo de fuentes (los navegadores solo descargan las usadas). */
export function fontFacesCss(fonts: Record<string, SiteFont>): string {
  return Object.values(fonts)
    .flatMap((font) => font.files)
    .map(
      (file) =>
        `@font-face { font-family: '${file.family}'; src: url('${file.src}'); ` +
        `font-weight: ${file.weight}; font-style: ${file.style}; font-display: swap; }`,
    )
    .join('\n')
}

const CACHE_KEY = 'edc_app_cache_site'

export const useSiteStore = defineStore('site', () => {
  const locales = useLocalesStore()
  // Última configuración buena (stale-while-revalidate): en visitas
  // repetidas el header pinta el logo y el título reales al instante.
  const settings = ref<SiteSettings | null>(readCache<SiteSettings>(CACHE_KEY))

  const title = computed(() => {
    const map = settings.value?.title ?? {}
    return map[locales.current] || map[locales.defaultLocale] || Object.values(map)[0] || ''
  })

  const footerText = computed(() => {
    const map = settings.value?.footer_text ?? {}
    return map[locales.current] || map[locales.defaultLocale] || Object.values(map)[0] || ''
  })

  const description = computed(() => {
    const map = settings.value?.description ?? {}
    return map[locales.current] || map[locales.defaultLocale] || Object.values(map)[0] || ''
  })

  // Logo del idioma actual (con fallback al por defecto, como el título).
  const logoUrl = computed(() => {
    const map = settings.value?.logo ?? {}
    return map[locales.current] || map[locales.defaultLocale] || Object.values(map)[0] || null
  })

  const logoInline = computed(() => {
    const map = settings.value?.logo_inline ?? {}
    const url = logoUrl.value
    // El inline correspondiente a la URL resuelta (no mezclar idiomas).
    const key = Object.entries(settings.value?.logo ?? {}).find(([, u]) => u === url)?.[0]
    return (key && map[key]) || null
  })

  /** Título del documento: "página · sitio" (o solo una de las partes). */
  function documentTitle(pageTitle?: string): string {
    return [pageTitle, title.value].filter(Boolean).join(' · ')
  }

  function applyFonts() {
    if (!settings.value) return
    const fonts = settings.value.fonts ?? {}

    // @font-face de todo el catálogo (solo se descargan las que se usan).
    let style = document.getElementById('site-fonts')
    if (!style) {
      style = document.createElement('style')
      style.id = 'site-fonts'
      document.head.appendChild(style)
    }
    style.textContent = fontFacesCss(fonts)

    const root = document.documentElement.style
    root.setProperty('--font-headings', fonts[settings.value.font_headings]?.stack || 'inherit')
    root.setProperty('--font-body', fonts[settings.value.font_body]?.stack || '')
    root.setProperty('--font-special', fonts[settings.value.font_special]?.stack || 'inherit')
  }

  function applyFavicon() {
    if (!settings.value?.favicon) return
    let link = document.querySelector<HTMLLinkElement>('link[rel="icon"]')
    if (!link) {
      link = document.createElement('link')
      link.rel = 'icon'
      document.head.appendChild(link)
    }
    link.href = settings.value.favicon
  }

  let inflight: Promise<void> | null = null
  let fresh = false
  let appliedFromCache = false

  /** Aplica la configuración actual (fuentes, favicon, acento, título). */
  function applySettings() {
    applyFonts()
    applyFavicon()
    if (settings.value) applyAccents(settings.value)
    if (!document.title) document.title = documentTitle()
  }

  /** Carga la configuración y la aplica (fuentes, favicon, acento). Con la
   *  caché de la visita anterior se aplica YA (sin esperar a la red) y la
   *  respuesta fresca solo re-aplica si algo cambió. */
  function load(): Promise<void> {
    if (fresh) return Promise.resolve()
    const hadCache = settings.value !== null
    if (hadCache && !appliedFromCache) {
      appliedFromCache = true
      applySettings()
    }
    // Con caché aplicada, el refresco es DE FONDO (no cuenta para el velo).
    inflight ??= api
      .get('/site', hadCache ? { edcBackground: true } : undefined)
      .then(({ data }) => {
        fresh = true
        const changed = JSON.stringify(data.data) !== JSON.stringify(settings.value)
        settings.value = data.data
        writeCache(CACHE_KEY, data.data)
        if (changed) applySettings()
      })
      .catch(() => {
        // sin configuración: la web funciona con los defaults del tema
      })
      .finally(() => {
        inflight = null
      })
    return hadCache ? Promise.resolve() : inflight
  }

  return {
    settings,
    title,
    footerText,
    description,
    logoUrl,
    logoInline,
    documentTitle,
    load,
  }
})
