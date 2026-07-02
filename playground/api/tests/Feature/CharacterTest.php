<?php

use App\Models\Character;

it('calcula el coste como suma de las estadísticas y la defensa igual al coste', function () {
    $response = $this->actingAs(motorUser('admin'))->postJson('/api/admin/characters', [
        'name' => ['es' => 'Tyrion'],
        'power' => 1,
        'prestige' => 2,
        'intrigue' => 4,
        'money' => 3,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.cost', 10)
        ->assertJsonPath('data.defense', 10);
});

it('recalcula el coste al editar las estadísticas', function () {
    $this->actingAs(motorUser('admin'))->postJson('/api/admin/characters', [
        'name' => ['es' => 'Tyrion'],
        'power' => 1, 'prestige' => 1, 'intrigue' => 1, 'money' => 1,
    ]);

    $character = Character::first();

    $this->actingAs(motorUser('admin'))->putJson("/api/admin/characters/{$character->slug}", [
        'name' => ['es' => 'Tyrion'],
        'power' => 5, 'prestige' => 0, 'intrigue' => 0, 'money' => 0,
    ])->assertOk()->assertJsonPath('data.cost', 5);
});

it('no acepta estadísticas negativas', function () {
    $this->actingAs(motorUser('admin'))->postJson('/api/admin/characters', [
        'name' => ['es' => 'Tyrion'],
        'power' => -1, 'prestige' => 0, 'intrigue' => 0, 'money' => 0,
    ])->assertUnprocessable()->assertJsonValidationErrors('power');
});
