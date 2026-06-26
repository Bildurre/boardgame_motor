# 01 · Render de componentes a PNG

## Qué hace

Captura el **componente visual** de una entidad (la carta, el héroe…) a una
imagen PNG, por cada idioma. Esas imágenes sirven para dos cosas: mostrarse en la
web y ser la materia prima del PDF (doc 02). Es la base de todo el pipeline de
impresión.

## Qué hay hoy en choque (y qué duele)

`App\Jobs\GeneratePreviewImage` + `Traits\ConfiguresBrowsershot` +
`Models\Traits\HasPreviewImage`. Funciona, pero es frágil por una razón de fondo:

- **Renderiza HTML inyectado** (`Browsershot::html($html)`) en vez de una URL real.
  Como el HTML va "suelto", el job tiene que:
  - **Buscar el CSS compilado a mano** con `glob('build/assets/app-*.css')` y
    fallar con "No CSS file found" si cambia el nombre.
  - **Convertir todas las imágenes y fuentes a base64** e inyectarlas.
  - **Hardcodear variables CSS** (`--faction-color`, tema, etc.) en un `<style>`
    gigante dentro del PHP.
  - Mapear rutas de assets a mano con varios `if (strpos(...))`.
- El tipo de modelo está **hardcodeado** (`if Hero … elseif Card …`) con sus
  `load()` de relaciones dentro del job → no extensible por juego.
- Tamaño/escala/delay mágicos mezclados con la lógica.

Todo ese andamiaje existe **solo porque no se renderiza desde una URL servida por
el frontend**. Es justo lo que el mundo Vue elimina.

## Diseño nuevo

**Principio:** Browsershot captura **una URL real** de la SPA `app`, no HTML
inyectado. El componente Vue de la entidad ya vive en `app` (se usa también en la
web pública), con sus estilos y fuentes cargados por Vite. Una imagen = una foto
de esa URL. Adiós a globbing de CSS, base64 y variables hardcodeadas.

### Ruta de render aislada (en la `app` del juego)

```
/_render/:entity/:id?locale=es        →  monta SOLO el componente visual,
                                          sin layout, fondo transparente/blanco,
                                          tamaño físico exacto (88×126 mm para carta)
```

- Es una ruta "desnuda" (sin navbar ni footer), pensada para captura.
- Lee los datos vía API (o se le inyectan), aplica el locale del query param.
- El componente es **el mismo** que se usa en el expositor → fuente única (D8).
- Acceso restringido (token interno / sólo desde el backend) para que no sea pública.

### Backend del motor (`motor-php/src/Previews/`)

```
Previews/
├── PreviewableContract.php      # interfaz que implementa la entidad del juego
├── Concerns/HasPreviewImage.php # trait: URLs, almacenamiento, invalidación
├── PreviewService.php           # orquesta render+guardado+limpieza
├── PreviewRenderer.php          # wrapper de Browsershot (config centralizada)
├── Jobs/GeneratePreviewJob.php  # async, por entidad+locale
└── Console/PreviewManageCommand.php
```

- **`PreviewableContract`**: el juego declara, por entidad:
  - `previewRouteName()` / cómo construir la URL de render (`/_render/card/{id}`),
  - `previewSize()` (ancho×alto físico),
  - `previewDirectory()`,
  - qué cambios deben invalidar la preview.
- **`HasPreviewImage`** (trait): guarda las rutas por locale (campo traducible
  `preview_image`), expone `previewUrl($locale)`, `hasPreview()`,
  `regeneratePreviews()`; invalida en `updated`/`deleting`. Se conserva la idea
  del trait actual, **sin** la lógica de render dentro.
- **`PreviewRenderer`**: única fuente de config de Browsershot (chrome path,
  `noSandbox`, args de producción, scale factor, delay/`waitUntilNetworkIdle`).
  Hoy disperso entre el job y `ConfiguresBrowsershot`.
- **`GeneratePreviewJob`**: recibe `(entidad, locale)`, pide a `PreviewRenderer`
  la captura de la URL, guarda, borra la antigua, actualiza el modelo.
- **`PreviewManageCommand`**: rediseño del `preview:manage` actual conservando sus
  acciones útiles (status/generate-all/regenerate/generate/delete/clean huérfanos,
  filtros `--type --id --force --sync --dry-run`) pero **genérico**: itera sobre
  las entidades registradas como `Previewable`, sin nombres de modelo a fuego.

### Invalidación automática

`HasPreviewImage` engancha `updated` y regenera salvo cambios irrelevantes
(timestamps, sólo `is_published`). Se conserva la lógica de `shouldRegeneratePreview`
actual, pero **declarativa**: la entidad lista qué campos disparan regeneración.

## Frontera motor ↔ juego

| Motor | Juego |
|---|---|
| Trait + contrato + servicio + job + comando + wrapper Browsershot | El **componente Vue** visual de la entidad |
| La ruta `/_render` la provee el andamiaje de `app` | Registra sus entidades como `Previewable` (tamaño, ruta, dir, triggers) |
| Almacenamiento, limpieza de huérfanos, multi-locale | — |

## Pasos

1. Infra Browsershot en `motor-php` (`PreviewRenderer` + config publicable).
2. Ruta `/_render` en el andamiaje de `app` (carga aislada de un componente).
3. `PreviewableContract` + `HasPreviewImage` + `PreviewService`.
4. `GeneratePreviewJob` (captura desde URL) + almacenamiento/limpieza.
5. `PreviewManageCommand` genérico.
6. Invalidación automática declarativa.
7. En el `playground`: una entidad con componente visual registrado como Previewable.

## Hito de aceptación

- Editar la entidad demo regenera su PNG sin comandos manuales.
- `preview:manage regenerate --type=demo` regenera en lote vía cola.
- Cero referencias a rutas de CSS por glob ni base64 manual.

## Riesgos / decisiones abiertas

- **Chromium en servidor**: Browsershot necesita Chrome headless. Documentar
  instalación y `noSandbox` en producción (ya resuelto en choque, se reutiliza).
- **Datos para la ruta de render**: ¿la `app` pide los datos por API o el backend
  pasa un token con payload? Propuesta: ruta de render protegida que pide por API
  con un token de servicio.
- **Rendimiento**: capturas en paralelo limitadas por nº de Chromes; usar cola con
  workers acotados (como hoy).
