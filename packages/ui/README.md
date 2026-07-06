# @edc-motor/ui

Componentes públicos de **EdC Motor** (Espadas de Ceniza Motor): la capa Vue 3
de la web pública de un juego de mesa construido sobre
[`edc-motor/core`](https://packagist.org/packages/edc-motor/core) — componentes
base (botones, formularios, modales, chips, migas…), tokens SCSS con temas
claro/oscuro, i18n, editor de texto rico (TipTap) y los composables del motor
(API, head/SEO, tema).

## Instalación

```bash
npm install @edc-motor/ui
```

Es un **paquete fuente** (`main: src/index.ts`): lo compila la app consumidora
con Vite. El consumidor necesita:

```bash
npm install -D vite @vitejs/plugin-vue sass-embedded typescript vue-tsc
```

y en su `vite.config.ts`, los tokens SCSS del paquete en `loadPaths`:

```ts
css: {
  preprocessorOptions: {
    scss: {
      additionalData: '@use "tokens" as *;\n',
      loadPaths: ['node_modules/@edc-motor/ui/scss'],
    },
  },
},
```

## Versionado

Versión *de tren* con `edc-motor/core` y `@edc-motor/admin-kit`: los tres
paquetes comparten número y se etiquetan juntos en el monorepo
[`bildurre/boardgame_motor`](https://github.com/bildurre/boardgame_motor),
donde viven el código, las guías, los issues y los pull requests.

## Licencia

[GPL-3.0-only](LICENSE).
