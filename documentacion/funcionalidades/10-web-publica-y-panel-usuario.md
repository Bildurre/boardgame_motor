# 10 · Web pública y panel de usuario

> **Estado: web pública implementada (Fases 5/5.5/6 ✅).** Además de lo de
> las Fases 5/5.5 (shell, `PageView`/home con plantillas, `/_render`,
> Configuración de la web completa con acento aleatorio y webfonts), la
> Fase 6 añade: **prefijo de locale** en el router (`/es`·`/eu`·`/en`; el
> guard sincroniza el store y vue-i18n, el cambio de idioma navega y cada
> vista redirige a su canónica, DC-12), **SEO** con `useHead` de @bgm/ui
> (title/description/canonical/hreflang), **sitemap.xml** servido por la API
> (páginas del CRM del motor + entidades del juego vía facade `Sitemap`),
> **prerender en build** (`npm run prerender` recorre el sitemap con
> Chromium y escribe dist/<ruta>/index.html, DC-18) y **listados de
> entidades genéricos** (`entityRegistry` + `EntityIndexView`/
> `EntityDetailView`; playground: personajes y casas con sus cartas).
> Y el **panel de usuario extensible**: `AccountLayout` (menú lateral) con
> una child route por sección registrada en `src/account/registry.ts` — el
> motor aporta Mis datos y Contraseña (doc 05) y el juego cuelga las suyas;
> el playground engancha "Para imprimir" (colección PDF del doc 02: añadir
> cartas, copias, generar en cola y descargar). Vistas de auth y cuenta
> traducidas con vue-i18n. **Doc completado.**

## Qué hace

El andamiaje de la SPA `app`: la **web pública** (el expositor: home, navegación
por páginas del CRM, listados de entidades, SEO) y el **panel de usuario** para
los que se loguean, ambos extensibles por cada juego.

## Qué hay hoy en choque

`Services\Public\*` + controladores `Public\*` (CardService, FactionService,
HeroService, FactionDeckService) sirviendo Blade público; el CRM renderiza páginas.
No hay panel de usuario. → se moderniza a SPA Vue consumiendo la API.

## Diseño nuevo

**SPA `app` (andamiaje del motor, reusable):**
- **Shell público**: layout (navbar/footer), router con **prefijo de locale**
  (doc 04), home, y render de páginas del CRM (`PageView`, doc 03).
- **Listados de entidades genéricos**: un patrón de "índice + detalle por slug"
  que el juego configura para sus entidades (qué campos, qué filtros), reutilizando
  componentes del CRM (ej. el bloque `relateds`/`automatic-index`).
- **SEO en SPA**: `useHead` (de `@bgm/ui`) fija `<title>`/meta por ruta desde los
  `meta_*` de la página/entidad; **prerender** de las rutas públicas en el build
  para que el HTML inicial sea indexable (D6). Sitemap generado desde las páginas
  publicadas.
- **Ruta de render `/_render`** para previews (doc 01) vive aquí, aislada.

**Panel de usuario (base, vacío):**
- Layout de panel + secciones de **cuenta** y **configuración** (doc 05).
- **Puntos de extensión**: el juego registra secciones propias (menú + rutas +
  componentes). Ej. choque: "Mis mazos" (guardar mazos prehechos para imprimirse
  los PDF, enganchando con doc 02).

## Frontera motor ↔ juego

| Motor | Juego |
|---|---|
| Shell público, router localizado, PageView, SEO/prerender/sitemap, panel base, ruta de render | Sus vistas de entidad, su home concreta, sus secciones de panel de usuario |

## Pasos

1. Shell de `app` + router con prefijo de locale + home.
2. `PageView` (render del CRM) + integración SEO (`useHead`) + prerender + sitemap.
3. Patrón de índice/detalle de entidades configurable.
4. Panel de usuario base + registro de secciones del juego.
5. Ruta `/_render` aislada para previews.

## Hito de aceptación

- Web pública del playground navegable por páginas del CRM, con SEO básico y
  cambio de idioma que conserva la entidad.
- Panel de usuario con una sección propia del playground enganchada por extensión.

## Decisiones (cerradas)

- **SEO de la SPA** → **DC-18**: **prerender en build** de rutas públicas + `useHead`
  + **sitemap** desde páginas/entidades publicadas (build/cron). SSR descartado salvo
  que el prerender se quede corto en páginas de entidad muy dinámicas (plan B medido).
- **PWA** → **DC-01**: la `app` es instalable en móvil.

## Riesgos

- Indexación de muchas entidades dinámicas (cartas): validar que el prerender +
  sitemap basta; si no, SSR solo para público.
