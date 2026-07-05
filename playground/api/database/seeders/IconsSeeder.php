<?php

namespace Database\Seeders;

use Bgm\Core\Icons\Models\Icon;
use Illuminate\Database\Seeder;

/**
 * Iconos demo de la biblioteca (DC-32): los símbolos del juego que el editor
 * WYSIWYG inserta en línea. SVG generados aquí mismo (sin binarios en git).
 * Idempotente: si ya hay iconos, no toca nada.
 */
class IconsSeeder extends Seeder
{
    public function run(): void
    {
        if (Icon::count() > 0) {
            return;
        }

        $iconos = [
            'Espada' => '<path d="M4 28 L20 12 L24 4 L28 8 L20 12 M4 28 L8 24 M6 22 L10 26" stroke="#c0392b" stroke-width="2.5" fill="none" stroke-linecap="round"/>',
            'Escudo' => '<path d="M16 3 L27 7 V16 C27 23 22 27 16 29 C10 27 5 23 5 16 V7 Z" fill="#2980b9" stroke="#1a5276" stroke-width="2"/>',
            'Corona' => '<path d="M5 22 L5 10 L11 16 L16 7 L21 16 L27 10 L27 22 Z" fill="#f1c40f" stroke="#b7950b" stroke-width="2"/>',
            'Moneda' => '<circle cx="16" cy="16" r="11" fill="#f39c12" stroke="#9c640c" stroke-width="2.5"/><text x="16" y="21" font-size="14" text-anchor="middle" fill="#7d5109" font-family="serif" font-weight="bold">$</text>',
        ];

        foreach ($iconos as $name => $shape) {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="32" height="32">'.$shape.'</svg>';

            $icon = new Icon;
            $icon->name = $name;
            $icon->save();

            $icon->addMediaFromString($svg)
                ->usingFileName(str($name)->slug().'.svg')
                ->withCustomProperties(['mime-type' => 'image/svg+xml'])
                ->toMediaCollection('image');
        }
    }
}
