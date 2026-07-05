# Prueba de consumo externo (Fase 7)

Verifica que el motor es **consumible por versión desde fuera del monorepo**
(el hito de la Fase 7), sin tocar el repo:

```bash
tools/consumo-externo/probar-consumo.sh            # usa un mktemp -d
tools/consumo-externo/probar-consumo.sh /ruta/dir  # o un directorio dado
```

Qué hace:

1. **Composer**: crea un proyecto externo que exige `bgm/core` a la versión
   actual (repositorio `path` hacia `packages/core`), lanza `composer
   install` real (descarga Sanctum/Spatie/etc.) y comprueba que las clases
   clave del motor autocargan y que la versión instalada es la esperada.
2. **Vite**: crea una app externa mínima con `@bgm/ui` y `@bgm/admin-kit`
   por `file:`, con los `loadPaths` de SCSS hacia los tokens del paquete, y
   la compila (`vite build`) importando componentes de ambos paquetes, un
   composable (`useHead`) y SCSS del motor.

Úsala antes de etiquetar una versión (ver §6 de
`documentacion/guia-arrancar-un-juego-nuevo.md`). Necesita red (Packagist y
npm) y PHP + Composer + Node en el PATH.
