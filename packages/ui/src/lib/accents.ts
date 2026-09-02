import type { AxiosInstance } from 'axios'

// Acentos de la web (Configuración del sitio, doc 10): dos colores, marca y
// acción, que pisan el 500 de cada escala del tema (_theme.scss deriva el
// resto de tonos por color-mix). Lo usan el store del sitio de la app
// pública y el arranque del admin: la misma pareja en las dos SPA.

export interface SiteAccents {
  /** Acento 1, marca: logos, enlaces, foco, selección, navegación. */
  accent_color?: string | null
  /** Acento 2, acción: botón primario, CTA, kickers, cifras. */
  accent_2_color?: string | null
}

const HEX = /^#[0-9a-fA-F]{6}$/

/** Pisa el 500 de cada acento en <html>; un valor vacío deja el del tema. */
export function applyAccents(accents: SiteAccents): void {
  if (typeof document === 'undefined') return
  const root = document.documentElement.style
  const set = (name: string, value: string | null | undefined) => {
    if (value && HEX.test(value)) root.setProperty(name, value)
    else root.removeProperty(name)
  }
  set('--accent-500', accents.accent_color)
  set('--accent-2-500', accents.accent_2_color)
}

/**
 * Arranque del admin: lee la configuración pública del sitio y aplica sus
 * acentos, sin bloquear nada (si falla, se queda el tema del motor). La
 * app pública lo hace desde su store del sitio, que además la cachea.
 */
export function loadSiteAccents(api: AxiosInstance): Promise<void> {
  return api
    .get<{ data?: SiteAccents }>('/site', { edcBackground: true })
    .then(({ data }) => applyAccents(data.data ?? {}))
    .catch(() => {
      // sin configuración: acentos del tema
    })
}
