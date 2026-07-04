// SEO de la SPA pública (doc 10, DC-18): fija <title>, meta description,
// canonical y alternates hreflang de la ruta actual manipulando el <head>.
// Sin dependencias: cada vista lo llama al cargar sus datos (y el prerender
// captura el head ya resuelto).

export interface HeadInput {
  title?: string
  description?: string
  /** URL canónica absoluta de la ruta en el locale activo. */
  canonical?: string
  /** URL absoluta por locale (hreflang). */
  alternates?: Record<string, string>
}

function upsertMeta(name: string, content: string | undefined) {
  let tag = document.head.querySelector<HTMLMetaElement>(`meta[name="${name}"]`)
  if (!content) {
    tag?.remove()
    return
  }
  if (!tag) {
    tag = document.createElement('meta')
    tag.setAttribute('name', name)
    document.head.appendChild(tag)
  }
  tag.setAttribute('content', content)
}

function upsertLink(rel: string, href: string, hreflang?: string) {
  const selector = hreflang ? `link[rel="${rel}"][hreflang="${hreflang}"]` : `link[rel="${rel}"]`
  let tag = document.head.querySelector<HTMLLinkElement>(selector)
  if (!tag) {
    tag = document.createElement('link')
    tag.setAttribute('rel', rel)
    if (hreflang) tag.setAttribute('hreflang', hreflang)
    document.head.appendChild(tag)
  }
  tag.setAttribute('href', href)
}

export function useHead(input: HeadInput): void {
  if (input.title !== undefined) document.title = input.title
  upsertMeta('description', input.description)

  // Canonical y alternates se reponen enteros en cada ruta (los sobrantes
  // de la ruta anterior se retiran).
  document.head
    .querySelectorAll('link[rel="canonical"], link[rel="alternate"][hreflang]')
    .forEach((tag) => tag.remove())
  if (input.canonical) upsertLink('canonical', input.canonical)
  for (const [locale, href] of Object.entries(input.alternates ?? {})) {
    upsertLink('alternate', href, locale)
  }
}
