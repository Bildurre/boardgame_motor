// Mensaje de error de la API para mostrar al usuario: solo el `message`
// (traducido por el backend); nunca el volcado crudo del servidor.

interface ApiErrorShape {
  response?: { status?: number; data?: { message?: string } }
}

export function apiMessage(e: unknown, fallback: string): string {
  const message = (e as ApiErrorShape)?.response?.data?.message
  return typeof message === 'string' && message !== '' ? message : fallback
}
