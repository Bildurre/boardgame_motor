// Visibilidad CONDICIONAL de un campo del DSL (Field::visibleWhen del core):
// el campo solo se pinta cuando otro campo del mismo esquema vale alguno de
// los valores declarados (p. ej. «Origen» solo con entidad = mazos). Puro:
// lo usan SchemaFields (formulario) y PageBlocks (volcado del panel).

export interface VisibleWhen {
  field: string
  values: string[]
}

interface VisibilityField {
  key: string
  default?: unknown
  visible_when?: VisibleWhen | null
}

/**
 * ¿Se muestra `field` con los valores actuales? Si el campo condicionante
 * aún no tiene valor, cuenta su default del esquema (el formulario nuevo
 * arranca con los defaults, pero un bloque guardado antes de que existiera
 * el campo no lo trae).
 */
export function isFieldVisible(
  field: VisibilityField,
  values: Record<string, unknown>,
  allFields: VisibilityField[],
): boolean {
  const condition = field.visible_when
  if (!condition) return true
  const raw = values[condition.field] ?? allFields.find((f) => f.key === condition.field)?.default
  return raw !== null && raw !== undefined && condition.values.includes(String(raw))
}
