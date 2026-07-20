<?php

// Config del motor propia del playground (se fusiona con la del paquete
// edc-motor/core). Rutas del menú (doc 10 ampliado): las secciones de
// entidades del app (src/entities/registry.ts) + el apartado de descargas.
return [
    'menu' => [
        'routes' => ['characters', 'houses', 'downloads'],
    ],
];
