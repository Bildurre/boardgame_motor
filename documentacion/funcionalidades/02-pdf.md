# 02 · Generación de PDF

> **Estado: implementado (Fase 4 ✅).** Notas de implementación al final.

## Qué hace

Monta **PDF recortables** a partir de los PNG (doc 01): hojas A4 con cartas/counters
para imprimir y recortar, "mazos"/colecciones, y páginas del CRM imprimibles.
Soporta multi-idioma, PDF **permanentes** (asociados a una entidad) y **temporales**
(el usuario elige ítems al vuelo). **Objetivo nº1: regenerar un PDF debe ser trivial.**

## Qué hay hoy en choque (y qué duele)

`Jobs\GeneratePdfJob` (DomPDF) + `Services\Pdf\*` (PdfExportService y export
services por tipo) + `Models\GeneratedPdf` + `TemporaryCollectionService` (sesión).

Lo que funciona y conservamos:
- DomPDF carga una vista que coloca los PNG en rejilla → A4. Simple y fiable.
- `GeneratedPdf` distingue permanentes/temporales con `expires_at` + limpieza.
- Colecciones temporales: elegir ítems con copias → PDF a la carta.
- Expandir copias (una carta ×3 = 3 huecos).

Lo que duele:
- **`GeneratedPdf` tiene FK a fuego del juego**: `faction_id`, `deck_id`, `page_id`.
  No es extensible: un juego nuevo no puede asociar un PDF a *su* entidad sin tocar
  el motor. → debe ser **polimórfico**.
- **Un export service por tipo** (`DeckExportService`, `FactionExportService`,
  `CutOutCountersExportService`, `PagesExportService`) con mucho copy-paste:
  cada uno arma items, itera locales, crea `GeneratedPdf`, despacha job.
- La **vista Blade** del PDF es del juego y está mezclada con el motor.
- Regenerar implica borrar+recrear a mano en cada service.

## Diseño nuevo

**Principio:** el motor da un **pipeline genérico** "lista de ítems imprimibles →
rejilla de PNG → A4 PDF", parametrizado por un **layout** (tamaño de pieza,
sangrado, marcas de corte, piezas por página). El juego solo describe *qué* va
dentro; nunca reescribe el ensamblado.

### Modelo de datos

`generated_pdfs` (motor), **polimórfico**:

| Campo | Notas |
|---|---|
| `id` | |
| `type` | semántico libre (`cut-out`, `collection`, `page`…) |
| `source_type` / `source_id` | **morphTo**: la entidad dueña (Faction, Deck, Page… del juego) o null si temporal |
| `owner_id` | usuario que lo generó (para temporales por usuario) |
| `locale` | |
| `path` / `filename` | |
| `layout` | clave del layout usado |
| `is_permanent` | |
| `expires_at` | |

→ Adiós `faction_id/deck_id/page_id`. Cualquier entidad del juego puede ser dueña
vía `morphTo`.

### Backend del motor (`core/src/Pdf/`)

```
Pdf/
├── GeneratedPdf.php             # modelo polimórfico
├── PdfService.php               # API pública: generateFor(source, options)
├── PdfComposer.php              # arma la rejilla de PNG → HTML → DomPDF
├── Layout/
│   ├── PrintLayout.php          # tamaño pieza, márgenes, sangrado, marcas corte,
│   │                            #   piezas/página, A4/A3…
│   └── layouts.php              # presets (carta 88×126, counter, etc.) publicable
├── PrintableItem.php            # value object: {previewUrl|png, copies, meta}
├── TemporaryCollection.php      # colección temporal (por usuario/token, no sesión)
├── Jobs/GeneratePdfJob.php      # async, multi-locale
├── Console/PdfCleanupCommand.php
└── views/grid.blade.php         # plantilla genérica del motor (rejilla de PNG)
```

- **`PdfService::generateFor($source, $options)`**: punto único. Recibe la entidad
  dueña (o null), una **lista de `PrintableItem`** y un layout. Crea el
  `GeneratedPdf`, despacha el job, y devuelve el registro. **Regenerar = volver a
  llamar**: el service borra el anterior del mismo `(source, type, locale)` y rehace.
  Esto mata el copy-paste de los export services.
- **`PdfComposer`**: expande copias, mete los PNG en `grid.blade.php` según el
  `PrintLayout`, y entrega a DomPDF. La plantilla del motor es **agnóstica**: solo
  pinta imágenes en rejilla con marcas de corte. (Si un juego quiere una portada o
  layout especial, registra su propia vista — ver frontera.)
