<?php

use App\Models\House;

function makeHouse(array $overrides = []): House
{
    $house = new House;
    $house->setTranslations('name', $overrides['name'] ?? ['es' => 'Casa Stark', 'en' => 'House Stark']);
    $house->setTranslations('description', $overrides['description'] ?? ['es' => 'Se acerca el invierno']);
    $house->color = $overrides['color'] ?? '#888888';
    $house->is_published = $overrides['is_published'] ?? false;
    $house->save();

    return $house;
}

it('crea una casa con traducciones y slug traducible', function () {
    $this->actingAs(motorUser('admin'))->postJson('/api/admin/houses', [
        'name' => ['es' => 'Casa Tully', 'en' => 'House Tully'],
        'description' => ['es' => 'Familia, deber, honor'],
        'color' => '#3355aa',
    ])->assertCreated()
        ->assertJsonPath('data.name.es', 'Casa Tully')
        ->assertJsonPath('data.slug.es', 'casa-tully')
        ->assertJsonPath('data.slug.en', 'house-tully');
});

it('exige el nombre en el locale por defecto', function () {
    $this->actingAs(motorUser('admin'))->postJson('/api/admin/houses', [
        'name' => ['en' => 'House Tully'],
    ])->assertUnprocessable()->assertJsonValidationErrors('name.es');
});

it('permite vaciar una traducción al editar', function () {
    makeHouse(['description' => ['es' => 'Se acerca el invierno', 'en' => 'Winter is coming']]);

    $this->actingAs(motorUser('admin'))->putJson('/api/admin/houses/casa-stark', [
        'name' => ['es' => 'Casa Stark', 'en' => 'House Stark'],
        'description' => ['es' => 'Se acerca el invierno', 'en' => ''],
    ])->assertOk()
        ->assertJsonPath('data.description.es', 'Se acerca el invierno')
        ->assertJsonMissingPath('data.description.en');
});

it('resuelve una casa por su slug en cualquier locale', function () {
    makeHouse();
    $admin = motorUser('admin');

    $this->actingAs($admin)->getJson('/api/admin/houses/casa-stark')
        ->assertOk()->assertJsonPath('data.name.es', 'Casa Stark');

    $this->actingAs($admin)->getJson('/api/admin/houses/house-stark')
        ->assertOk()->assertJsonPath('data.name.es', 'Casa Stark');
});

it('filtra por búsqueda y por estado', function () {
    makeHouse(['name' => ['es' => 'Casa Stark'], 'is_published' => true]);
    makeHouse(['name' => ['es' => 'Casa Lannister']]);
    $admin = motorUser('admin');

    // La búsqueda es sobre el json del locale activo: se fija el locale.
    $this->actingAs($admin)->getJson('/api/admin/houses?search=stark&locale=es')
        ->assertOk()->assertJsonCount(1, 'data');

    $this->actingAs($admin)->getJson('/api/admin/houses?status=draft')
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name.es', 'Casa Lannister');
});

it('publica y despublica con el toggle', function () {
    makeHouse();

    $this->actingAs(motorUser('admin'))
        ->postJson('/api/admin/houses/casa-stark/toggle-published')
        ->assertOk()->assertJsonPath('data.is_published', true);
});

it('manda a la papelera, restaura y borra definitivamente', function () {
    $house = makeHouse();
    $admin = motorUser('admin');

    $this->actingAs($admin)->deleteJson('/api/admin/houses/casa-stark')->assertNoContent();
    expect(House::count())->toBe(0)
        ->and(House::onlyTrashed()->count())->toBe(1);

    $this->actingAs($admin)->postJson("/api/admin/houses/{$house->id}/restore")->assertOk();
    expect(House::count())->toBe(1);

    $this->actingAs($admin)->deleteJson('/api/admin/houses/casa-stark')->assertNoContent();
    $this->actingAs($admin)->deleteJson("/api/admin/houses/{$house->id}/force")->assertNoContent();
    expect(House::withTrashed()->count())->toBe(0);
});

it('la web pública solo ve casas publicadas', function () {
    makeHouse(['name' => ['es' => 'Casa Stark'], 'is_published' => true]);
    makeHouse(['name' => ['es' => 'Casa Lannister']]);

    $this->getJson('/api/houses')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/houses/casa-stark')->assertOk();
    $this->getJson('/api/houses/casa-lannister')->assertNotFound();
});

it('el CRUD de admin exige rol admin o editor', function () {
    makeHouse();

    $this->getJson('/api/admin/houses')->assertUnauthorized();
    $this->actingAs(motorUser('user'))->getJson('/api/admin/houses')->assertForbidden();
    $this->actingAs(motorUser('editor'))->getJson('/api/admin/houses')->assertOk();
});
