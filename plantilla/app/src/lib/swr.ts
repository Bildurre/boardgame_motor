import { createSwrGet } from '@edc-motor/ui'
import { api } from '@/lib/api'

// Caché SWR en memoria del contenido público (ver createSwrGet en el motor):
// la comparten las vistas de página — home, páginas del CRM y singles — para
// que revisitar sea instantáneo (sin velo) con revalidación de fondo.
export const swrGet = createSwrGet(api)
