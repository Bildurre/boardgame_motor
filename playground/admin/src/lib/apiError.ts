// Extrae los errores de validación (422) por campo, ya traducidos por el
// backend. Para cualquier otro error se muestra un mensaje genérico (nunca el
// volcado del servidor: SQL, trazas, etc.).
export function fieldErrors(e: any): Record<string, string> {
  const errs = e?.response?.data?.errors
  if (e?.response?.status !== 422 || !errs) return {}
  const out: Record<string, string> = {}
  for (const [key, msgs] of Object.entries(errs)) {
    out[key] = Array.isArray(msgs) ? String(msgs[0]) : String(msgs)
  }
  return out
}

export function isValidationError(e: any): boolean {
  return e?.response?.status === 422
}
