<?php

use App\Models\Character;

// Cobertura del scopeFilter del motor (HasFilters): búsqueda multi-campo
// sobre el json del locale activo, agrupada con el resto de filtros.

function filterCharacter(array $name, array $ability = [], bool $published = true): Character
{
    $character = new Character;
    $character->setTranslations('name', $name);
    if ($ability !== []) {
        $character->setTranslations('ability', $ability);
    }
    $character->power = 1;
    $character->prestige = 1;
    $character->intrigue = 1;
    $character->money = 1;
    $character->is_published = $published;
    $character->save();

    return $character;
}

it('busca en todos los campos de $searchable, no solo en el primero', function () {
    filterCharacter(['es' => 'Espadachín'], ['es' => 'Duplica la intriga']);
    filterCharacter(['es' => 'Arquera']);

    $this->actingAs(motorUser('admin'))
        ->getJson('/api/admin/characters?search=intriga&locale=es')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name.es', 'Espadachín');
});

it('busca solo sobre el json del locale activo', function () {
    filterCharacter(['es' => 'Espadachín', 'en' => 'Swordsman']);

    $admin = motorUser('admin');

    // En es no aparece por su nombre en inglés (antes el LIKE mezclaba locales)…
    $this->actingAs($admin)
        ->getJson('/api/admin/characters?search=Swords&locale=es')
        ->assertOk()
        ->assertJsonCount(0, 'data');

    // …y en en sí.
    $this->actingAs($admin)
        ->getJson('/api/admin/characters?search=Swords&locale=en')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('la búsqueda va agrupada y no rompe el filtro de estado', function () {
    filterCharacter(['es' => 'Espadachín'], published: true);
    filterCharacter(['es' => 'Espada rota'], published: false);

    // Sin el where agrupado, el orWhere del search colaría la publicada.
    $this->actingAs(motorUser('admin'))
        ->getJson('/api/admin/characters?search=Espada&status=draft&locale=es')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name.es', 'Espada rota');
});
