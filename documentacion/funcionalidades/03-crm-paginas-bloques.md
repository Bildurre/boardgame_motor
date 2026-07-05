# 03 · CRM de páginas y bloques

> **Estado: implementado (Fase 5 ✅ + flecos ✅).** Notas: los valores de
> TODOS los campos de un bloque viven en `settings` JSON (sin columnas
> title/subtitle/content); los campos comunes (align, **width**
> full/wide/narrow y background como **tinte semitransparente** por tema)
> los añade el motor a cada tipo; el DSL v1 cubre text/textarea/richtext/
> number/boolean/select/color/image — la **imagen es multilingüe**
> (`->translatable()`, editor TranslatableImage) y sube por
> POST /admin/content/uploads. Hechos también: bloque índice automático,
> PDF de páginas imprimibles (export `pages`), plantillas por juego en la
> SPA (`templateRegistry`), `background_image` por página (capa
> PageBackground atenuada por tema) y posición de imagen "el texto la
> rodea" (clear-left/right). DSL anidado: **group / repeater / entity**
> (validación, saneado y localización recursivos; editor con filas
> añadir/quitar/reordenar y buscador de entidad sobre el endpoint de
> opciones del juego; demos: bloque `faq` del motor y `featured-house` del
> playground). **Doc completado.**

## Qué hace

Construye la **web pública** (el expositor) por composición de **bloques** dentro
de **páginas**. Páginas jerárquicas, traducibles, con SEO, plantillas y flag de
imprimible. Bloques reordenables de varios tipos: unos de presentación, otros que
consultan datos del juego. **Objetivo: añadir un bloque nuevo sin sufrir.**

## Qué hay hoy en choque (y qué duele)

`Models\Page` + `Models\Block` + `Services\Content\{PageService,BlockService,
BlockDataService}` + reglas por tipo en `Http/Requests/Admin/BlockRules/*`.

Tipos de bloque actuales:
- **Presentación** (genéricos): `header`, `text`, `text-card`, `quote`, `cta`.
- **Con datos** (dependen del juego): `counters-list`, `relateds`,
  `automatic-index`, `game-modes`.

Lo que funciona y conservamos:
- Page/Block traducibles (`title`, `subtitle`, `content`), `settings` JSON por
  bloque, jerarquía (`parent_id`), orden, `is_printable`/`is_indexable`, plantillas,
  SEO (`meta_title`, `meta_description`), slug traducible.
- `BlockDataService` resuelve los datos de los bloques-con-datos.

Lo que duele (el "desastre"):
- **Cada tipo de bloque está cableado en varios sitios a la vez**: un `match($type)`
  en `BlockDataService`, una clase de reglas en `BlockRules/`, una vista de
  edición, una vista de render, y un `match` para el formulario de creación. Añadir
  uno obliga a tocar 5 lugares.
- **Los bloques-con-datos referencian modelos del juego a fuego** (`Counter`,
  `Hero`, `Card`, `Faction`…) dentro de un service del "core" → no hay frontera:
  el motor no puede llevar el CRM sin conocer las entidades del juego.
- Detección del "modelo actual" hurgando en parámetros de ruta.

## Diseño nuevo

**Principio:** un bloque es un **tipo declarado** en un único sitio
(`BlockType`), que reúne todo lo suyo: esquema de campos, validación, resolución
de datos y componente de render. El motor trae los de presentación; **el juego
registra los suyos** en un `BlockTypeRegistry`. Añadir un bloque = registrar un
`BlockType`, nada más.

### Modelo de datos (motor)

`pages` y `blocks` prácticamente como hoy, en el motor:

- **`pages`**: `title`, `description`, `slug` (traducibles), `parent_id`, `order`,
  `template`, `is_published`, `is_printable`, `meta_title`, `meta_description`,
  `background_image`. Soft-deletes.
- **`blocks`**: `page_id`, `parent_id`, `type`, `title`/`subtitle`/`content`
  (traducibles; `content` es JSON para tipos ricos), `order`, `is_printable`,
  `is_indexable`, `background_color`, `image` (multilingüe), `settings` (JSON).

El motor **no añade columnas por tipo**: cada tipo guarda lo suyo en `settings` +
`content`. Por eso el registro es lo que da forma.

### El `BlockType` (pieza central)

Un tipo de bloque se declara una vez (backend), describiendo:

```
BlockType {
  key:           'quote' | 'counters-list' | …
  name:          traducible (para la paleta del admin)
  icon:          icono en la paleta
  category:      'presentation' | 'data' | 'layout'
  fields():      esquema declarativo de campos (qué edita el admin)
                 → genera el formulario en el admin-kit automáticamente
  rules():       validación derivada del esquema (sustituye BlockRules/*)
  resolveData(block): datos extra para el render (sustituye BlockDataService)
                 → para los de presentación, vacío
  component:     nombre del componente Vue de render (en app)
}
```

