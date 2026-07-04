<?php

// Configuración de la web (doc 10): GET público con defaults + catálogo de
// fuentes, PUT de admin con validación, y caché invalidada al guardar.

it('sirve los ajustes públicos con defaults y catálogo de fuentes', function () {
    $response = $this->getJson('/api/site')->assertOk();

    expect($response->json('data.accent_mode'))->toBe('fixed')
        ->and($response->json('data.accent_color'))->toBe('#6c5ce7')
        ->and($response->json('data.font_headings'))->toBe('system')
        ->and($response->json('data.fonts'))->toHaveKey('serif');
});

it('guarda la configuración desde el admin y el público la refleja', function () {
    $admin = motorUser('admin');

    $this->actingAs($admin)->putJson('/api/admin/settings/site', [
        'title' => ['es' => 'Choque de Leyendas', 'en' => 'Clash of Legends'],
        'accent_mode' => 'random',
        'accent_colors' => ['#29ab5f', '#f15959', '#408cfd'],
        'font_headings' => 'serif',
        'footer_text' => ['es' => 'Un juego de mesa imprimible.'],
    ])->assertOk()->assertJsonPath('data.accent_mode', 'random');

    $public = $this->getJson('/api/site')->assertOk();
    expect($public->json('data.title.es'))->toBe('Choque de Leyendas')
        ->and($public->json('data.accent_colors'))->toBe(['#29ab5f', '#f15959', '#408cfd'])
        ->and($public->json('data.font_headings'))->toBe('serif')
        // Lo no tocado conserva su default.
        ->and($public->json('data.accent_color'))->toBe('#6c5ce7');
});

it('valida colores, modo y fuentes', function () {
    $admin = motorUser('admin');

    $this->actingAs($admin)->putJson('/api/admin/settings/site', ['accent_color' => 'rojo'])
        ->assertUnprocessable();
    $this->actingAs($admin)->putJson('/api/admin/settings/site', ['accent_colors' => ['#123']])
        ->assertUnprocessable();
    $this->actingAs($admin)->putJson('/api/admin/settings/site', ['accent_mode' => 'disco'])
        ->assertUnprocessable();
    $this->actingAs($admin)->putJson('/api/admin/settings/site', ['font_body' => 'comic-sans'])
        ->assertUnprocessable();
});

it('la configuración exige admin', function () {
    $this->putJson('/api/admin/settings/site', [])->assertUnauthorized();
    $this->actingAs(motorUser('user'))->putJson('/api/admin/settings/site', [])->assertForbidden();
});
