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

**Backend (`motor-php/src/Media/`):**
- Decidir base: **Spatie MediaLibrary** (como kontuan) **o** traits propios
  modernizados. MediaLibrary da conversiones, colecciones y limpieza; los traits
  actuales son más ligeros. → evaluar (ver riesgos).
- Traits del motor:
  - `HasImageAttribute` — una imagen por campo.
  - `HasMultilingualImage` — imagen por locale (clave para CRM/previews).
  - `HasColorAttribute` / `HasCostAttribute` — utilidades reutilizables.
- Almacenamiento por disco configurable; helper de URL; borrado al eliminar el
  modelo; soporte de "dados"/iconos inline en texto rico (relación con doc 03).

**Frontend (`motor-ui`):**
- `ImageUpload` (drag&drop + preview) y variante multilingüe (una por locale).

## Frontera motor ↔ juego

| Motor | Juego |
|---|---|
| Traits de imagen/color/coste, almacenamiento, componentes de subida | Declara qué campos de imagen tiene cada entidad |

## Pasos

1. Decidir MediaLibrary vs traits propios.
2. Traits del motor + almacenamiento + borrado en cascada.
3. `ImageUpload` (simple y multilingüe) en `motor-ui`.

## Hito de aceptación

- La entidad demo sube imagen simple y multilingüe; se ve en admin y público; se
  borra al eliminar la entidad.

## Riesgos / decisiones abiertas

- **MediaLibrary vs traits**: MediaLibrary integra mejor con kontuan-style y da
  conversiones/limpieza; pero las previews/PDF dependen de paths predecibles. Elegir
  pronto porque afecta a docs 01, 02 y 03.
- Iconos inline en richtext (los "dados" de choque) necesitan rutas estables para
  el render a PNG.
