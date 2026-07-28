// Texto de preview de un bloque, DEPURADO y transversal (index de bloques,
// card de bloque y paneles derechos): del PRIMER campo con contenido en el
// orden título > subtítulo > contenido (el primer campo de texto restante
// del esquema), SOLO la primera frase, sin HTML. Helpers puros: quien pinta
// decide si además trunca por CSS (una línea con ellipsis en card/panel).

/** Campo mínimo del esquema que el preview necesita conocer. */
export interface BlockPreviewField {
  key: string
  type: string
  translatable: boolean
}

// Tipos de campo con texto legible (mismo criterio que SchemaFields).
const TEXT_TYPES = ['text', 'textarea', 'richtext']

/**
 * Primera frase de un texto: sin HTML, cortada en el primer signo de
 * puntuación de cierre (. ! ? … : ;) — que se conserva — o salto de línea.
 */
export function firstSentence(text: string): string {
  const plain = text.replace(/<[^>]*>/g, ' ')
  const stop = /[.!?…:;\n]/.exec(plain)
  const cut = stop ? plain.slice(0, stop.index + (stop[0] === '\n' ? 0 : 1)) : plain
  return cut.replace(/\s+/g, ' ').trim()
}

/**
 * Preview de un bloque: la primera frase (`firstSentence`) del primer campo
 * de texto con contenido, priorizando `title`, luego `subtitle` y después el
 * resto de campos de texto en el orden del esquema (el "contenido").
 * `displayText` resuelve los traducibles (locale actual con fallback).
 */
export function blockPreview(
  settings: Record<string, unknown> | null | undefined,
  fields: BlockPreviewField[],
  displayText: (map: Record<string, string> | null | undefined) => string,
): string {
  const textual = fields.filter((field) => TEXT_TYPES.includes(field.type))
  const ordered = [
    ...textual.filter((field) => field.key === 'title'),
    ...textual.filter((field) => field.key === 'subtitle'),
    ...textual.filter((field) => field.key !== 'title' && field.key !== 'subtitle'),
  ]
  for (const field of ordered) {
    const raw = settings?.[field.key]
    const text =
      field.translatable && raw && typeof raw === 'object'
        ? displayText(raw as Record<string, string>)
        : typeof raw === 'string'
          ? raw
          : ''
    const sentence = firstSentence(text)
    if (sentence) return sentence
  }
  return ''
}
