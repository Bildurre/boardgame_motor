<?php

use App\Models\Character;

// Catálogo público genérico (/api/catalog/{key}): cualquier entidad del
// registry de previews, sin auth, solo publicadas, ítem mínimo
// {id, name, slug, preview} con paginación/búsqueda o modo aleatorio.

it('devuelve 404 si la clave no está en el registry', function () {
    $this->getJson('/api/catalog/inexistente?locale=es')->assertNotFound();
});

it('lista solo publicadas, paginadas y con la clave en la respuesta', function () {
    foreach (range(1, 30) as $i) {
        makeCharacter(['name' => ['es' => "Personaje {$i}"], 'is_published' => true]);
    }
    makeCharacter(['name' => ['es' => 'Borrador'], 'is_published' => false]);

    // Página 1: 24 por defecto, orden id desc, meta estándar.
    $this->getJson('/api/catalog/character?locale=es')
        ->assertOk()
        ->assertJsonPath('key', 'character')
        ->assertJsonCount(24, 'data')
        ->assertJsonPath('data.0.name', 'Personaje 30')
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('meta.per_page', 24)
        ->assertJsonPath('meta.total', 30);

    // Página 2 con per_page propio (y tope 48 si se pasa de rosca).
    $this->getJson('/api/catalog/character?page=2&per_page=10&locale=es')
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.last_page', 3);

    $this->getJson('/api/catalog/character?per_page=100&locale=es')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 48);
});

it('busca por nombre sobre el locale activo', function () {
    makeCharacter(['name' => ['es' => 'Arya', 'eu' => 'Aitor'], 'is_published' => true]);
    makeCharacter(['name' => ['es' => 'Bran', 'eu' => 'Beñat'], 'is_published' => true]);

    $this->getJson('/api/catalog/character?search=ary&locale=es')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Arya');

    // En eu busca sobre la columna eu (no mezcla locales como HasFilters).
    $this->getJson('/api/catalog/character?search=aitor&locale=eu')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Aitor');

    $this->getJson('/api/catalog/character?search=aitor&locale=es')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('modo random respeta count (1..12, default 4) y exclude', function () {
    $characters = collect(range(1, 6))->map(
        fn (int $i) => makeCharacter(['name' => ['es' => "Personaje {$i}"], 'is_published' => true])
    );

    $this->getJson('/api/catalog/character?mode=random&count=3&locale=es')
        ->assertOk()
        ->assertJsonPath('key', 'character')
        ->assertJsonCount(3, 'data');

    // Sin count: 4. Pasado de rosca: tope 12 (aquí solo hay 6).
    $this->getJson('/api/catalog/character?mode=random&locale=es')
        ->assertOk()
        ->assertJsonCount(4, 'data');

    $excluded = $characters->first();
    $response = $this->getJson("/api/catalog/character?mode=random&count=12&exclude={$excluded->id}")
        ->assertOk()
        ->assertJsonCount(5, 'data');

    expect(collect($response->json('data'))->pluck('id'))->not->toContain($excluded->id);
});

it('el ítem lleva name y slug localizados y la preview (o null)', function () {
    $character = makeCharacter(['name' => ['es' => 'Arya'], 'is_published' => true]);

    // Sin PNG generado: preview null (el fallback visual es del front).
    $this->getJson('/api/catalog/character?locale=es')
        ->assertOk()
        ->assertJsonPath('data.0.id', $character->id)
        ->assertJsonPath('data.0.name', 'Arya')
        ->assertJsonPath('data.0.slug', 'arya')
        ->assertJsonPath('data.0.preview', null);

    // Con PNG registrado en la columna: URL pública del disco de previews.
    $character->preview_image = ['character' => ['es' => "previews/character/{$character->id}-es.png"]];
    $character->saveQuietly();

    $preview = $this->getJson('/api/catalog/character?locale=es')->assertOk()->json('data.0.preview');
    expect($preview)->toContain("previews/character/{$character->id}-es.png");
});
