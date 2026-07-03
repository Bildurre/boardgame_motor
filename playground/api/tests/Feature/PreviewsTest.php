<?php

use App\Models\Character;
use App\Models\House;
use Bgm\Core\Previews\Jobs\GeneratePreviewJob;
use Bgm\Core\Previews\RenderToken;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['motor.previews.enabled' => true]);
    Storage::fake('public');
    // La cola de tests es síncrona: al crear una entidad, el job corre al
    // momento. Siempre con el renderer falso, nunca con Chromium.
    fakeRenderer();
});

it('encola un render por locale al crear la entidad', function () {
    Queue::fake();

    makeCharacter();

    Queue::assertPushed(GeneratePreviewJob::class, 3); // es, eu, en
});

it('regenera al cambiar un campo que dispara y no al publicar', function () {
    fakeRenderer();
    $character = makeCharacter();

    Queue::fake();

    $character->power = 9;
    $character->save();
    Queue::assertPushed(GeneratePreviewJob::class, 3);

    Queue::fake();
    $character->is_published = true;
    $character->save();
    Queue::assertNothingPushed();
});

it('no encola nada con las previews deshabilitadas', function () {
    config(['motor.previews.enabled' => false]);
    Queue::fake();

    makeCharacter();

    Queue::assertNothingPushed();
});

it('el job captura la URL /_render con token y guarda el PNG versionado', function () {
    $renderer = fakeRenderer();
    $character = makeCharacter();

    GeneratePreviewJob::dispatchSync(Character::class, $character->id, 'es');

    $character->refresh();
    expect($character->hasPreview('es'))->toBeTrue()
        ->and($character->previewUrl('es'))->toContain('previews/character/'.$character->id.'/es-');
    Storage::disk('public')->assertExists($character->previewPath('es'));

    $url = $renderer->captured[0]['url'];
    expect($url)->toContain('/_render/character/'.$character->id)
        ->and($url)->toContain('locale=es')
        ->and($url)->toContain('token=')
        ->and($renderer->captured[0]['width'])->toBe(315);
});

it('al regenerar borra el PNG anterior del locale', function () {
    fakeRenderer();
    $character = makeCharacter();

    GeneratePreviewJob::dispatchSync(Character::class, $character->id, 'es');
    $old = $character->refresh()->previewPath('es');

    GeneratePreviewJob::dispatchSync(Character::class, $character->id, 'es');
    $new = $character->refresh()->previewPath('es');

    expect($new)->not->toBe($old);
    Storage::disk('public')->assertMissing($old);
    Storage::disk('public')->assertExists($new);
});

it('el borrado definitivo elimina los PNG del disco', function () {
    fakeRenderer();
    $character = makeCharacter();
    GeneratePreviewJob::dispatchSync(Character::class, $character->id, 'es');
    $path = $character->refresh()->previewPath('es');

    $character->delete(); // papelera: los PNG se conservan
    Storage::disk('public')->assertExists($path);

    $character->forceDelete();
    Storage::disk('public')->assertMissing($path);
});

it('la ruta de datos de render exige un token válido', function () {
    $character = makeCharacter();

    $this->getJson("/api/render/character/{$character->id}")->assertForbidden();

    $this->getJson("/api/render/character/{$character->id}?token=falso")->assertForbidden();

    // Token de otra entidad: tampoco.
    $foreign = app(RenderToken::class)->issue('scheme', $character->id);
    $this->getJson("/api/render/character/{$character->id}?token={$foreign}")->assertForbidden();

    $token = app(RenderToken::class)->issue('character', $character->id);
    $this->getJson("/api/render/character/{$character->id}?token={$token}&locale=es")
        ->assertOk()
        ->assertJsonPath('entity', 'character')
        ->assertJsonPath('size.width', 315)
        ->assertJsonPath('data.name.es', 'Tyrion');
});

it('el admin consulta y regenera previews', function () {
    fakeRenderer();
    $character = makeCharacter();
    GeneratePreviewJob::dispatchSync(Character::class, $character->id, 'es');

    $admin = motorUser('admin');

    $this->actingAs($admin)->getJson("/api/admin/previews/character/{$character->id}")
        ->assertOk()
        ->assertJsonStructure(['data' => ['es']]);

    Queue::fake();
    $this->actingAs($admin)->postJson("/api/admin/previews/character/{$character->id}/regenerate")
        ->assertAccepted();
    Queue::assertPushed(GeneratePreviewJob::class, 3);

    // Y requiere rol de admin.
    $this->actingAs(motorUser('user'))
        ->postJson("/api/admin/previews/character/{$character->id}/regenerate")
        ->assertForbidden();
});

