<?php

use Edc\Core\Site\SiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// Configuración de la web (doc 10): GET público con defaults + catálogo de
// fuentes, PUT de admin con validación, y caché invalidada al guardar.

it('sirve los ajustes públicos con defaults y catálogo de fuentes', function () {
    $response = $this->getJson('/api/site')->assertOk();

    expect($response->json('data.accent_mode'))->toBe('fixed')
        ->and($response->json('data.accent_color'))->toBe('#6c5ce7')
        ->and($response->json('data.font_headings'))->toBe('system')
        ->and($response->json('data.fonts'))->toHaveKey('serif')
        // Webfonts del juego (AppServiceProvider): con ficheros resueltos
        // hacia la ruta con CORS del API.
        ->and($response->json('data.fonts.inter.label'))->toBe('Inter')
        ->and($response->json('data.fonts.inter.files.0.src'))->toContain('/api/site/fonts/inter/')
        ->and($response->json('data.fonts.inter.files.1.style'))->toBe('italic');
});

it('sirve los ficheros de fuente por la ruta del api', function () {
    $this->get('/api/site/fonts/inter/InterVariable.woff2')->assertOk();
    $this->get('/api/site/fonts/../.env')->assertNotFound();
    $this->get('/api/site/fonts/no-existe.woff2')->assertNotFound();
});

it('sube una fuente propia y queda elegible', function () {
    $admin = motorUser('admin');

    $upload = $this->actingAs($admin)->post('/api/admin/settings/fonts', [
        'name' => 'Mi Gótica',
        'file' => UploadedFile::fake()->create('gotica.woff2', 10),
    ])->assertCreated();

    $font = $upload->json('data');
    expect($font['key'])->toBe('custom-mi-gotica');

    // Se persiste en custom_fonts y vale como fuente de títulos.
    $this->actingAs($admin)->putJson('/api/admin/settings/site', [
        'custom_fonts' => [$font],
        'font_headings' => $font['key'],
    ])->assertOk()->assertJsonPath('data.font_headings', $font['key']);

    $public = $this->getJson('/api/site')->assertOk();
    expect($public->json("data.fonts.{$font['key']}.label"))->toBe('Mi Gótica')
        ->and($public->json("data.fonts.{$font['key']}.files.0.src"))->toContain('/api/site/fonts/');

    // Y su fichero se sirve desde el disco.
    $this->get('/api/site/fonts/'.$font['file'])->assertOk();

    // Una extensión no admitida es 422.
    $this->actingAs($admin)->post('/api/admin/settings/fonts', [
        'name' => 'Mala',
        'file' => UploadedFile::fake()->create('mala.exe', 10),
    ])->assertUnprocessable();
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

it('el logo es traducible y los svg del disco viajan inlineados por idioma', function () {
    $admin = motorUser('admin');
    $disk = Storage::disk(config('motor.storage.disk', 'public'));
    $disk->put('content/logo-es.svg', '<svg><text>ES</text></svg>');
    $disk->put('content/logo-eu.svg', '<svg><text>EU</text></svg>');

    $this->actingAs($admin)->putJson('/api/admin/settings/site', [
        'logo' => [
            'es' => $disk->url('content/logo-es.svg'),
            'eu' => $disk->url('content/logo-eu.svg'),
        ],
    ])->assertOk();

    $public = $this->getJson('/api/site')->assertOk();
    expect($public->json('data.logo.es'))->toContain('logo-es.svg')
        ->and($public->json('data.logo.eu'))->toContain('logo-eu.svg')
        ->and($public->json('data.logo_inline.es'))->toContain('ES')
        ->and($public->json('data.logo_inline.eu'))->toContain('EU');
});

it('normaliza el logo del formato antiguo (string) al locale por defecto', function () {
    // Instalaciones que guardaron el logo como URL única (pre 0.3.0).
    app(SiteSettings::class)->update(['logo' => 'https://cdn.example/logo.png']);

    $public = $this->getJson('/api/site')->assertOk();
    expect($public->json('data.logo'))->toBe([config('motor.default_locale') => 'https://cdn.example/logo.png']);
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
