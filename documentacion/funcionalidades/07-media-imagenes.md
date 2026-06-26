# 07 · Media e imágenes

## Qué hace

Gestión de imágenes de las entidades y del CRM: subida, almacenamiento, URLs, y
soporte de **imágenes multilingües** (una imagen distinta por idioma) y atributos
asociados (color, coste) que el juego reutiliza.

## Qué hay hoy en choque

Traits de modelo: `HasImageAttribute`, `HasMultilingualImageAttribute`,
`HasColorAttribute`, `HasCostAttribute`. Almacenamiento en disco `public`. Las
imágenes se referencian por path y se sirven con `asset('storage/...')`.

Conservamos: simplicidad por traits, imágenes multilingües (las usa el CRM y las
previews), atributos de color/coste.

## Diseño nuevo

**Backend (`core/src/Media/`):**
- Decidir base: **Spatie MediaLibrary** (como kontuan) **o** traits propios
  modernizados. MediaLibrary da conversiones, colecciones y limpieza; los traits
  actuales son más ligeros. → evaluar (ver riesgos).
- Traits del motor:
  - `HasImageAttribute` — una imagen por campo.
  - `HasMultilingualImage` — imagen por locale (clave para CRM/previews).
  - `HasColorAttribute` / `HasCostAttribute` — utilidades reutilizables.
- Almacenamiento por disco configurable; helper de URL; borrado al eliminar el
  modelo; soporte de "dados"/iconos inline en texto rico (relación con doc 03).

**Frontend (`@bgm/ui`):**
- `ImageUpload` (drag&drop + preview) y variante multilingüe (una por locale).

## Frontera motor ↔ juego

| Motor | Juego |
|---|---|
| Traits de imagen/color/coste, almacenamiento, componentes de subida | Declara qué campos de imagen tiene cada entidad |

## Pasos

1. Decidir MediaLibrary vs traits propios.
2. Traits del motor + almacenamiento + borrado en cascada.
3. `ImageUpload` (simple y multilingüe) en `@bgm/ui`.

## Hito de aceptación

- La entidad demo sube imagen simple y multilingüe; se ve en admin y público; se
  borra al eliminar la entidad.

## Decisiones (cerradas)

- **MediaLibrary vs traits** → **DC-15**: **`spatie/laravel-media-library`** con un
  **PathGenerator propio** para rutas predecibles (las necesitan previews y PDF).
  Imágenes multilingües como colecciones por locale.
- **Iconos inline (dados)** → **DC-15**: como media con ruta estable, usables en el
  texto rico (TipTap, DC-09) y en el render a PNG (doc 01).

## Riesgos

- Que el PathGenerator mantenga rutas estables tras borrados/regeneraciones.
