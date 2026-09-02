import type { AxiosInstance } from 'axios'

// Color del juego (Configuración del sitio, doc 10): el ACENTO 3 del tema.
// Los acentos 1 (marca) y 2 (acción) son fijos de la IP en _theme.scss; el
// 3 lo elige cada juego y pisa solo el --accent-3-500 (el tema deriva el
// resto de tonos por color-mix). Lo usan el store del sitio de la app
// pública y el arranque del admin: el mismo color en las dos SPA.

export interface SiteAccents {
  /** Acento 3, color del juego: kickers, cabeceras, separadores, badges. */
  game_color?: string | null
}

const HEX = /^#[0-9a-fA-F]{6}$/

/** Pisa el 500 del acento de juego en <html>; vacío deja el del tema. */
export function applyAccents(accents: SiteAccents): void {
  if (typeof document === 'undefined') return
  const root = document.documentElement.style
  const value = accents.game_color
  if (value && HEX.test(value)) root.setProperty('--accent-3-500', value)
  else root.removeProperty('--accent-3-500')
}

/**
 * Arranque del admin: lee la configuración pública del sitio y aplica su
 * color de juego, sin bloquear nada (si falla, se queda el del tema). La
 * app pública lo hace desde su store del sitio, que además la cachea.
 */
export function loadSiteAccents(api: AxiosInstance): Promise<void> {
  return api
    .get<{ data?: SiteAccents }>('/site', { edcBackground: true })
    .then(({ data }) => applyAccents(data.data ?? {}))
    .catch(() => {
      // sin configuración: color del tema
    })
}
