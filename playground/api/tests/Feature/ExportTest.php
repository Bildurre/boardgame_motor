<?php

use App\Models\Character;
use App\Models\User;
use Edc\Core\Export\ExportableContract;
use Edc\Core\Export\ExportRegistry;
use Edc\Core\Support\Facades\Exports;

// Exportación a JSON (doc 12): modelos registrados como exportables, solo
// administradores; el JSON lleva los campos elegidos en el orden del
// catálogo y los textos por idioma (cadena con uno, mapa con varios).

function exportCharacter(array $names, int $power = 2): Character
{
    $character = new Character;
    $character->setTranslations('name', $names);
    $character->setTranslations('ability', ['es' => 'Golpea dos veces']);
    $character->power = $power;
    $character->prestige = 1;
    $character->is_published = true;
    $character->save();

    return $character;
}

it('solo registra modelos que implementan el contrato', function () {
    expect(Exports::has('characters'))->toBeTrue()
        ->and(Exports::modelFor('characters'))->toBe(Character::class)
        ->and(is_subclass_of(Character::class, ExportableContract::class))->toBeTrue();

    expect(fn () => app(ExportRegistry::class)->register('users', User::class))
        ->toThrow(InvalidArgumentException::class);
});

it('solo los administradores exportan', function () {
    $this->getJson('/api/admin/export/options')->assertUnauthorized();
    $this->actingAs(motorUser('editor'))->getJson('/api/admin/export/options')->assertForbidden();
    $this->actingAs(motorUser('editor'))
        ->postJson('/api/admin/export/characters', ['locales' => ['es'], 'fields' => ['name']])
        ->assertForbidden();

    $response = $this->actingAs(motorUser('admin'))->getJson('/api/admin/export/options')->assertOk();
    expect(array_column($response->json('data.models'), 'key'))->toBe(['characters'])
        ->and($response->json('data.models.0.fields.0'))->toBe(['key' => 'name', 'group' => 'basic'])
        ->and(array_column($response->json('data.locales'), 'code'))->toContain('es', 'en')
        ->and($response->json('data.default_locale'))->toBe('es');
});

it('exporta los campos elegidos en el orden del catálogo, por idioma', function () {
    $admin = motorUser('admin');
    exportCharacter(['es' => 'Bran', 'en' => 'Bran']);
    exportCharacter(['es' => 'Arya', 'en' => 'Arya'], 3);

    $response = $this->actingAs($admin)
        ->postJson('/api/admin/export/characters', ['locales' => ['es'], 'fields' => ['ability', 'name', 'stats']])
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="characters-'.now()->format('Y-m-d').'.json"');

    expect($response->json('model'))->toBe('characters')
        ->and($response->json('count'))->toBe(2)
        ->and($response->json('fields'))->toBe(['name', 'stats', 'ability'])
        ->and(array_keys($response->json('items.0')))->toBe(['name', 'stats', 'ability'])
        ->and($response->json('items.0.name'))->toBe('Arya') // orden por nombre
        ->and($response->json('items.0.stats.power'))->toBe(3)
        ->and($response->json('items.0.stats.cost'))->toBe(4)
        ->and($response->json('items.0.ability'))->toBe('Golpea dos veces');

    // Varios idiomas: mapa por idioma; sin traducción, null
    $multi = $this->actingAs($admin)
        ->postJson('/api/admin/export/characters', ['locales' => ['es', 'en'], 'fields' => ['name', 'ability']])
        ->assertOk();
    expect($multi->json('items.0.name'))->toBe(['es' => 'Arya', 'en' => 'Arya'])
        ->and($multi->json('items.0.ability'))->toBe(['es' => 'Golpea dos veces', 'en' => null]);
});

it('valida modelo, idiomas y campos', function () {
    $admin = motorUser('admin');

    $this->actingAs($admin)->postJson('/api/admin/export/houses', ['locales' => ['es'], 'fields' => ['name']])->assertNotFound();
    $this->actingAs($admin)
        ->postJson('/api/admin/export/characters', ['locales' => ['xx'], 'fields' => ['nope']])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['locales.0', 'fields.0']);
});