- **Presentación** (motor): `header`, `text`, `text-card`, `quote`, `cta` con
  `resolveData` vacío.
- **Con datos** (juego): el juego declara `counters-list`, `relateds`, etc. y en su
  `resolveData` consulta **sus** modelos. El motor nunca importa `Counter`/`Hero`.

```
core/src/Content/
├── Page.php · Block.php
├── PageService.php · BlockService.php   # CRUD, reorder, traducciones
├── BlockType.php (abstract) + Contracts
├── BlockTypeRegistry.php                # registro central (motor + juego)
├── BlockTypes/                          # los de presentación
│   ├── HeaderBlock.php · TextBlock.php · TextCardBlock.php
│   ├── QuoteBlock.php · CtaBlock.php
└── Http/{Controllers,Requests,Resources}
```

El juego registra los suyos en su `AppServiceProvider`:

```php
BlockTypeRegistry::register(CountersListBlock::class);
BlockTypeRegistry::register(RelatedsBlock::class);
```

### API

```
# Páginas
GET/POST/PUT/DELETE /api/v1/pages           # CRUD, por slug, jerárquico
POST   /api/v1/pages/{page}/restore
POST   /api/v1/pages/set-home
# Bloques
GET    /api/v1/block-types                  # paleta: tipos disponibles + esquema de campos
GET/POST/PUT/DELETE /api/v1/pages/{page}/blocks
POST   /api/v1/pages/{page}/blocks/reorder
# Render público
GET    /api/v1/public/pages/{slug}          # página + bloques + datos resueltos, por locale
```

`GET /block-types` devuelve el **esquema de campos** de cada tipo → el editor del
admin se construye solo a partir de ahí (no hay formularios por tipo a mano).

### Admin (`admin-kit/src/content/`)

- **`PageEditor`**: datos de página + SEO + plantilla + jerarquía.
- **`BlockPalette`**: lista los `block-types` (con icono/categoría) para añadir.
- **`BlockEditor`**: **genera el formulario desde el esquema de campos** del tipo
  (texto, texto rico, imagen, select, número, referencia a entidad…). Un solo
  componente para todos los tipos → añadir un tipo no toca el admin.
- **Reorder** con drag (sortable), `is_printable`/`is_indexable`, color de fondo.

### Público (`app`)

- **`PageView`**: pide `/public/pages/{slug}`, itera bloques y monta el componente
  Vue de cada tipo (`component` del `BlockType`). Los de presentación vienen en
  `@bgm/ui`; los de datos, en `app` del juego.
- SEO por `meta_*` (doc 10). Páginas con `is_printable` enlazan con PDF (doc 02).

## Frontera motor ↔ juego

| Motor | Juego |
|---|---|
| Page/Block, CRUD, reorder, i18n, SEO, plantillas | Registra sus `BlockType` con datos |
| Bloques de presentación (back + componentes Vue) | Componentes Vue de sus bloques con datos |
| Editor de bloques dirigido por esquema | (Nada: el editor se genera solo) |
| `BlockTypeRegistry` | `register(MiBloque::class)` en su provider |

## Pasos

1. Modelos Page/Block + migraciones del motor + servicios CRUD/reorder/i18n.
2. `BlockType` (abstract) + `BlockTypeRegistry` + contrato de esquema de campos.
3. Bloques de presentación del motor (back + componentes en `@bgm/ui`).
4. API (`/pages`, `/block-types`, render público).
5. `PageEditor` + `BlockPalette` + `BlockEditor` dirigido por esquema en admin-kit.
6. `PageView` en `app` (render por componente de tipo).
7. Playground: registrar un bloque-con-datos propio y verlo en la web pública.

## Hito de aceptación

- Crear una página con varios bloques (incluido uno con-datos del playground),
  reordenar, traducir, publicar y verla en público con su **URL traducible**.
- **Añadir un tipo de bloque nuevo = registrar un `BlockType`** (sin tocar admin
  ni el render genérico).

## Decisiones (cerradas)

- **Esquema de campos** → **DC-08**: DSL base `text, richtext, number, boolean,
  select, multiselect, image (simple+multilingüe), color, entity-ref, repeater,
  group`, **compartido** con el `ResourceForm` del admin-kit (un solo renderer).
- **Texto rico** → **DC-09**: **TipTap**, con nodos inline a medida para los
  "dados"/iconos y sanitización en servidor.
- **Caché de bloques-con-datos** → **DC-10**: payload por `(page, locale)`,
  invalidado al cambiar página/bloques o entidades referenciadas.

## Riesgos

- Que el DSL de campos sea lo bastante expresivo sin volverse un framework propio
  (mantener escotilla a campos a medida).
