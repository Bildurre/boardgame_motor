// Navegación SPA para los <a> internos que NO son RouterLink: los CTA de
// bloque (BlockCta) y cualquier enlace del contenido enriquecido (v-html)
// llegan del CRM como HTML plano, y sin esto el navegador recarga la SPA
// entera (arranque completo, splash incluido). Este delegador global de
// clics los convierte en router.push cuando apuntan al mismo origen.
//
// Uso (main.ts, tras crear el router):
//
//   interceptInternalLinks(router)
//
// Se respetan los gestos y atributos de siempre: clic no-primario o con
// modificadora, target distinto de _self, download, anclas (#…), otros
// esquemas (mailto:, tel:…) y cualquier clic que otro handler ya haya
// prevenido (RouterLink navega solo y llega aquí con defaultPrevented).
// Los prefijos excluidos siguen recargando: API, ficheros servidos por el
// backend y el admin (otra SPA del mismo dominio).

export interface InternalLinksRouterLike {
  push(to: string): unknown
}

export interface InterceptInternalLinksOptions {
  /** Prefijos de ruta que SÍ deben recargar (por defecto /api, /storage y
   *  /admin: backend y admin comparten dominio con la web pública). */
  excludePrefixes?: string[]
}

/** Activa el delegador; devuelve la función que lo retira. */
export function interceptInternalLinks(
  router: InternalLinksRouterLike,
  options: InterceptInternalLinksOptions = {},
): () => void {
  const exclude = options.excludePrefixes ?? ['/api', '/storage', '/admin']

  const onClick = (event: MouseEvent) => {
    if (event.defaultPrevented) return
    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return
    }
    const anchor = (event.target as Element | null)?.closest?.('a')
    if (!anchor) return
    const href = anchor.getAttribute('href')
    if (!href || href.startsWith('#')) return
    if (anchor.target && anchor.target !== '_self') return
    if (anchor.hasAttribute('download')) return
    let url: URL
    try {
      url = new URL(anchor.href, window.location.href)
    } catch {
      return
    }
    if (!/^https?:$/.test(url.protocol)) return
    if (url.origin !== window.location.origin) return
    if (
      exclude.some((prefix) => url.pathname === prefix || url.pathname.startsWith(`${prefix}/`))
    ) {
      return
    }
    event.preventDefault()
    void router.push(url.pathname + url.search + url.hash)
  }

  document.addEventListener('click', onClick)
  return () => document.removeEventListener('click', onClick)
}