it('preview:manage genera en síncrono y limpia huérfanos', function () {
    fakeRenderer();
    $character = makeCharacter();

    $this->artisan('preview:manage', ['action' => 'generate', '--type' => 'character', '--sync' => true])
        ->assertSuccessful();

    expect($character->refresh()->previewUrls())->toHaveKeys(['es', 'eu', 'en']);

    // Huérfano: un fichero que ninguna entidad referencia.
    Storage::disk('public')->put('previews/character/999/es-abcd1234.png', 'x');

    $this->artisan('preview:manage', ['action' => 'clean', '--dry-run' => true])->assertSuccessful();
    Storage::disk('public')->assertExists('previews/character/999/es-abcd1234.png');

    $this->artisan('preview:manage', ['action' => 'clean'])->assertSuccessful();
    Storage::disk('public')->assertMissing('previews/character/999/es-abcd1234.png');

    // Los referenciados siguen.
    Storage::disk('public')->assertExists($character->previewPath('es'));
});

it('preview:manage generate solo rellena lo que falta', function () {
    $character = makeCharacter(); // la creación ya renderiza es/eu/en (cola síncrona)

    // Deja al personaje sin las previews de eu y en.
    $character->refresh();
    $character->preview_image = ['character' => ['es' => $character->previewPath('es')]];
    $character->saveQuietly();

    $renderer = fakeRenderer();
    $this->artisan('preview:manage', ['action' => 'generate', '--sync' => true])->assertSuccessful();

    // Solo eu y en (es ya estaba).
    expect(count($renderer->captured))->toBe(2)
        ->and($character->refresh()->previewUrls())->toHaveKeys(['es', 'eu', 'en']);
});

it('un modelo puede tener varias previews: clave por clave', function () {
    $renderer = fakeRenderer();

    $house = new House;
    $house->setTranslations('name', ['es' => 'Casa Stark']);
    $house->save(); // encola (cola síncrona) house y house-counter, 3 locales cada una

    $house->refresh();
    expect($house->hasPreview('es'))->toBeTrue()                       // por defecto: 'house'
        ->and($house->hasPreview('es', 'house-counter'))->toBeTrue()
        ->and($house->previewPath('es'))->toContain('previews/house/')
        ->and($house->previewPath('es', 'house-counter'))->toContain('previews/house-counter/');

    // Cada preview con su tamaño (token 200, contador 125).
    $widths = collect($renderer->captured)->pluck('width')->unique()->sort()->values()->all();
    expect($widths)->toBe([125, 200]);

    // El gestor las lista como tipos separados.
    $this->actingAs(motorUser('admin'))->getJson('/api/admin/previews')
        ->assertOk()
        ->assertJsonPath('data.2.key', 'house')
        ->assertJsonPath('data.2.complete', 1)
        ->assertJsonPath('data.3.key', 'house-counter')
        ->assertJsonPath('data.3.complete', 1);

    // Borrar una clave no toca la otra.
    $this->actingAs(motorUser('admin'))->deleteJson("/api/admin/previews/house-counter/{$house->id}")
        ->assertOk();
    $house->refresh();
    expect($house->hasPreview('es', 'house-counter'))->toBeFalse()
        ->and($house->hasPreview('es'))->toBeTrue();
});

// --- Gestor de previews del admin (lotes) ---

it('el gestor lista el estado por tipo', function () {
    makeCharacter();

    $this->actingAs(motorUser('admin'))->getJson('/api/admin/previews')
        ->assertOk()
        ->assertJsonPath('data.0.key', 'character')
        ->assertJsonPath('data.0.total', 1)
        ->assertJsonPath('data.0.complete', 1) // la creación renderizó (cola síncrona + fake)
        ->assertJsonPath('data.0.locales.es', 1) // generadas por idioma
        ->assertJsonPath('data.0.locales.eu', 1)
        ->assertJsonPath('data.1.key', 'scheme')
        ->assertJsonPath('data.1.locales.es', 0);
});