- **`TemporaryCollection`**: igual que hoy (añadir/quitar/copias) pero ligada al
  **usuario autenticado o a un token** en vez de a la sesión Blade (somos API/SPA).
- **Cola + limpieza**: `GeneratePdfJob` multi-locale; `PdfCleanupCommand`
  programado borra temporales caducados (hoy `CleanupTemporaryPdfs`).

### API

```
GET    /api/v1/pdfs                       # listar (filtros: type, source, locale)
POST   /api/v1/pdfs/generate              # {source_type, source_id, type, layout, locale?}
POST   /api/v1/pdfs/{pdf}/regenerate
DELETE /api/v1/pdfs/{pdf}
GET    /api/v1/pdfs/{pdf}/download

# Colección temporal del usuario
GET    /api/v1/pdf-collection
POST   /api/v1/pdf-collection/items       # {type, id, copies}
DELETE /api/v1/pdf-collection/items/{key}
POST   /api/v1/pdf-collection/generate    # → PDF temporal
```

### Admin (`admin-kit/src/pdf/`)

- **`PdfManager`**: lista los PDF de una entidad, botones **Generar / Regenerar /
  Descargar / Borrar** (un clic), estado (en cola / listo / error), por idioma.
- Reutilizable: el admin de cada juego lo monta pasándole la entidad.

## Frontera motor ↔ juego

| Motor | Juego |
|---|---|
| Pipeline genérico, `GeneratedPdf` polimórfico, layouts, cola, limpieza | Decide **qué** entidades exportan y con qué layout |
| Plantilla `grid.blade.php` genérica + marcas de corte | (Opcional) registra una vista de PDF propia para layouts especiales |
| `PdfManager` en admin | Monta `PdfManager` en las pantallas de sus entidades |
| Colección temporal por usuario | (Ej. choque) "guardar mazo para imprimir" sobre la colección |

## Pasos

1. `GeneratedPdf` polimórfico + migración del motor.
2. `PrintLayout` + presets + `PrintableItem`.
3. `PdfComposer` + `grid.blade.php` + `GeneratePdfJob`.
4. `PdfService.generateFor/regenerate` (borra+rehace).
5. `TemporaryCollection` por usuario + endpoints.
6. API REST + `PdfManager` en admin-kit.
7. `PdfCleanupCommand` programado.
8. Playground: exportar una entidad demo a "cut-out" A4 y regenerar con un clic.

## Hito de aceptación

- Generar y **regenerar** un PDF de una colección desde el admin con un clic.
- Un usuario arma una colección temporal de ítems elegidos y descarga su PDF.
- Asociar un PDF a una entidad nueva **sin tocar el motor** (solo `morphTo`).

## Decisiones (cerradas)

- **Motor de ensamblado** → **DC-06**: **DomPDF** ensambla desde los PNG;
  Browsershot solo genera PNG (doc 01).
- **Marcas de corte y sangrado** → **DC-07**: presets en `PrintLayout` (carta
  88×126 mm con sangrado y marcas de corte, counters, A4/A3, piezas por página),
  publicables y ampliables por juego.

## Riesgos

- Que los presets de corte cuadren con el recorte físico real (verificar imprimiendo).
- Tamaño/peso de los PNG a scale factor alto (equilibrio en doc 01).

## Notas de implementación (Fase 4)

- El "qué va dentro" lo declara el juego con **exports** (`PdfExportRegistry` +
  facade `Pdfs`, espejo del PreviewRegistry): tres sabores demostrados en el
  playground — colección por entidad (`house-schemes`), global (`characters`)
  e individual con copias (`character-card`). Guía §6.
- `PdfService::generate()` reutiliza el registro de (type, source, locale) y
  versiona el fichero (URL nueva, borra el anterior): regenerar = volver a llamar.
- Las previews que falten se generan **en el momento** al componer (sin pasos
  manuales previos).
- La rejilla `motor::pdf.grid` usa **posicionamiento absoluto en mm** (nada de
  floats: DomPDF los maneja mal con marcas absolutas) y desplaza el margen de
  página para que las marcas de corte no necesiten coordenadas negativas.
- La colección temporal vive en BD por usuario (`pdf_collection_items`) y el
  PDF temporal guarda un **snapshot** en `payload` (regenerable aunque la
  colección cambie). `pdf:cleanup` borra caducados (programar en el juego).
- El estado del job queda en el registro (`pending/ready/failed` + `error`),
  visible en el `PdfManager` del admin.
- Los tests componen PDF **reales** con DomPDF (sin browser): se verifica hasta
  el número de páginas resultante.
