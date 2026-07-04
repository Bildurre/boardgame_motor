<?php

use Illuminate\Support\Facades\Storage;

// Copias de seguridad (doc 06, DC-16): crear, listar, descargar y borrar
// desde el admin; solo manage-web (los editores no entran).

beforeEach(function () {
    // Disco de copias falso y una fuente mínima (la BBDD de tests es sqlite
    // en memoria: no hay fichero que meter en el zip).
    Storage::fake('backups');
    config([
        'backup.backup.source.files.include' => [base_path('composer.json')],
        'backup.backup.source.databases' => [],
    ]);
});

it('el admin crea, lista, descarga y borra copias de seguridad', function () {
    $admin = motorUser('admin');

    // Crear (síncrono) devuelve el listado con la copia nueva.
    $created = $this->actingAs($admin)->postJson('/api/admin/backups')
        ->assertCreated();
    $file = $created->json('data.0.file');
    expect($file)->toEndWith('.zip')
        ->and($created->json('data.0.size'))->toBeGreaterThan(0);

    // Listar.
    $this->actingAs($admin)->getJson('/api/admin/backups')
        ->assertOk()
        ->assertJsonPath('data.0.file', $file);

    // Descargar.
    $this->actingAs($admin)->get("/api/admin/backups/{$file}/download")
        ->assertOk()
        ->assertDownload($file);

    // Borrar (y un nombre desconocido da 404).
    $this->actingAs($admin)->deleteJson("/api/admin/backups/{$file}")
        ->assertNoContent();
    $this->actingAs($admin)->getJson('/api/admin/backups')
        ->assertOk()
        ->assertJsonCount(0, 'data');
    $this->actingAs($admin)->deleteJson("/api/admin/backups/{$file}")
        ->assertNotFound();
});

it('las copias de seguridad son solo de manage-web', function () {
    $editor = motorUser('editor');

    $this->actingAs($editor)->getJson('/api/admin/backups')->assertForbidden();
    $this->actingAs($editor)->postJson('/api/admin/backups')->assertForbidden();
});
