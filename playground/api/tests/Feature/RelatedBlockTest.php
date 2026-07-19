<?php

use Edc\Core\Content\BlockTypeRegistry;
use Edc\Core\Content\Models\Block;
use Edc\Core\Content\Models\Page;

// Bloque 'related' del motor: rejilla de entidades del registry de previews
// (más recientes o aleatorias) en formato ítem de catálogo.

function makeRelatedBlock(array $settings): Block
{
    $page = new Page;
    $page->setTranslations('title', ['es' => 'Página con relacionados']);
    $page->is_published = true;
    $page->save();

    return Block::create([
        'page_id' => $page->id,
        'type' => 'related',
        'order' => 1,
        'settings' => $settings,
        'is_printable' => false,
        'is_indexable' => false,
    ]);
}

it('está registrado con las claves del registry de previews como opciones', function () {
    $registry = app(BlockTypeRegistry::class);

    expect($registry->has('related'))->toBeTrue();

    $schema = $registry->get('related')->toArray();
    expect($schema['category'])->toBe('data');

    // Las opciones de preview_key salen del registry EN VIVO (las del juego).
    $field = collect($schema['fields'])->firstWhere('key', 'preview_key');
    expect(array_keys($field['options']))
        ->toContain('character', 'scheme', 'house', 'house-counter');

    // Sin campo de número de elementos: siempre 6 (el grid recorta por ancho).
    expect(collect($schema['fields'])->pluck('key'))->not->toContain('count');
});

it('latest devuelve las 6 más recientes publicadas', function () {
    $published = collect(range(1, 7))->map(
        fn (int $i) => makeCharacter(['name' => ['es' => "Personaje {$i}"], 'is_published' => true])
    );
    makeCharacter(['name' => ['es' => 'Borrador'], 'is_published' => false]);

    $block = makeRelatedBlock(['preview_key' => 'character', 'mode' => 'latest']);

    $data = app(BlockTypeRegistry::class)->get('related')->resolveData($block, 'es');

    expect($data['key'])->toBe('character')
        ->and(collect($data['items'])->pluck('id')->all())
        ->toBe($published->reverse()->take(6)->pluck('id')->values()->all())
        ->and($data['items'][0])->toBe([
            'id' => $published->last()->id,
            'name' => 'Personaje 7',
            'slug' => 'personaje-7',
            'preview' => null,
        ]);
});

it('random devuelve 6 publicadas al azar (el count de settings viejos se ignora)', function () {
    $published = collect(range(1, 8))->map(
        fn (int $i) => makeCharacter(['name' => ['es' => "Personaje {$i}"], 'is_published' => true])
    );
    makeCharacter(['name' => ['es' => 'Borrador'], 'is_published' => false]);

    $type = app(BlockTypeRegistry::class)->get('related');

    // Un bloque guardado antes del cambio aún trae count: no manda.
    $block = makeRelatedBlock([
        'preview_key' => 'character',
        'mode' => 'random',
        'count' => 3,
    ]);
    $data = $type->resolveData($block, 'es');

    expect($data['items'])->toHaveCount(6)
        ->and(collect($data['items'])->pluck('id')->diff($published->pluck('id')))->toBeEmpty();
});

it('no revienta si la clave ya no está en el registry', function () {
    $block = makeRelatedBlock(['preview_key' => 'desaparecida', 'mode' => 'latest']);

    $data = app(BlockTypeRegistry::class)->get('related')->resolveData($block, 'es');

    expect($data)->toBe(['key' => null, 'items' => []]);
});
