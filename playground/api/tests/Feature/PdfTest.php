<?php

use App\Models\House;
use App\Models\Scheme;
use Bgm\Core\Content\Models\Page;
use Bgm\Core\Pdf\Jobs\GeneratePdfJob;
use Bgm\Core\Pdf\Models\GeneratedPdf;
use Bgm\Core\Pdf\Models\PdfCollectionItem;
use Bgm\Core\Pdf\PdfExport;
use Bgm\Core\Pdf\PdfService;
use Bgm\Core\Support\Facades\Pdfs;
use Illuminate\Database\Eloquent\Model;
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

    // Layout 'card' (Magic 63x88): 9 por A4 -> 10 huecos = 2 páginas.
    $owner = motorUser();
    $pdf = app(PdfService::class)->generateCollection(
        $owner,
        [['entity' => 'character', 'id' => $character->id, 'copies' => 10]],
        'es',
        sync: true,
    )->refresh();

    expect(pdfPageCount(Storage::disk('public')->get($pdf->path)))->toBe(2)
        ->and($pdf->is_permanent)->toBeFalse()
        ->and($pdf->expires_at)->not->toBeNull();
});

it('imprime otro tipo de pieza: 9 tokens de 40 mm por casa (layout token-40)', function () {
    makeHouseWithSchemes(0);
    makeHouseWithSchemes(0);
    makeHouseWithSchemes(0);

    $pdf = app(PdfService::class)->generate('house-tokens', null, 'es', sync: true)->refresh();

    // 3 casas x 9 tokens = 27 huecos; token-40 mete 24 por A4 -> 2 páginas.
    expect($pdf->status)->toBe(GeneratedPdf::STATUS_READY)
        ->and($pdf->url())->toContain('pdfs/house-tokens/global/')
        ->and(pdfPageCount(Storage::disk('public')->get($pdf->path)))->toBe(2);
});

it('cada export imprime a su tamaño: personajes al doble (card-big)', function () {
    makeCharacter(['is_published' => true]);
    makeCharacter(['is_published' => true]);
    makeCharacter(['is_published' => true]);

    $pdf = app(PdfService::class)->generate('characters', null, 'es', sync: true)->refresh();

    // card-big (126x176, A4 apaisado): 2 por página -> 3 cartas = 2 páginas.
    expect(pdfPageCount(Storage::disk('public')->get($pdf->path)))->toBe(2);
});

it('el export elige qué preview imprime (house-counters usa house-counter)', function () {
    $house = makeHouseWithSchemes(0);

    $pdf = app(PdfService::class)->generate('house-counters', null, 'es', sync: true)->refresh();

    // Se generó (o reutilizó) la preview 'house-counter', no la por defecto.
    expect($pdf->status)->toBe(GeneratedPdf::STATUS_READY)
        ->and($house->refresh()->hasPreview('es', 'house-counter'))->toBeTrue();
});

it('generar ignora el ?locale de la query (locale de contenido del admin)', function () {
    Queue::fake();
    makeCharacter(['is_published' => true]);
    $admin = motorUser('admin');

    // El admin añade ?locale=es a TODAS sus peticiones: aun así se generan
    // los 3 idiomas. Solo un locale en el CUERPO limita.
    $this->actingAs($admin)->postJson('/api/admin/pdfs/generate?locale=es', [
        'type' => 'characters',
    ])->assertAccepted()->assertJsonCount(3, 'data');

    $this->actingAs($admin)->postJson('/api/admin/pdfs/generate?locale=es', [
        'type' => 'characters', 'locale' => 'eu',
    ])->assertAccepted()->assertJsonCount(1, 'data');
});

it('los errores inesperados no se filtran al frontend (mensaje genérico)', function () {
    Pdfs::register('roto', RotoExport::class);

    try {
        app(PdfService::class)->generate('roto', null, 'es', sync: true);
    } catch (LogicException) {
        // el job relanza (el detalle va a los logs)
    }

    expect(GeneratedPdf::first()->error)->toBe(__('motor::motor.pdf_error_internal'))
        ->and(GeneratedPdf::first()->error)->not->toContain('SQLSTATE');
});

it('genera el PDF de una página imprimible del CRM (vista propia, sin rejilla)', function () {
    $admin = motorUser('admin');
    $pageId = $this->actingAs($admin)->postJson('/api/admin/pages', [
        'title' => ['es' => 'Reglamento'], 'is_published' => true, 'is_printable' => true,
    ])->json('data.id');
    $this->actingAs($admin)->postJson("/api/admin/pages/{$pageId}/blocks", [
        'type' => 'text', 'settings' => ['title' => ['es' => 'Preparación'], 'body' => ['es' => '<p>Baraja y reparte <strong>5 cartas</strong>.</p>']],
    ]);
    $this->actingAs($admin)->postJson("/api/admin/pages/{$pageId}/blocks", [
        'type' => 'text', 'settings' => ['body' => ['es' => '<p>Secreto</p>']], 'is_printable' => false,
    ]);

    $page = Page::find($pageId);
    $pdf = app(PdfService::class)->generate('pages', $page, 'es', sync: true)->refresh();

    expect($pdf->status)->toBe(GeneratedPdf::STATUS_READY);
    $raw = Storage::disk('public')->get($pdf->path);
    expect(str_starts_with($raw, '%PDF'))->toBeTrue();

    // El catálogo lista la página imprimible como fuente.
    $this->actingAs($admin)->getJson('/api/admin/pdfs/exports')
        ->assertJsonPath('data.0.sources.0.label', 'Reglamento');
});

