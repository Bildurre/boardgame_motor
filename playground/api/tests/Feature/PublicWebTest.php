<?php

use App\Models\Character;
use Edc\Core\Content\Models\Page;

// Web pública (doc 10): lectura pública de entidades por slug en cualquier
// locale (con los slugs por locale para la canónica, DC-12) y sitemap con
// alternates hreflang (DC-18).

it('sirve los personajes publicados en público, por slug en cualquier locale', function () {
    $published = Character::create([
        'name' => ['es' => 'Arya', 'eu' => 'Arya'],
        'slug' => ['es' => 'arya', 'eu' => 'arya-eu'],
        'power' => 1, 'prestige' => 1, 'intrigue' => 1, 'money' => 1,
        'is_published' => true,
    ]);
    Character::create([
        'name' => ['es' => 'Borrador'],
        'power' => 1, 'prestige' => 1, 'intrigue' => 1, 'money' => 1,
        'is_published' => false,
    ]);

    // El índice solo trae publicados, con el mapa de slugs por locale.
    $this->getJson('/api/characters')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug.es', 'arya');

    // El detalle resuelve el slug de OTRO locale (la SPA redirige a la canónica).
    $this->getJson('/api/characters/arya-eu?locale=es')
        ->assertOk()
        ->assertJsonPath('data.id', $published->id)
        ->assertJsonPath('data.slug.es', 'arya');

    // Un borrador no se sirve.
    $this->getJson('/api/characters/borrador')->assertNotFound();
});

it('el sitemap trae páginas y entidades con alternates por locale', function () {
    Page::create([
        'title' => ['es' => 'Reglas', 'eu' => 'Arauak'],
        'slug' => ['es' => 'reglas', 'eu' => 'arauak'],
        'is_published' => true,
    ]);
    Character::create([
        'name' => ['es' => 'Arya'],
        'slug' => ['es' => 'arya'],
        'power' => 1, 'prestige' => 1, 'intrigue' => 1, 'money' => 1,
        'is_published' => true,
    ]);
    Character::create([
        'name' => ['es' => 'Borrador'],
        'slug' => ['es' => 'borrador'],
        'power' => 1, 'prestige' => 1, 'intrigue' => 1, 'money' => 1,
        'is_published' => false,
    ]);

    $base = rtrim(config('motor.frontend.app_url'), '/');

    $xml = $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->getContent();

    expect($xml)
        ->toContain("<loc>{$base}/es/reglas</loc>")
        ->toContain("hreflang=\"eu\" href=\"{$base}/eu/arauak\"")
        ->toContain("<loc>{$base}/es/personajes</loc>")
        ->toContain("<loc>{$base}/es/personajes/arya</loc>")
        ->not->toContain('borrador');
});
