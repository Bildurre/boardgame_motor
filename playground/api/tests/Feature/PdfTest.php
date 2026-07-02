<?php

use App\Models\House;
use App\Models\Scheme;
use Bgm\Core\Pdf\Jobs\GeneratePdfJob;
use Bgm\Core\Pdf\Models\GeneratedPdf;
use Bgm\Core\Pdf\Models\PdfCollectionItem;
use Bgm\Core\Pdf\PdfService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

function makeHouseWithSchemes(int $schemes = 2): House
{
    $house = new House;
    $house->setTranslations('name', ['es' => 'Casa Stark']);
    $house->is_published = true;
    $house->save();

    for ($i = 1; $i <= $schemes; $i++) {
        $scheme = new Scheme;
        $scheme->house_id = $house->id;
        $scheme->setTranslations('title', ['es' => "Argucia {$i}"]);
        $scheme->cost = $i;
        $scheme->is_published = true;
        $scheme->save();
    }

    return $house;
}

function pdfPageCount(string $raw): int
{
    return preg_match_all('#/Type\s*/Page\b(?!s)#', $raw);
}

beforeEach(function () {
    config(['motor.previews.enabled' => true]);
    Storage::fake('public');
    fakeRenderer();
});

it('genera un PDF real de la colección de una casa (DomPDF, marcas de corte)', function () {
    $house = makeHouseWithSchemes(2);

    $pdf = app(PdfService::class)->generate('house-schemes', $house, 'es', sync: true);

    $pdf->refresh();
    expect($pdf->status)->toBe(GeneratedPdf::STATUS_READY)
        ->and($pdf->url())->toContain('pdfs/house-schemes/'.$house->id.'/');

    $raw = Storage::disk('public')->get($pdf->path);
    expect(str_starts_with($raw, '%PDF'))->toBeTrue()
        ->and(pdfPageCount($raw))->toBe(1); // 2 cartas caben en una hoja (4/página)
});

it('genera las previews que falten al componer el PDF', function () {
    // Se crea la casa SIN previews (deshabilitadas): al componer el PDF, el
    // servicio tiene que generarlas en el momento.
    config(['motor.previews.enabled' => false]);
    $house = makeHouseWithSchemes(1);
    config(['motor.previews.enabled' => true]);

    $scheme = $house->schemes()->first();
    expect($scheme->hasPreview('es'))->toBeFalse();

    app(PdfService::class)->generate('house-schemes', $house, 'es', sync: true);

    expect($scheme->refresh()->hasPreview('es'))->toBeTrue();
});

