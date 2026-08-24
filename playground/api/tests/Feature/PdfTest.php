<?php

use App\Models\House;
use App\Models\Scheme;
use Edc\Core\Content\BlockTypeRegistry;
use Edc\Core\Content\Models\Page;
use Edc\Core\Pdf\Jobs\GeneratePdfJob;
use Edc\Core\Pdf\Models\GeneratedPdf;
use Edc\Core\Pdf\Models\PdfCollectionItem;
use Edc\Core\Pdf\PdfExport;
use Edc\Core\Pdf\PdfExportRegistry;
use Edc\Core\Pdf\PdfPageAssets;
use Edc\Core\Pdf\PdfService;
use Edc\Core\Support\Facades\Pdfs;
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

it('un bloque con pdfView se imprime con su vista propia (characters-grid)', function () {
    makeCharacter(['is_published' => true]);
    $admin = motorUser('admin');
    $pageId = $this->actingAs($admin)->postJson('/api/admin/pages', [
        'title' => ['es' => 'Elenco'], 'is_published' => true, 'is_printable' => true,
    ])->json('data.id');
    $this->actingAs($admin)->postJson("/api/admin/pages/{$pageId}/blocks", [
        'type' => 'characters-grid', 'settings' => ['title' => ['es' => 'Personajes']],
    ]);

    // El PDF real se compone sin errores con la vista del bloque dentro.
    $page = Page::find($pageId);
    $pdf = app(PdfService::class)->generate('pages', $page, 'es', sync: true)->refresh();
    expect($pdf->status)->toBe(GeneratedPdf::STATUS_READY);

    // La vista declarada imprime la tabla de personajes (no la rejilla web).
    $type = app(BlockTypeRegistry::class)->get('characters-grid');
    expect($type->pdfView())->toBe('pdf.blocks.characters-grid');

    $block = $page->blocks()->first();
    $html = view($type->pdfView(), [
        'block' => $block,
        's' => $type->localizeSettings($block->settings, 'es'),
        'data' => $type->resolveData($block, 'es'),
        'locale' => 'es',
        'assets' => app(PdfPageAssets::class),
        'hTitle' => 'h2',
        'hSubtitle' => 'h3',
        'styleAttr' => fn ($align) => '',
        'headingAlign' => fn ($s, $field) => null,
        'bodyAlign' => fn ($s) => null,
    ])->render();

    expect($html)->toContain('Personajes')
        ->toContain('Tyrion')
        ->toContain('<thead>');
});

it('normaliza las tablas del wysiwyg: la fila de th pasa a un thead real', function () {
    $assets = app(PdfPageAssets::class);

    // TipTap emite la fila de cabeceras dentro del tbody: se mueve a un
    // <thead> para que DomPDF la repita en cada página al cruzar de página.
    $out = $assets->normalizeTables('<table><tbody><tr><th>A</th><th>B</th></tr><tr><td>1</td><td>2</td></tr></tbody></table>');
    expect($out)->toContain('<thead><tr><th>A</th><th>B</th></tr></thead>')
        ->toContain('<tbody><tr><td>1</td><td>2</td></tr></tbody>');

    // Con thead propio no se duplica…
    $withThead = $assets->normalizeTables('<table><thead><tr><th>A</th></tr></thead><tbody><tr><td>1</td></tr></tbody></table>');
    expect(substr_count($withThead, '<thead>'))->toBe(1);

    // …y una primera fila mixta (th + td) no es cabecera: no se toca.
    $mixed = $assets->normalizeTables('<table><tbody><tr><th>A</th><td>1</td></tr></tbody></table>');
    expect($mixed)->not->toContain('<thead>');
});

