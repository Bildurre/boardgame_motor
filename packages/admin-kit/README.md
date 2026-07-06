# @edc-motor/admin-kit

Kit del panel de administración de **EdC Motor** (Espadas de Ceniza Motor):
layout completo (sidebar colapsable, panel derecho contextual, migas), gestores
CRUD declarativos (`defineResource`), y los gestores del motor listos para
montar — imágenes/previews, PDF, páginas y bloques, configuración de la web,
usuarios y copias de seguridad. Habla con la API de
[`edc-motor/core`](https://packagist.org/packages/edc-motor/core) y se apoya en
[`@edc-motor/ui`](https://www.npmjs.com/package/@edc-motor/ui).

## Instalación

```bash
npm install @edc-motor/admin-kit
```

Es un **paquete fuente** (`main: src/index.ts`): lo compila la app consumidora
con Vite (necesita `sass-embedded` y `@vitejs/plugin-vue`, ver el README de
`@edc-motor/ui`). En el `vite.config.ts` del admin, añade también su SCSS:

```ts
loadPaths: [
  'node_modules/@edc-motor/ui/scss',
  'node_modules/@edc-motor/admin-kit/scss',
],
```

## Versionado

Versión *de tren* con `edc-motor/core` y `@edc-motor/ui`: los tres paquetes
comparten número y se etiquetan juntos en el monorepo
[`bildurre/boardgame_motor`](https://github.com/bildurre/boardgame_motor),
donde viven el código, las guías, los issues y los pull requests.

## Licencia

[GPL-3.0-only](LICENSE).