it('regenerar reutiliza el registro y borra el fichero anterior', function () {
    $house = makeHouseWithSchemes(1);
    $service = app(PdfService::class);

    $first = $service->generate('house-schemes', $house, 'es', sync: true)->refresh();
    $oldPath = $first->path;

    $second = $service->generate('house-schemes', $house, 'es', sync: true)->refresh();

    expect($second->id)->toBe($first->id)
        ->and($second->path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($second->path);
});

it('expande copias y pagina según la capacidad del layout', function () {
    $character = makeCharacter(['is_published' => true]);

    // character-card = 1 carta x4 copias -> 1 página (capacidad card = 4).
    $pdf = app(PdfService::class)->generate('character-card', $character, 'es', sync: true)->refresh();
    expect(pdfPageCount(Storage::disk('public')->get($pdf->path)))->toBe(1);

    // Colección temporal con 10 huecos -> 3 páginas.
    $owner = motorUser();
    $pdf = app(PdfService::class)->generateCollection(
        $owner,
        [['entity' => 'character', 'id' => $character->id, 'copies' => 10]],
        'es',
        sync: true,
    )->refresh();

    expect(pdfPageCount(Storage::disk('public')->get($pdf->path)))->toBe(3)
        ->and($pdf->is_permanent)->toBeFalse()
        ->and($pdf->expires_at)->not->toBeNull();
});

it('marca el PDF como failed si no hay ítems', function () {
    $house = makeHouseWithSchemes(0);

    try {
        app(PdfService::class)->generate('house-schemes', $house, 'es', sync: true);
    } catch (RuntimeException) {
        // el job relanza para marcar el fallo en cola
    }

    expect(GeneratedPdf::first()->status)->toBe(GeneratedPdf::STATUS_FAILED);
});

// --- API de admin ---

it('el admin genera un PDF por export y entidad (todos los locales)', function () {
    Queue::fake();
    $house = makeHouseWithSchemes(1);
    $admin = motorUser('admin');

    $this->actingAs($admin)->postJson('/api/admin/pdfs/generate', [
        'type' => 'house-schemes',
        'source_id' => $house->id,
    ])->assertAccepted()->assertJsonCount(3, 'data');

    Queue::assertPushed(GeneratePdfJob::class, 3);

    // Listado por export + entidad.
    $this->actingAs($admin)
        ->getJson('/api/admin/pdfs?type=house-schemes&source_id='.$house->id)
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.status', 'pending');
});

it('el admin genera un export global y uno individual', function () {
    Queue::fake();
    makeCharacter(['is_published' => true]);
    $admin = motorUser('admin');

    $this->actingAs($admin)->postJson('/api/admin/pdfs/generate', [
        'type' => 'characters',
        'locale' => 'es',
    ])->assertAccepted()->assertJsonCount(1, 'data');

    // Individual sin source_id -> 422.
    $this->actingAs($admin)->postJson('/api/admin/pdfs/generate', [
        'type' => 'character-card',
        'locale' => 'es',
    ])->assertUnprocessable();
});

it('regenera, borra y descarga desde la API', function () {
    $house = makeHouseWithSchemes(1);
    $admin = motorUser('admin');

    $pdf = app(PdfService::class)->generate('house-schemes', $house, 'es', sync: true)->refresh();

    // Descarga pública (permanente y listo).
    $this->get("/api/pdfs/{$pdf->id}/download")->assertOk();

    // Regenerar lo deja pendiente (en cola).
    Queue::fake();
    $this->actingAs($admin)->postJson("/api/admin/pdfs/{$pdf->id}/regenerate")->assertAccepted();
    expect($pdf->refresh()->status)->toBe(GeneratedPdf::STATUS_PENDING);

    // Pendiente -> descarga 404.
    $this->get("/api/pdfs/{$pdf->id}/download")->assertNotFound();

    // Borrar elimina fichero y registro.
    $path = $pdf->path;
    $this->actingAs($admin)->deleteJson("/api/admin/pdfs/{$pdf->id}")->assertOk();
    Storage::disk('public')->assertMissing($path);
    expect(GeneratedPdf::count())->toBe(0);
});

it('la gestión de PDF exige admin', function () {
    $this->postJson('/api/admin/pdfs/generate', ['type' => 'characters'])->assertUnauthorized();
    $this->actingAs(motorUser('user'))
        ->postJson('/api/admin/pdfs/generate', ['type' => 'characters'])
        ->assertForbidden();
});

// --- Colección temporal del usuario ---

it('el usuario arma su colección y genera un PDF temporal', function () {
    $character = makeCharacter(['is_published' => true]);
    $user = motorUser();

    // Añadir (y actualizar copias con el mismo endpoint).
    $this->actingAs($user)->postJson('/api/pdf-collection/items', [
        'entity' => 'character', 'id' => $character->id, 'copies' => 2,
    ])->assertCreated();
    $this->actingAs($user)->postJson('/api/pdf-collection/items', [
        'entity' => 'character', 'id' => $character->id, 'copies' => 3,
    ])->assertCreated();

    $this->actingAs($user)->getJson('/api/pdf-collection')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.copies', 3)
        ->assertJsonPath('data.0.label', 'Tyrion');

    // Entidad no registrada -> 422.
    $this->actingAs($user)->postJson('/api/pdf-collection/items', [
        'entity' => 'nope', 'id' => 1,
    ])->assertUnprocessable();

    // Generar el PDF temporal.
    Queue::fake();
    $response = $this->actingAs($user)->postJson('/api/pdf-collection/generate')
        ->assertAccepted();
    Queue::assertPushed(GeneratePdfJob::class);

    $pdf = GeneratedPdf::findOrFail($response->json('data.id'));
    expect($pdf->owner_id)->toBe($user->id)
        ->and($pdf->is_permanent)->toBeFalse()
        ->and($pdf->payload)->toBe([['entity' => 'character', 'id' => $character->id, 'copies' => 3]]);

    // Vaciar.
    $this->actingAs($user)->deleteJson('/api/pdf-collection')->assertOk();
    expect(PdfCollectionItem::count())->toBe(0);
    $this->actingAs($user)->postJson('/api/pdf-collection/generate')->assertUnprocessable();
});

it('el PDF temporal solo lo descarga su dueño (o un admin)', function () {
    $character = makeCharacter(['is_published' => true]);
    $owner = motorUser();

    $pdf = app(PdfService::class)->generateCollection(
        $owner,
        [['entity' => 'character', 'id' => $character->id, 'copies' => 1]],
        'es',
        sync: true,
    )->refresh();

    $this->get("/api/pdfs/{$pdf->id}/download")->assertForbidden();
    $this->actingAs(motorUser())->get("/api/pdfs/{$pdf->id}/download")->assertForbidden();
    $this->actingAs($owner)->get("/api/pdfs/{$pdf->id}/download")->assertOk();
    $this->actingAs(motorUser('admin'))->get("/api/pdfs/{$pdf->id}/download")->assertOk();
});

it('pdf:cleanup borra los temporales caducados', function () {
    $character = makeCharacter(['is_published' => true]);
    $owner = motorUser();
    $service = app(PdfService::class);

    $expired = $service->generateCollection(
        $owner,
        [['entity' => 'character', 'id' => $character->id, 'copies' => 1]],
        'es',
        sync: true,
    )->refresh();
    $expired->update(['expires_at' => now()->subHour()]);
    $path = $expired->path;

    $alive = $service->generateCollection(
        $owner,
        [['entity' => 'character', 'id' => $character->id, 'copies' => 1]],
        'es',
        sync: true,
    )->refresh();

    $this->artisan('pdf:cleanup')->assertSuccessful();

    Storage::disk('public')->assertMissing($path);
    Storage::disk('public')->assertExists($alive->path);
    expect(GeneratedPdf::count())->toBe(1);
});
