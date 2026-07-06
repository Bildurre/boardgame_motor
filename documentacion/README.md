# Documentación — EdC Motor

Motor común (paquetes versionados) para construir webs de juegos de mesa: API REST
(Laravel) + admin y público (Vue), con generación de PDF/PNG, CRM de páginas y
bloques, i18n, auth y backup reutilizables. Cada juego consume el motor y programa
solo sus entidades.

> Estado: **Fases 0–7 completadas** — motor versionado (`v0.1.0`, versión de
> tren), con CI y consumible por versión desde un repo externo (DC-33).
> Siguiente: Fase 8, la migración de choque.

## Índice

1. [Visión y decisiones](00-vision-y-decisiones.md) — qué, por qué, decisiones cerradas (D1–D10).
2. [Arquitectura](01-arquitectura.md) — paquetes, topología de un juego, frontera motor/juego.
3. [Registro de decisiones (ADR)](03-decisiones-cerradas.md) — todas las cuestiones técnicas resueltas (DC-01 … DC-33).
4. [Plan de acción](02-plan-de-accion.md) — fases, dependencias, hitos.
5. [Planes por funcionalidad](funcionalidades/00-indice.md) — el "cómo" de cada pieza.
6. [Guía de componentes](guia-de-componentes.md) — catálogo de `@edc-motor/ui` y `@edc-motor/admin-kit`: uso y finalidad. **Se mantiene al día.**
7. [Cómo montar una web nueva](guia-como-montar-una-web.md) — crear un juego, añadir modelos (y qué traits usar), iconos y vistas. **Se mantiene al día.**
8. [Cómo arrancar un juego nuevo](guia-arrancar-un-juego-nuevo.md) — consumir el motor **por versión** (tag + composer `path` + npm `file:`), esqueleto del repo, CI y checklist del primer día. **Se mantiene al día.**

## Mapa rápido de los repos

- **kontuan** — inspiración de arquitectura (no se toca, no es un juego).
- **choque_de_leyendas** — fuente de funcionalidad y de bugs; intacto hasta que el
  motor esté listo; será su primer cliente.
- **boardgame_motor** — este repo; el motor, desde cero.