it('el selector del gestor busca por texto (?q)', function () {
    makeCharacter(); // Tyrion
    $otro = makeCharacter(['name' => ['es' => 'Cersei']]);

    $admin = motorUser('admin');

    $this->actingAs($admin)->getJson('/api/admin/previews/character/items?q=cersei')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $otro->id);

    $this->actingAs($admin)->getJson('/api/admin/previews/character/items?q=nadie')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('el gestor lista las entidades con su estado por locale', function () {
    $character = makeCharacter();

    $this->actingAs(motorUser('admin'))
        ->getJson('/api/admin/previews/character/items')
        ->assertOk()
        ->assertJsonPath('data.0.id', $character->id)
        ->assertJsonPath('data.0.label', 'Tyrion')
        ->assertJsonStructure(['data' => [['previews' => ['es', 'eu', 'en']]], 'meta'])
        ->assertJsonPath('meta.total', 1);
});

it('el gestor encola por lotes: pendientes y regenerar todo', function () {
    $character = makeCharacter();
    // Deja solo es generado.
    $character->refresh();
    $character->preview_image = ['character' => ['es' => $character->previewPath('es')]];
    $character->saveQuietly();

    $admin = motorUser('admin');

    Queue::fake();
    $this->actingAs($admin)->postJson('/api/admin/previews/character/generate')
        ->assertAccepted()->assertJsonPath('queued', 2); // eu + en
    Queue::assertPushed(GeneratePreviewJob::class, 2);

    Queue::fake();
    $this->actingAs($admin)->postJson('/api/admin/previews/character/regenerate')
        ->assertAccepted()->assertJsonPath('queued', 3);
    Queue::assertPushed(GeneratePreviewJob::class, 3);

    // Limitado a un locale (en el cuerpo).
    Queue::fake();
    $this->actingAs($admin)->postJson('/api/admin/previews/character/regenerate', ['locale' => 'eu'])
        ->assertAccepted()->assertJsonPath('queued', 1);

    // El ?locale de la query (locale de contenido del admin) NO limita.
    Queue::fake();
    $this->actingAs($admin)->postJson('/api/admin/previews/character/regenerate?locale=eu')
        ->assertAccepted()->assertJsonPath('queued', 3);
});

it('el gestor borra las previews de un tipo y de una entidad', function () {
    $character = makeCharacter();
    $path = $character->refresh()->previewPath('es');
    $admin = motorUser('admin');

    // Individual.
    $this->actingAs($admin)->deleteJson("/api/admin/previews/character/{$character->id}")
        ->assertOk();
    Storage::disk('public')->assertMissing($path);
    expect($character->refresh()->previewUrls())->toBe([]);

    // Por tipo.
    GeneratePreviewJob::dispatchSync(Character::class, $character->id, 'es');
    expect($character->refresh()->hasPreview('es'))->toBeTrue();

    $this->actingAs($admin)->deleteJson('/api/admin/previews/character')
        ->assertOk()->assertJsonPath('entities', 1);
    expect($character->refresh()->previewUrls())->toBe([]);
});

it('el gestor limpia huérfanos con y sin dry-run', function () {
    makeCharacter();
    Storage::disk('public')->put('previews/character/999/es-huerfano.png', 'x');
    $admin = motorUser('admin');

    $this->actingAs($admin)->postJson('/api/admin/previews/clean', ['dry_run' => true])
        ->assertOk()->assertJsonPath('dry_run', true)->assertJsonCount(1, 'orphans');
    Storage::disk('public')->assertExists('previews/character/999/es-huerfano.png');

    $this->actingAs($admin)->postJson('/api/admin/previews/clean')
        ->assertOk()->assertJsonCount(1, 'orphans');
    Storage::disk('public')->assertMissing('previews/character/999/es-huerfano.png');
});

it('el gestor exige acceso de admin', function () {
    $this->getJson('/api/admin/previews')->assertUnauthorized();
    $this->actingAs(motorUser('user'))->getJson('/api/admin/previews')->assertForbidden();
    $this->actingAs(motorUser('user'))->postJson('/api/admin/previews/clean')->assertForbidden();
});
