<?php

use App\Models\Character;
use Bgm\Core\Previews\Jobs\GeneratePreviewJob;
use Bgm\Core\Previews\PreviewRenderer;
use Bgm\Core\Previews\RenderToken;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/**
 * Renderer de mentira: no abre Chromium, escribe un PNG falso y registra las
 * URLs capturadas para poder asertar sobre ellas.
 */
class FakePreviewRenderer extends PreviewRenderer
{
    public array $captured = [];

    public function capture(string $url, int $width, int $height, string $savePath): void
    {
        $this->captured[] = compact('url', 'width', 'height');
        file_put_contents($savePath, 'fake-png');
    }
}

function fakeRenderer(): FakePreviewRenderer
{
    $fake = new FakePreviewRenderer;
    app()->instance(PreviewRenderer::class, $fake);

    return $fake;
}

function makeCharacter(array $overrides = []): Character
{
    $character = new Character;
    $character->setTranslations('name', ['es' => 'Tyrion']);
    $character->power = $overrides['power'] ?? 1;
    $character->prestige = 2;
    $character->intrigue = 3;
    $character->money = 4;
    $character->save();

    return $character;
}

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
        ->and($renderer->captured[0]['width'])->toBe(350);
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
        ->assertJsonPath('size.width', 350)
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
    $character->preview_image = ['es' => $character->previewPath('es')];
    $character->saveQuietly();

    $renderer = fakeRenderer();
    $this->artisan('preview:manage', ['action' => 'generate', '--sync' => true])->assertSuccessful();

    // Solo eu y en (es ya estaba).
    expect(count($renderer->captured))->toBe(2)
        ->and($character->refresh()->previewUrls())->toHaveKeys(['es', 'eu', 'en']);
});
