<?php

// Config del motor propia de ESTE juego (se fusiona con la del paquete
// edc-motor/core — MotorServiceProvider::mergeConfigFrom — así que solo hace
// falta declarar aquí las claves que el juego ajusta).
return [
    // Rutas propias que este juego ofrece al menú configurable (doc 10
    // ampliado): el admin las lista junto a las páginas del CRM; cada clave
    // se mapea a ruta+etiqueta en AppHeader.vue (app) y MenuView.vue (admin).
    // Ejemplo, tras registrar tus entidades en src/entities/registry.ts:
    // 'menu' => [
    //     'routes' => ['cartas', 'downloads'],
    // ],
    'menu' => [
        'routes' => [],
    ],
];
