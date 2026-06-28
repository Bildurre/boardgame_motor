# Boardgame Motor — Visión y decisiones

> Documento vivo. Recoge **qué** estamos construyendo, **por qué**, y las
> **decisiones cerradas** que dan forma a todo lo demás. Si una decisión
> cambia, se edita aquí primero.

---

## 1. El problema

Tengo un juego de mesa (**Choque de Leyendas**) gestionado con una app Laravel.
El flujo es: creo las entidades (cartas, clases, héroes, facciones…), genero los
**PDF recortables** para jugar en físico, y genero **páginas web** (a partir de
bloques) que sirven de expositor público para ver cartas y leer reglas.

Tengo **más juegos en espera** de su propia web. El proceso será el mismo en
todos. No quiero rehacer desde cero el generador de PDF, el CRM de páginas y
bloques, el export, el backup, el panel de admin, etc., en cada juego.

Además, lo que ya existe en Choque tiene deuda que quiero **rehacer casi desde
cero**:

- El **generador de PDF** no va del todo fino; regenerar un PDF debería ser trivial.
- El **CRM** es un desastre; añadir un bloque nuevo es un lío.
- Las **traducciones y URLs traducibles** no funcionan bien, y menos en el admin.

## 2. El objetivo

Un **motor común** (`boardgame_motor`) que aporte todo lo reutilizable, y que
cada juego consuma para construir su web con el mínimo esfuerzo, programando
**solo sus entidades propias**.

Tres metas, compatibles entre sí:

1. **No duplicar** el código común entre juegos.
2. **Base técnica moderna**: API REST + Vue, mejor arquitectura.
3. **Refactor** de los servicios que duelen (PDF, CRM, i18n), casi desde cero.

## 3. Los tres repos de referencia

| Repo | Papel |
|---|---|
| **kontuan** | **Inspiración de arquitectura, no se toca.** App de gestión de empresas (nada que ver con juegos). De aquí copiamos *ideas* modernas: Vue 3 + TS, monorepo con `packages/shared`, separación api/app/admin, tokens SCSS, i18n, patrones de componentes. No clonamos ni forkeamos: solo miramos. |
| **choque_de_leyendas** | **Fuente de funcionalidad y de bugs a corregir.** Laravel monolito (Blade, sin Vue). De aquí sale *qué* tiene que hacer el motor y *qué* hay que arreglar. **Se queda intacto y en producción** hasta que el motor esté terminado; será su primer cliente, no un conejillo de indias. |
| **boardgame_motor** | **Lo nuevo, desde cero.** El motor que publicamos como paquetes. |

## 4. Decisiones cerradas

| # | Decisión | Detalle |
|---|---|---|
| D1 | **Modelo de reutilización: paquetes versionados** (no multi-tenant, no plantilla) | El motor son paquetes con versión. Cada juego es **su propio repo** que instala el motor, **fija una versión**, y sube cuando quiere. Mejora central → versión nueva → nadie se rompe. Es lo único que cumple a la vez "mejora a todas" **y** "no romper las que funcionan". |
| D2 | **No multi-tenant** | Cada juego = su app y su base de datos. Nada de "un despliegue para todos los juegos". El sistema de módulos *activables* de kontuan **no aplica** aquí. |
| D3 | **Cada juego programa su propio dominio** | El motor NO es un constructor de entidades configurable. Es un **framework / caja de herramientas**: da clases base, traits, servicios y componentes; cada juego define `Card`, `Hero`, etc. en código. |
| D4 | **choque intacto** hasta que el motor esté terminado | Primer cliente del motor cuando esté listo. No se migra antes. |
| D5 | **Backend: Laravel (última) + API REST** | Como choque y kontuan. |
| D6 | **Frontend: 2 SPA Vue por juego** | `admin` (admin + editores) y `app` (web pública del expositor + panel de usuario al loguearse). Ambas Vue 3 + Vite + TS. **SPA, no SSR/Nuxt.** SEO se resuelve con gestión de `<head>`/meta + prerender donde haga falta. |
| D7 | **Auth con 3 roles simples** | `admin` (yo), `editor` (ayuda con algunas cosas), `user` (sin acceso al admin). El motor trae el panel de usuario y opciones de cuenta/config montadas pero "vacías", listas para que cada juego cuelgue lo suyo. Sin roles complejos. |
| D8 | **Pipeline PDF: fuente única de verdad** | El **componente Vue** de la entidad sirve para (a) mostrarse en web, (b) capturarse a PNG con browser headless, y (c) montar el PDF con esos PNG. Nada de plantillas de impresión duplicadas. |
| D9 | **Distribución de paquetes: Git + tags** | Repos Git con tags de versión (Composer vía VCS, npm vía Git/GitHub Packages). Sin Packagist/npm públicos. *(Cerrado: ver DC-02.)* |
| D10 | **Frontends instalables en móvil (PWA)** | `admin` y `app` son PWA instalables (Add to Home Screen) vía `vite-plugin-pwa`: manifest + service worker, app-shell cacheado, modo standalone. No offline completo de datos. *(Ver DC-01.)* |
| D11 | **Marca y namespace: `bgm`** | El proyecto es **BGM** (BoardgameMotor). Paquetes `bgm/core` (Composer), `@bgm/ui` y `@bgm/admin-kit` (npm). *(Ver DC-21.)* |
| D12 | **Infra: un droplet DigitalOcean por juego** | Cada web (api+admin+app) en su droplet, con worker de cola + Chromium. Storage configurable: disco por defecto, S3/Spaces opcional. *(Ver DC-22.)* |
| D13 | **Locales por defecto: es / eu / en** | Euskera incluido; `es` por defecto; configurable por juego. *(Ver DC-23.)* |

> **Todas las cuestiones técnicas que quedaban abiertas están resueltas en
> [`03-decisiones-cerradas.md`](03-decisiones-cerradas.md) (DC-01 … DC-23).**

## 5. Glosario rápido

- **Motor**: los paquetes comunes de `boardgame_motor`.
- **Juego**: una app concreta (Choque, y los futuros) que consume el motor.
- **Entidad de juego**: modelo propio de un juego (Card, Hero…). No la toca el motor.
- **Expositor**: la web pública donde se ven cartas y reglas.
- **Preview / PNG**: imagen generada capturando el componente visual de una entidad.
- **Bloque-con-datos**: bloque del CRM que consulta entidades del juego (lo registra cada juego).
