import type { AxiosInstance } from 'axios'
import type { FieldSchema } from './SchemaFields.vue'

// Subidas de imagen DIFERIDAS (CRM y configuración): los inputs de imagen
// retienen el File en el estado del formulario y NADA viaja al servidor hasta
// el submit. Al guardar: (1) se suben los File pendientes, (2) se persiste el
// formulario con las URLs resueltas y (3) SOLO si el guardado fue bien se
// borran del disco las imágenes que el registro ya no referencia. Si el
// guardado falla, se borran las subidas recién hechas (sin huérfanos de
// formularios cancelados o fallidos).

/** Sube una imagen de contenido (`POST /admin/content/uploads`) y devuelve su URL pública. */
export async function uploadContentImage(api: AxiosInstance, file: File): Promise<string> {
  const form = new FormData()
  form.append('image', file)
  const { data } = await api.post('/admin/content/uploads', form)
  return data.url as string
}

/** Borra una subida de contenido del disco; en silencio si falla. */
export async function deleteContentImage(api: AxiosInstance, url: string): Promise<void> {
  await api.delete('/admin/content/uploads', { data: { url } }).catch(() => {})
}

/**
 * Sube los File pendientes de los campos imagen de unos settings (recursivo
 * en group/repeater) y devuelve el valor resuelto, con URLs donde había
 * Files. Las URLs recién subidas se van APUNTANDO en `uploaded` (parámetro
 * de salida): si una subida o el guardado posterior fallan, el llamador
 * puede deshacerlas aunque el proceso quedara a medias.
 */
export async function uploadPendingImages(
  api: AxiosInstance,
  fields: FieldSchema[],
  value: Record<string, unknown>,
  uploaded: string[],
): Promise<Record<string, unknown>> {
  const out: Record<string, unknown> = { ...value }
  for (const field of fields) {
    const raw = out[field.key]
    if (field.type === 'image') {
      if (raw instanceof File) {
        const url = await uploadContentImage(api, raw)
        uploaded.push(url)
        out[field.key] = url
      } else if (field.translatable && raw && typeof raw === 'object') {
        const map: Record<string, string> = {}
        for (const [code, entry] of Object.entries(raw as Record<string, string | File>)) {
          if (entry instanceof File) {
            const url = await uploadContentImage(api, entry)
            uploaded.push(url)
            map[code] = url
          } else if (entry) {
            map[code] = entry
          }
        }
        out[field.key] = map
      }
    } else if (field.type === 'group' && raw && typeof raw === 'object' && !Array.isArray(raw)) {
      out[field.key] = await uploadPendingImages(
        api,
        field.fields ?? [],
        raw as Record<string, unknown>,
        uploaded,
      )
    } else if (field.type === 'repeater' && Array.isArray(raw)) {
      const rows: Record<string, unknown>[] = []
      for (const row of raw as Record<string, unknown>[]) {
        rows.push(await uploadPendingImages(api, field.fields ?? [], row ?? {}, uploaded))
      }
      out[field.key] = rows
    }
  }
  return out
}

/**
 * URLs de imagen presentes en unos settings según su esquema (recursivo).
 * Comparar las de ANTES de guardar con las de DESPUÉS da las que borrar del
 * disco (robusto ante reordenar/quitar filas de un repeater: una URL que siga
 * en cualquier posición no se toca).
 */
export function collectImageUrls(fields: FieldSchema[], value: Record<string, unknown>): string[] {
  const urls: string[] = []
  for (const field of fields) {
    const raw = value?.[field.key]
    if (field.type === 'image') {
      if (typeof raw === 'string' && raw) {
        urls.push(raw)
      } else if (raw && typeof raw === 'object' && !(raw instanceof File)) {
        for (const entry of Object.values(raw as Record<string, unknown>)) {
          if (typeof entry === 'string' && entry) urls.push(entry)
        }
      }
    } else if (field.type === 'group' && raw && typeof raw === 'object' && !Array.isArray(raw)) {
      urls.push(...collectImageUrls(field.fields ?? [], raw as Record<string, unknown>))
    } else if (field.type === 'repeater' && Array.isArray(raw)) {
      for (const row of raw as Record<string, unknown>[]) {
        urls.push(...collectImageUrls(field.fields ?? [], row ?? {}))
      }
    }
  }
  return urls
}