it('marca el PDF como failed si no hay ítems', function () {
    $house = makeHouseWithSchemes(0);

    try {
        app(PdfService::class)->generate('house-schemes', $house, 'es', sync: true);
    } catch (RuntimeException) {
        // el job relanza para marcar el fallo en cola
    }

    expect(GeneratedPdf::first()->status)->toBe(GeneratedPdf::STATUS_FAILED)
        ->and(GeneratedPdf::first()->error)->toBe(__('motor::motor.pdf_no_items'));
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

it('el admin genera los exports globales (personajes y argucias)', function () {
    Queue::fake();
    makeCharacter(['is_published' => true]);
    makeHouseWithSchemes(1);
    $admin = motorUser('admin');

    $this->actingAs($admin)->postJson('/api/admin/pdfs/generate', [
        'type' => 'characters',
        'locale' => 'es',
    ])->assertAccepted()->assertJsonCount(1, 'data');

    $this->actingAs($admin)->postJson('/api/admin/pdfs/generate', [
        'type' => 'schemes',
        'locale' => 'es',
    ])->assertAccepted()->assertJsonCount(1, 'data');

    // Un export por entidad sin source_id -> 422.
    $this->actingAs($admin)->postJson('/api/admin/pdfs/generate', [
        'type' => 'house-schemes',
        'locale' => 'es',
    ])->assertUnprocessable();
});

it('el catálogo de exports lista los tipos con sus entidades dueñas', function () {
    $house = makeHouseWithSchemes(1);

    $this->actingAs(motorUser('admin'))->getJson('/api/admin/pdfs/exports')
        ->assertOk()
        ->assertJsonCount(6, 'data')
        ->assertJsonPath('data.0.type', 'pages') // lo registra el motor (CRM)
        ->assertJsonPath('data.1.type', 'characters')
        ->assertJsonPath('data.1.global', true)
        ->assertJsonPath('data.1.layout', 'card-big')
        ->assertJsonPath('data.1.sources', [])
        ->assertJsonPath('data.3.type', 'house-schemes')
        ->assertJsonPath('data.3.global', false)
        ->assertJsonPath('data.3.sources.0.id', $house->id)
        ->assertJsonPath('data.3.sources.0.label', 'Casa Stark')
        ->assertJsonPath('data.4.type', 'house-tokens')
        ->assertJsonPath('data.4.global', true)
        ->assertJsonPath('data.4.layout', 'token-40')
        ->assertJsonPath('data.5.type', 'house-counters')
        ->assertJsonPath('data.5.layout', 'counter');
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

// --- Acciones "de todas" del export (espejo de las previews) ---

it('el catálogo trae estadísticas por idioma (total y listos)', function () {
    $house = makeHouseWithSchemes(1);
    app(PdfService::class)->generate('house-schemes', $house, 'es', sync: true);

    $response = $this->actingAs(motorUser('admin'))->getJson('/api/admin/pdfs/exports')->assertOk();
    $stats = collect($response->json('data'))->firstWhere('type', 'house-schemes')['stats'];

    expect($stats['total'])->toBe(1)
        ->and($stats['locales'])->toBe(['es' => 1, 'eu' => 0, 'en' => 0]);
});

it('generar faltantes encola solo los combos sin PDF o fallidos', function () {
    $house = makeHouseWithSchemes(1);
    $admin = motorUser('admin');
    app(PdfService::class)->generate('house-schemes', $house, 'es', sync: true);

    Queue::fake();
    $this->actingAs($admin)->postJson('/api/admin/pdfs/generate-missing', [
        'type' => 'house-schemes',
    ])->assertAccepted()->assertJsonPath('queued', 2); // faltaban eu y en

    // Un fallido también cuenta como faltante.
    GeneratedPdf::where('locale', 'es')->update(['status' => GeneratedPdf::STATUS_FAILED]);
    $this->actingAs($admin)->postJson('/api/admin/pdfs/generate-missing', [
        'type' => 'house-schemes',
    ])->assertAccepted()->assertJsonPath('queued', 1);
});

it('regenerar todo encola todos los combos y borrar todo vacía el export', function () {
    $house = makeHouseWithSchemes(1);
    $admin = motorUser('admin');
    $pdf = app(PdfService::class)->generate('house-schemes', $house, 'es', sync: true)->refresh();
    $path = $pdf->path;

    Queue::fake();
    $this->actingAs($admin)->postJson('/api/admin/pdfs/regenerate-all', [
        'type' => 'house-schemes',
    ])->assertAccepted()->assertJsonPath('queued', 3); // 1 casa x 3 idiomas

    $this->actingAs($admin)->deleteJson('/api/admin/pdfs?type=house-schemes')->assertOk();
    Storage::disk('public')->assertMissing($path);
    expect(GeneratedPdf::where('type', 'house-schemes')->count())->toBe(0);
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

// --- Colección de INVITADO (token en X-Collection-Token, como en CDL) ---

it('un invitado arma su colección con token y genera su PDF temporal', function () {
    $character = makeCharacter(['is_published' => true]);
    $token = 'guest-0123456789abcdef';
    $headers = ['X-Collection-Token' => $token];

    // Sin sesión ni token -> 401.
    $this->postJson('/api/pdf-collection/items', [
        'entity' => 'character', 'id' => $character->id,
    ])->assertUnauthorized();

    // Con token: añadir, listar, generar.
    $this->postJson('/api/pdf-collection/items', [
        'entity' => 'character', 'id' => $character->id, 'copies' => 2,
    ], $headers)->assertCreated();
    $this->getJson('/api/pdf-collection', $headers)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    // Otro token no ve la colección.
    $this->getJson('/api/pdf-collection', ['X-Collection-Token' => 'otro-9876543210fedcba'])
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $pdf = null;
    Queue::fake();
    $response = $this->postJson('/api/pdf-collection/generate', [], $headers)->assertAccepted();
    $pdf = GeneratedPdf::findOrFail($response->json('data.id'));
    expect($pdf->owner_id)->toBeNull()
        ->and($pdf->guest_token)->toBe($token);

    // El sondeo del estado también va por token.
    $this->getJson("/api/pdf-collection/pdfs/{$pdf->id}", $headers)->assertOk();
    $this->getJson("/api/pdf-collection/pdfs/{$pdf->id}")->assertUnauthorized();
});

it('al autenticarse se adopta la colección del invitado (items y PDF a la cuenta)', function () {
    $character = makeCharacter(['is_published' => true]);
    $token = 'guest-0123456789abcdef';
    $headers = ['X-Collection-Token' => $token];

    // Como invitado: 2 copias + un PDF temporal generado.
    $this->postJson('/api/pdf-collection/items', [
        'entity' => 'character', 'id' => $character->id, 'copies' => 2,
    ], $headers)->assertCreated();
    Queue::fake();
    $pdfId = $this->postJson('/api/pdf-collection/generate', [], $headers)
        ->assertAccepted()
        ->json('data.id');

    // Al loguearse, la SPA sigue mandando el token: todo pasa a la cuenta.
    $user = motorUser();
    $this->actingAs($user)->getJson('/api/pdf-collection', $headers)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.copies', 2);

    $item = PdfCollectionItem::sole();
    expect($item->user_id)->toBe($user->id)
        ->and($item->guest_token)->toBeNull()
        ->and(GeneratedPdf::find($pdfId)->owner_id)->toBe($user->id)
        ->and(GeneratedPdf::find($pdfId)->guest_token)->toBeNull();
});

it('el PDF temporal de un invitado solo se descarga con su token', function () {
    $character = makeCharacter(['is_published' => true]);
    $token = 'guest-0123456789abcdef';

    $pdf = app(PdfService::class)->generateCollection(
        null,
        [['entity' => 'character', 'id' => $character->id, 'copies' => 1]],
        'es',
        sync: true,
        guestToken: $token,
    )->refresh();

    $this->get("/api/pdfs/{$pdf->id}/download")->assertForbidden();
    $this->get("/api/pdfs/{$pdf->id}/download", ['X-Collection-Token' => 'otro-9876543210fedcba'])
        ->assertForbidden();
    $this->get("/api/pdfs/{$pdf->id}/download", ['X-Collection-Token' => $token])->assertOk();
    $this->actingAs(motorUser('admin'))->get("/api/pdfs/{$pdf->id}/download")->assertOk();
});

// --- Apartado público de Descargas (permanentes, sin auth) ---

it('las descargas públicas listan los PDF permanentes listos, agrupados por tipo', function () {
    $character = makeCharacter(['is_published' => true]);
    $service = app(PdfService::class);

    // Un permanente listo, y un temporal que NO debe salir.
    $service->generate('characters', null, 'es', sync: true);
    $service->generateCollection(
        motorUser(),
        [['entity' => 'character', 'id' => $character->id, 'copies' => 1]],
        'es',
        sync: true,
    );

    $response = $this->getJson('/api/downloads')->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.type'))->toBe('characters')
        ->and($response->json('data.0.items.0.url'))->toContain('/download')
        ->and($response->json('data.0.items.0.size'))->toBeGreaterThan(0);
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

/** Export deliberadamente roto para el test de errores saneados. */
class RotoExport extends PdfExport
{
    public function sourceModel(): ?string
    {
        return null;
    }

    public function items(?Model $source, string $locale): array
    {
        throw new LogicException('SQLSTATE[42S22]: detalle interno que NO debe verse');
    }
}
