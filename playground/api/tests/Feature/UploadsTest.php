<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// Subidas de imagen del CRM/configuración: nombre original saneado, sufijo
// solo en colisiones, borrado del fichero sustituido y endpoint de borrado
// acotado a content/ (nada de traversal ni de tocar las fuentes).

beforeEach(fn () => Storage::fake(config('motor.storage.disk', 'public')));

function subir(object $test, string $nombre, ?string $replaces = null)
{
    $payload = ['image' => UploadedFile::fake()->image($nombre)];
    if ($replaces !== null) {
        $payload['replaces'] = $replaces;
    }

    return $test->actingAs(motorUser('admin'))->post('/api/admin/content/uploads', $payload);
}

it('guarda con el nombre original saneado y sufija solo si colisiona', function () {
    $disk = Storage::disk(config('motor.storage.disk', 'public'));

    $primera = subir($this, 'Mi Logo (v2).PNG')->assertCreated();
    expect($primera->json('path'))->toBe('content/mi-logo-v2.png')
        ->and($disk->exists('content/mi-logo-v2.png'))->toBeTrue();

    // Mismo nombre sin sustituir: no pisa, sufija.
    $segunda = subir($this, 'Mi Logo (v2).PNG')->assertCreated();
    expect($segunda->json('path'))->toBe('content/mi-logo-v2-2.png');
});

it('borra el fichero sustituido al subir con replaces', function () {
    $disk = Storage::disk(config('motor.storage.disk', 'public'));

    $vieja = subir($this, 'fondo.png')->assertCreated();
    expect($disk->exists('content/fondo.png'))->toBeTrue();

    subir($this, 'fondo.png', $vieja->json('url'))->assertCreated();

    // El nombre quedó libre: sin sufijo, y solo hay un fichero.
    expect($disk->exists('content/fondo.png'))->toBeTrue()
        ->and($disk->exists('content/fondo-2.png'))->toBeFalse();
});

it('el endpoint de borrado elimina la subida y exige manage-web', function () {
    $disk = Storage::disk(config('motor.storage.disk', 'public'));
    $subida = subir($this, 'quitar.png')->assertCreated();

    $this->actingAs(motorUser('user'))
        ->deleteJson('/api/admin/content/uploads', ['url' => $subida->json('url')])
        ->assertForbidden();

    $this->actingAs(motorUser('admin'))
        ->deleteJson('/api/admin/content/uploads', ['url' => $subida->json('url')])
        ->assertOk();
    expect($disk->exists('content/quitar.png'))->toBeFalse();
});

it('ignora borrados fuera de content/ (traversal y fuentes a salvo)', function () {
    $disk = Storage::disk(config('motor.storage.disk', 'public'));
    $disk->put('fonts/protegida.woff2', 'x');

    $this->actingAs(motorUser('admin'))
        ->deleteJson('/api/admin/content/uploads', ['url' => '/storage/fonts/protegida.woff2'])
        ->assertOk();
    $this->actingAs(motorUser('admin'))
        ->deleteJson('/api/admin/content/uploads', ['url' => '/storage/content/../fonts/protegida.woff2'])
        ->assertOk();

    expect($disk->exists('fonts/protegida.woff2'))->toBeTrue();
});
