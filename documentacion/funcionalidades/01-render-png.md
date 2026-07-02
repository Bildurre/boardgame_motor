# 01 · Render de componentes a PNG

> **Estado: implementado (Fase 3 ✅).** Notas de implementación al final.

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

### Backend del motor (`core/src/Previews/`)

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

1. Infra Browsershot en `core` (`PreviewRenderer` + config publicable).
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

## Decisiones (cerradas)

- **Datos de la ruta de render** → **DC-04**: la ruta `/_render` pide los datos por
  API con un **token de servicio de vida corta** firmado; no es pública.
- **Chromium y rendimiento** → **DC-05**: Chrome headless con `noSandbox` + args de
  producción (reutilizado de choque); generación **en cola** con workers acotados.

## Riesgos

- Memoria/concurrencia de Chrome: límite de instancias configurable.
- Calidad vs peso del PNG (scale factor) — afecta también al PDF (doc 02).

## Notas de implementación (Fase 3)

- `previewSize()` se declara en **píxeles CSS** (la carta demo: 350×500, proporción
  88×126 mm); la calidad la da `motor.previews.scale` (deviceScaleFactor, def. 2).
  El mapeo px→mm exacto se cerrará con los presets de impresión del PDF (DC-07).
- El token de servicio (DC-04) vive en **caché** con TTL `motor.previews.token_ttl`
  (def. 300 s): `RenderToken::issue()` al lanzar Browsershot; `GET api/render/{entity}/{id}`
  lo exige y solo vale para esa entidad+id.
- La ruta `/_render` marca `window.__bgmRenderReady` cuando el componente está
  montado con datos, fuentes (`document.fonts.ready`) e imágenes cargadas;
  `PreviewRenderer` espera esa señal (`waitForFunction`) además de `networkidle`.
- Los PNG se guardan **versionados** (`{locale}-{rand}.png`): cada render produce
  una URL nueva (sin cachés rancias) y borra el fichero anterior.
- La **imagen** de la entidad vive en MediaLibrary (no es columna), así que el
  trait no puede detectar su cambio: el controlador invalida a mano tras
  `setImageFromRequest()` (ver playground). La papelera conserva los PNG; el
  borrado definitivo los elimina.
- **Gestor en el admin** (`PreviewManager`, admin-kit): estado por tipo, lotes
  (generar pendientes / regenerar todo / borrar todo), entidades paginadas con
  su PNG por locale, acciones individuales y limpieza de huérfanos, sobre
  `GET/POST/DELETE api/admin/previews/...`. El comando `preview:manage` cubre
  lo mismo por CLI.
- Apagado global con `MOTOR_PREVIEWS=false` (tests, entornos sin Chromium).
- Chromium: por defecto el que descarga `puppeteer` (dependencia npm de la api
  del juego); en el droplet se fija `MOTOR_CHROME_PATH` (DC-22). El renderer usa
  el **headless moderno** (`newHeadless()`): sin él, Browsershot pide el binario
  `chrome-headless-shell`, que puppeteer no descarga por defecto (error
  "Could not find chrome-headless-shell"). Si falta el chrome de puppeteer:
  `cd api && npx puppeteer browsers install chrome`.
