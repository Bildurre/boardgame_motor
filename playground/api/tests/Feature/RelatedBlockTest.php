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
});

it('latest devuelve las más recientes publicadas, respetando count', function () {
    $old = makeCharacter(['name' => ['es' => 'Viejo'], 'is_published' => true]);
    $mid = makeCharacter(['name' => ['es' => 'Medio'], 'is_published' => true]);
    $new = makeCharacter(['name' => ['es' => 'Nuevo'], 'is_published' => true]);
    makeCharacter(['name' => ['es' => 'Borrador'], 'is_published' => false]);

    $block = makeRelatedBlock([
        'preview_key' => 'character',
        'mode' => 'latest',
        'count' => 2,
    ]);

    $data = app(BlockTypeRegistry::class)->get('related')->resolveData($block, 'es');

    expect($data['key'])->toBe('character')
        ->and(collect($data['items'])->pluck('id')->all())->toBe([$new->id, $mid->id])
        ->and($data['items'][0])->toBe([
            'id' => $new->id,
            'name' => 'Nuevo',
            'slug' => 'nuevo',
            'preview' => null,
        ]);
});

it('random devuelve count publicadas al azar (default 4)', function () {
    $published = collect(range(1, 6))->map(
        fn (int $i) => makeCharacter(['name' => ['es' => "Personaje {$i}"], 'is_published' => true])
    );
    makeCharacter(['name' => ['es' => 'Borrador'], 'is_published' => false]);

    $type = app(BlockTypeRegistry::class)->get('related');

    $block = makeRelatedBlock([
        'preview_key' => 'character',
        'mode' => 'random',
        'count' => 3,
    ]);
    $data = $type->resolveData($block, 'es');

    expect($data['items'])->toHaveCount(3)
        ->and(collect($data['items'])->pluck('id')->diff($published->pluck('id')))->toBeEmpty();

    // Sin count en settings: el default del campo (4) manda.
    $block = makeRelatedBlock(['preview_key' => 'character', 'mode' => 'random']);
    expect($type->resolveData($block, 'es')['items'])->toHaveCount(4);
});

it('no revienta si la clave ya no está en el registry', function () {
    $block = makeRelatedBlock(['preview_key' => 'desaparecida', 'mode' => 'latest']);

    $data = app(BlockTypeRegistry::class)->get('related')->resolveData($block, 'es');

    expect($data)->toBe(['key' => null, 'items' => []]);
});