it('separa el arranque del contenido para que viaje con el título (splitFirstElement)', function () {
    $assets = app(PdfPageAssets::class);

    [$first, $rest] = $assets->splitFirstElement('<p>Uno</p><p>Dos</p><p>Tres</p>');
    expect($first)->toBe('<p>Uno</p>')
        ->and($rest)->toBe('<p>Dos</p><p>Tres</p>');

    // Un arranque ALTO (tabla, lista) no se agrupa: todo queda como resto y
    // el título se protege con el page-break-after: avoid del block__lead.
    $conTabla = '<table><tbody><tr><td>1</td></tr></tbody></table><p>Después</p>';
    [$first, $rest] = $assets->splitFirstElement($conTabla);
    expect($first)->toBe('')->and($rest)->toBe($conTabla);
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

    // Descarga pública (permanente y listo): attachment por defecto, con el
    // nombre LEGIBLE de la entidad dueña (no el slug de la BD)…
    $this->get("/api/pdfs/{$pdf->id}/download")->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="Casa Stark.pdf"');

    // …e inline con ?inline=1 (el botón «ver» abre el PDF en la pestaña, y
    // Chrome titula la pestaña con este nombre).
    $this->get("/api/pdfs/{$pdf->id}/download?inline=1")->assertOk()
        ->assertHeader('Content-Disposition', 'inline; filename="Casa Stark.pdf"');

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

it('el índice de la colección incluye los PDF temporales vigentes (generated)', function () {
    $character = makeCharacter(['is_published' => true]);
    $owner = motorUser();
    $service = app(PdfService::class);
    $items = [['entity' => 'character', 'id' => $character->id, 'copies' => 1]];

    // Uno LISTO, uno CADUCADO (no debe salir) y uno de OTRO dueño (tampoco).
    $ready = $service->generateCollection($owner, $items, 'es', sync: true)->refresh();
    $expired = $service->generateCollection($owner, $items, 'es', sync: true)->refresh();
    $expired->update(['expires_at' => now()->subHour()]);
    $service->generateCollection(motorUser(), $items, 'es', sync: true);

    // Un invitado con su token no ve los de otros dueños (lista vacía).
    // OJO: va ANTES de cualquier actingAs — la sesión de test persiste y,
    // con usuario + cabecera de invitado, lo del invitado se adoptaría.
    $this->getJson('/api/pdf-collection', ['X-Collection-Token' => 'guest-0123456789abcdef'])
        ->assertOk()
        ->assertJsonCount(0, 'generated');

    // Y uno PENDIENTE (encolado): también sale, aún sin URL ni tamaño.
    Queue::fake();
    $this->actingAs($owner)->postJson('/api/pdf-collection/items', [
        'entity' => 'character', 'id' => $character->id,
    ])->assertCreated();
    $pendingId = $this->actingAs($owner)->postJson('/api/pdf-collection/generate')
        ->assertAccepted()
        ->json('data.id');

    $response = $this->actingAs($owner)->getJson('/api/pdf-collection')->assertOk();
    expect($response->json('generated'))->toHaveCount(2)
        ->and($response->json('generated.0.id'))->toBe($pendingId)
        ->and($response->json('generated.0.status'))->toBe('pending')
        ->and($response->json('generated.0.url'))->toBeNull()
        ->and($response->json('generated.1.id'))->toBe($ready->id)
        ->and($response->json('generated.1.status'))->toBe('ready')
        ->and($response->json('generated.1.url'))->toContain('pdfs/collection/')
        ->and($response->json('generated.1.size'))->toBeGreaterThan(0)
        ->and($response->json('generated.1.expires_at'))->not->toBeNull();
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

// --- Nombres human readable (title en downloads y Content-Disposition) ---

it('las descargas exponen el título legible en el locale de cada PDF', function () {
    $service = app(PdfService::class);

    // Página del CRM: el título traducido de la página.
    $admin = motorUser('admin');
    $pageId = $this->actingAs($admin)->postJson('/api/admin/pages', [
        'title' => ['es' => 'Reglas del juego', 'en' => 'Game rules'],
        'is_published' => true, 'is_printable' => true,
    ])->json('data.id');
    $this->actingAs($admin)->postJson("/api/admin/pages/{$pageId}/blocks", [
        'type' => 'text', 'settings' => ['body' => ['es' => '<p>Hola</p>', 'en' => '<p>Hi</p>']],
    ]);
    $page = Page::find($pageId);
    $service->generate('pages', $page, 'es', sync: true);
    $service->generate('pages', $page, 'en', sync: true);

    // Export global con etiquetas declaradas por locale (labels()).
    makeCharacter(['is_published' => true]);
    $service->generate('characters', null, 'es', sync: true);
    $service->generate('characters', null, 'en', sync: true);

    // Export por entidad: el nombre traducible de la casa.
    $house = makeHouseWithSchemes(1);
    $house->setTranslations('name', ['es' => 'Casa Dragón', 'en' => 'House Dragon']);
    $house->save();
    $service->generate('house-schemes', $house, 'es', sync: true);

    $items = collect($this->getJson('/api/downloads')->assertOk()->json('data'))->keyBy('type');

    $pages = collect($items['pages']['items'])->keyBy('locale');
    expect($pages['es']['title'])->toBe('Reglas del juego')
        ->and($pages['en']['title'])->toBe('Game rules')
        ->and($pages['es']['filename'])->toStartWith('page-'); // el slug de la BD no cambia

    $characters = collect($items['characters']['items'])->keyBy('locale');
    expect($characters['es']['title'])->toBe('Personajes')
        ->and($characters['en']['title'])->toBe('Characters')
        ->and($characters['es']['filename'])->toBe('characters-es');

    expect($items['house-schemes']['items'][0]['title'])->toBe('Casa Dragón');
});

it('la descarga escapa el nombre UTF-8: filename* RFC 5987 + fallback ASCII', function () {
    $house = makeHouseWithSchemes(1);
    $house->setTranslations('name', ['es' => 'Casa Dragón']);
    $house->save();

    $pdf = app(PdfService::class)->generate('house-schemes', $house, 'es', sync: true)->refresh();

    $disposition = $this->get("/api/pdfs/{$pdf->id}/download?inline=1")
        ->assertOk()
        ->headers->get('Content-Disposition');

    expect($disposition)->toStartWith('inline;')
        ->toContain('filename="Casa Dragon.pdf"') // fallback transliterado
        ->toContain("filename*=utf-8''Casa%20Drag%C3%B3n.pdf");
});

it('sin etiqueta ni entidad dueña, el nombre legible cae al filename embellecido', function () {
    Pdfs::register('mega-pack', MegaPackExport::class);

    $export = app(PdfExportRegistry::class)->get('mega-pack');

    // Sin labels(): guiones a espacios, mayúscula inicial y sin sufijo de idioma.
    expect($export->displayName(null, 'es'))->toBe('Mega pack')
        ->and($export->displayName(null, 'en'))->toBe('Mega pack');
});

it('los PDF de USUARIO conservan su filename: ni title en la card ni nombre nuevo al descargar', function () {
    $character = makeCharacter(['is_published' => true]);
    $owner = motorUser();

    $pdf = app(PdfService::class)->generateCollection(
        $owner,
        [['entity' => 'character', 'id' => $character->id, 'copies' => 1]],
        'es',
        sync: true,
    )->refresh();

    // La card de "Mi colección" sigue pintando el filename (sin title).
    $generated = $this->actingAs($owner)->getJson('/api/pdf-collection')->assertOk()->json('generated.0');
    expect($generated)->not->toHaveKey('title')
        ->and($generated['filename'])->toStartWith('collection-');

    // Y la descarga mantiene el filename de siempre.
    $this->actingAs($owner)->get("/api/pdfs/{$pdf->id}/download")
        ->assertOk()
        ->assertHeader('Content-Disposition', "attachment; filename={$pdf->filename}.pdf");
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

/** Export global SIN labels(): demuestra el fallback embellecido del nombre. */
class MegaPackExport extends PdfExport
{
    public function sourceModel(): ?string
    {
        return null;
    }

    public function items(?Model $source, string $locale): array
    {
        return [];
    }

    public function filename(?Model $source, string $locale): string
    {
        return "mega-pack-{$locale}";
    }
}

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
