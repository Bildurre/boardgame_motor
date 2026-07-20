<?php

use App\Models\House;
use Edc\Core\Content\BlockTypeRegistry;
use Edc\Core\Content\Models\Block;
use Edc\Core\Content\Models\Page;
use Edc\Core\Content\PageService;

function makePage(array $attributes = []): Page
{
    $page = new Page;
    $page->setTranslations('title', $attributes['title'] ?? ['es' => 'Sobre el juego']);
    $page->is_published = $attributes['is_published'] ?? true;
    $page->save();

    return $page->refresh();
}

// --- Catálogo de tipos ---

it('la paleta lista los tipos del motor y los del juego con su esquema', function () {
    $response = $this->actingAs(motorUser('admin'))->getJson('/api/admin/block-types')
        ->assertOk()
        ->assertJsonCount(11, 'data'); // 7 presentación + 1 con-datos (motor) + 3 con-datos (juego)

    $keys = collect($response->json('data'))->pluck('key');
    expect($keys)->toContain('header', 'text', 'text-card', 'quote', 'cta', 'index', 'faq', 'related', 'characters-grid', 'houses-schemes', 'featured-house');

    // El esquema de campos viaja serializado (el BlockEditor se genera de aquí).
    $header = collect($response->json('data'))->firstWhere('key', 'header');
    // Título y subtítulo nunca son obligatorios (ni en la cabecera).
    expect($header['fields'][0])->toMatchArray(['key' => 'title', 'type' => 'text', 'translatable' => true, 'required' => false])
        ->and($header['common'])->toHaveCount(5); // align + title/subtitle_align + width + background
});

// --- CRUD de páginas ---

it('crea, edita y publica una página con slug traducible', function () {
    $admin = motorUser('admin');

    $response = $this->actingAs($admin)->postJson('/api/admin/pages', [
        'title' => ['es' => 'Cómo jugar', 'en' => 'How to play'],
        'is_published' => true,
    ])->assertCreated();

    $id = $response->json('data.id');
    expect($response->json('data.slug.es'))->toBe('como-jugar')
        ->and($response->json('data.slug.en'))->toBe('how-to-play');

    // El título por defecto es obligatorio.
    $this->actingAs($admin)->postJson('/api/admin/pages', ['title' => ['en' => 'Only english']])
        ->assertUnprocessable();

    $this->actingAs($admin)->putJson("/api/admin/pages/{$id}", [
        'title' => ['es' => 'Cómo se juega'],
        'meta_description' => ['es' => 'Aprende a jugar'],
        'is_published' => false,
    ])->assertOk();

    expect(Page::find($id)->getTranslation('meta_description', 'es'))->toBe('Aprende a jugar');
});

it('solo puede haber una home y el borrado pasa las hijas a raíz', function () {
    $admin = motorUser('admin');
    $first = makePage(['title' => ['es' => 'Inicio']]);
    $second = makePage(['title' => ['es' => 'Novedades']]);
    $child = makePage(['title' => ['es' => 'Hija']]);
    $child->parent_id = $second->id;
    $child->save();

    $this->actingAs($admin)->postJson("/api/admin/pages/{$first->id}/set-home")->assertOk();
    $this->actingAs($admin)->postJson("/api/admin/pages/{$second->id}/set-home")->assertOk();

    expect(Page::where('is_home', true)->count())->toBe(1)
        ->and($second->refresh()->is_home)->toBeTrue();

    // Papelera: la hija queda en raíz; restore la recupera.
    $this->actingAs($admin)->deleteJson("/api/admin/pages/{$second->id}")->assertNoContent();
    expect($child->refresh()->parent_id)->toBeNull();

    $this->actingAs($admin)->postJson("/api/admin/pages/{$second->id}/restore")->assertOk();
    expect($second->refresh()->deleted_at)->toBeNull();
});

it('expone el catálogo de plantillas y valida la plantilla de la página', function () {
    $admin = motorUser('admin');

    // Catálogo: la del motor + la registrada por el juego (AppServiceProvider).
    $keys = collect($this->actingAs($admin)->getJson('/api/admin/pages/templates')
        ->assertOk()
        ->json('data'))->pluck('key');
    expect($keys)->toContain('default', 'landing');

    // La clave viaja al guardar… y una desconocida es 422.
    $this->actingAs($admin)->postJson('/api/admin/pages', [
        'title' => ['es' => 'Portada'], 'template' => 'landing',
    ])->assertCreated()->assertJsonPath('data.template', 'landing');

    $this->actingAs($admin)->postJson('/api/admin/pages', [
        'title' => ['es' => 'Rota'], 'template' => 'nope',
    ])->assertUnprocessable();
});

// --- Bloques ---

it('valida los bloques con las reglas derivadas del esquema', function () {
    $admin = motorUser('admin');
    $page = makePage();

    // El título ya no es obligatorio en ningún bloque (cabecera incluida).
    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'header',
        'settings' => ['subtitle' => ['es' => 'Sin título']],
    ])->assertCreated();

    // select fuera de opciones -> 422.
    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'header',
        'settings' => ['title' => ['es' => 'Hola'], 'align' => 'diagonal'],
    ])->assertUnprocessable();

    // Tipo desconocido -> 422.
    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", ['type' => 'nope'])
        ->assertUnprocessable();

    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'header',
        'settings' => ['title' => ['es' => 'Hola'], 'align' => 'center'],
    ])->assertCreated();
});

it('anida bloques en varios niveles (sin límite, solo se prohíben los ciclos) y el índice saca la profundidad real', function () {
    $admin = motorUser('admin');
    $page = makePage();

    $indice = $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'index',
        'settings' => [],
    ])->assertCreated()->json('data.id');

    $padre = $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'text',
        'settings' => ['title' => ['es' => 'Padre'], 'body' => ['es' => '<p>a</p>']],
    ])->assertCreated()->json('data.id');

    $hijo = $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'text',
        'settings' => ['title' => ['es' => 'Hijo'], 'body' => ['es' => '<p>b</p>']],
        'parent_id' => $padre,
    ])->assertCreated()->json('data.id');

    // Encadenar SÍ se permite (nieto, tercer nivel): sin límite de niveles.
    $nieto = $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'text',
        'settings' => ['title' => ['es' => 'Nieto'], 'body' => ['es' => '<p>c</p>']],
        'parent_id' => $hijo,
    ])->assertCreated()->json('data.id');

    // Un padre de otra página, no.
    $otra = makePage();
    $this->actingAs($admin)->postJson("/api/admin/pages/{$otra->id}/blocks", [
        'type' => 'text',
        'settings' => ['body' => ['es' => '<p>d</p>']],
        'parent_id' => $padre,
    ])->assertUnprocessable();

    // Un bloque no puede ser su propio padre…
    $this->actingAs($admin)->putJson("/api/admin/blocks/{$hijo}", ['parent_id' => $hijo])
        ->assertUnprocessable();
    // …ni colgar de uno de sus propios descendientes (ciclo): padre bajo su nieto.
    $this->actingAs($admin)->putJson("/api/admin/blocks/{$padre}", ['parent_id' => $nieto])
        ->assertUnprocessable();

    // El render público saca el índice con la profundidad real (0/1/2).
    $page->update(['is_published' => true]);
    $slug = $page->getTranslation('slug', 'es');
    $blocks = $this->getJson("/api/pages/{$slug}?locale=es")->assertOk()->json('data.blocks');
    $index = collect($blocks)->firstWhere('type', 'index');
    expect($index['data']['items'])->toHaveCount(3)
        ->and($index['data']['items'][0])->toMatchArray(['label' => 'Padre', 'depth' => 0])
        ->and($index['data']['items'][1])->toMatchArray(['label' => 'Hijo', 'depth' => 1])
        ->and($index['data']['items'][2])->toMatchArray(['label' => 'Nieto', 'depth' => 2]);
});

it('el saneado tira los párrafos vacíos del wysiwyg', function () {
    $admin = motorUser('admin');
    $page = makePage();

    $response = $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'text',
        'settings' => ['body' => ['es' => '<p>Uno</p><p> </p><p><br></p><p>Dos</p>']],
    ])->assertCreated();

    expect($response->json('data.settings.body.es'))->toBe('<p>Uno</p><p>Dos</p>');
});

it('el DSL anidado valida, sanea y localiza (repeater y entity)', function () {
    $admin = motorUser('admin');
    $page = makePage();

    // repeater con min:1 -> vacío es 422; una fila sin question (required) -> 422.
    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'faq',
        'settings' => ['items' => []],
    ])->assertUnprocessable();
    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'faq',
        'settings' => ['items' => [['answer' => ['es' => '<p>Sin pregunta</p>']]]],
    ])->assertUnprocessable();

    // Filas válidas: el richtext anidado se sanea (DC-09).
    $response = $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'faq',
        'settings' => ['items' => [
            [
                'question' => ['es' => '¿Jugadores?', 'eu' => 'Jokalariak?'],
                'answer' => ['es' => '<p>De <script>alert(1)</script><strong>2 a 4</strong>.</p>'],
            ],
        ]],
    ])->assertCreated();

    $block = Block::find($response->json('data.id'));
    expect($block->settings['items'][0]['answer']['es'])->not->toContain('<script')
        ->and($block->settings['items'][0]['answer']['es'])->toContain('<strong>2 a 4</strong>');

    // localizeSettings localiza DENTRO de cada fila (eu con fallback a es).
    $localized = app(BlockTypeRegistry::class)->get('faq')
        ->localizeSettings($block->settings, 'eu');
    expect($localized['items'][0]['question'])->toBe('Jokalariak?')
        ->and($localized['items'][0]['answer'])->toContain('2 a 4');

    // entity: guarda el id y resolveData carga el modelo publicado.
    $house = House::create([
        'name' => ['es' => 'Casa Testa'], 'color' => '#123456', 'is_published' => true,
    ]);
    $response = $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'featured-house',
        'settings' => ['house_id' => $house->id],
    ])->assertCreated();

    $block = Block::find($response->json('data.id'));
    $data = app(BlockTypeRegistry::class)->get('featured-house')
        ->resolveData($block, 'es');
    expect($data['house']['id'])->toBe($house->id)
        ->and($data['house']['name']['es'])->toBe('Casa Testa');

    // Un id que no es entero -> 422.
    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'featured-house',
        'settings' => ['house_id' => 'stark'],
    ])->assertUnprocessable();
});

it('sanea el texto rico en servidor (DC-09)', function () {
    $admin = motorUser('admin');
    $page = makePage();

    $response = $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'text',
        'settings' => [
            'body' => ['es' => '<p onclick="hack()">Hola <script>alert(1)</script><strong>mundo</strong></p><img class="rt-icon" src="/storage/icons/dado.png">'],
        ],
    ])->assertCreated();

    $saved = Block::find($response->json('data.id'))->settings['body']['es'];
    expect($saved)->not->toContain('<script')
        ->and($saved)->not->toContain('onclick')
        ->and($saved)->toContain('<strong>mundo</strong>')
        ->and($saved)->toContain('rt-icon'); // los iconos del juego sobreviven
});

it('localiza la imagen multilingüe con fallback al locale por defecto', function () {
    $admin = motorUser('admin');
    $page = makePage();

    // Plantilla e imagen de fondo viajan también en el render público.
    $this->actingAs($admin)->putJson("/api/admin/pages/{$page->id}", [
        'template' => 'landing',
        'background_image' => '/storage/content/fondo.png',
    ])->assertOk();

    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'text',
        'settings' => [
            'body' => ['es' => '<p>Hola</p>'],
            'image' => ['es' => '/storage/es.png', 'eu' => '/storage/eu.png'],
        ],
    ])->assertCreated();

    $slug = $page->getTranslation('slug', 'es');

    // Locale con imagen propia; locale sin ella cae al default (es).
    $this->getJson("/api/pages/{$slug}?locale=eu")
        ->assertOk()
        ->assertJsonPath('data.template', 'landing')
        ->assertJsonPath('data.background_image', '/storage/content/fondo.png')
        ->assertJsonPath('data.blocks.0.settings.image', '/storage/eu.png');

    $this->getJson("/api/pages/{$slug}?locale=en")
        ->assertOk()
        ->assertJsonPath('data.blocks.0.settings.image', '/storage/es.png');
});

it('reordena los bloques con la lista de ids', function () {
    $admin = motorUser('admin');
    $page = makePage();

    $ids = collect(['Uno', 'Dos', 'Tres'])->map(function ($title) use ($admin, $page) {
        return $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
            'type' => 'header',
            'settings' => ['title' => ['es' => $title]],
        ])->json('data.id');
    });

    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks/reorder", [
        'ids' => [$ids[2], $ids[0], $ids[1]],
    ])->assertOk()->assertJsonPath('data.0.id', $ids[2]);

    expect($page->blocks()->pluck('id')->all())->toBe([$ids[2], $ids[0], $ids[1]]);
});

// --- Render público ---

it('sirve la página publicada por slug con settings localizados y datos resueltos', function () {
    config(['motor.previews.enabled' => false]);
    $admin = motorUser('admin');
    $character = makeCharacter(['is_published' => true]);
    makeCharacter(['name' => ['es' => 'Borrador']]); // sin publicar: fuera

    $page = makePage(['title' => ['es' => 'Personajes', 'en' => 'Characters']]);

    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'characters-grid',
        'settings' => ['title' => ['es' => 'Todas las cartas'], 'limit' => 4, 'order' => 'name'],
    ])->assertCreated();

    // Por slug en es… y también por el slug de OTRO locale (canónica, DC-12).
    foreach (['personajes', 'characters'] as $slug) {
        $this->getJson("/api/pages/{$slug}?locale=es")
            ->assertOk()
            ->assertJsonPath('data.title', 'Personajes')
            ->assertJsonPath('data.slugs.en', 'characters')
            ->assertJsonPath('data.blocks.0.component', 'characters-grid')
            ->assertJsonPath('data.blocks.0.settings.title', 'Todas las cartas')
            ->assertJsonPath('data.blocks.0.settings.align', 'justify') // default del común
            ->assertJsonPath('data.blocks.0.settings.width', 'wide') // default de anchura
            ->assertJsonPath('data.blocks.0.data.characters.0.name.es', $character->getTranslation('name', 'es'));
    }

    // Sin publicar -> 404 en público.
    $page->update(['is_published' => false]);
    app(PageService::class)->forget($page);
    $this->getJson('/api/pages/personajes')->assertNotFound();
});

it('nav y home públicos con caché invalidada al cambiar bloques (DC-10)', function () {
    $admin = motorUser('admin');
    $home = makePage(['title' => ['es' => 'Bienvenida']]);
    $reglas = makePage(['title' => ['es' => 'Reglas']]);
    makePage(['title' => ['es' => 'Oculta'], 'is_published' => false]);

    // Hijas: la publicada sale como submenú del nav; la oculta, no.
    $reglas->children()->create(['title' => ['es' => 'FAQ'], 'is_published' => true]);
    $reglas->children()->create(['title' => ['es' => 'Borrador'], 'is_published' => false]);

    $this->actingAs($admin)->postJson("/api/admin/pages/{$home->id}/set-home")->assertOk();

    $this->getJson('/api/pages/nav')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.is_home', true)
        ->assertJsonCount(1, 'data.1.children')
        ->assertJsonPath('data.1.children.0.title.es', 'FAQ');

    $this->getJson('/api/pages/home')->assertOk()->assertJsonPath('data.title', 'Bienvenida');

    // El payload se cachea… y el cambio de un bloque lo invalida al momento.
    $block = $this->actingAs($admin)->postJson("/api/admin/pages/{$home->id}/blocks", [
        'type' => 'header', 'settings' => ['title' => ['es' => 'Hola']],
    ])->json('data.id');

    $this->getJson('/api/pages/home')->assertJsonPath('data.blocks.0.settings.title', 'Hola');

    $this->actingAs($admin)->putJson("/api/admin/blocks/{$block}", [
        'settings' => ['title' => ['es' => 'Adiós']],
    ])->assertOk();

    $this->getJson('/api/pages/home')->assertJsonPath('data.blocks.0.settings.title', 'Adiós');
});

it('el bloque índice enlaza a los bloques posteriores indexables', function () {
    $admin = motorUser('admin');
    $page = makePage();

    // Antes del índice: no debe salir.
    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'header', 'settings' => ['title' => ['es' => 'Antes']],
    ]);
    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'index', 'settings' => ['numbered' => true],
    ]);
    $after = $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'header', 'settings' => ['title' => ['es' => 'Capítulo 1']],
    ])->json('data.id');
    // Posterior pero NO indexable: fuera.
    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'header', 'settings' => ['title' => ['es' => 'Oculto']], 'is_indexable' => false,
    ]);

    $slug = $page->getTranslation('slug', 'es');
    $this->getJson("/api/pages/{$slug}")
        ->assertOk()
        ->assertJsonCount(1, 'data.blocks.1.data.items')
        ->assertJsonPath('data.blocks.1.data.items.0.id', $after)
        ->assertJsonPath('data.blocks.1.data.items.0.label', 'Capítulo 1');
});

it('el índice etiqueta por título > subtítulo > primer párrafo del contenido', function () {
    $admin = motorUser('admin');
    $page = makePage();

    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'index', 'settings' => [],
    ]);
    // Sin título: manda el subtítulo (aunque haya contenido).
    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'text',
        'settings' => ['subtitle' => ['es' => 'El subtítulo'], 'body' => ['es' => '<p>Cuerpo</p>']],
    ]);
    // Sin título ni subtítulo: SOLO el texto de la primera etiqueta del wysiwyg.
    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'text',
        'settings' => ['body' => ['es' => '<p>Solo <strong>este</strong> texto</p><p>No este otro</p>']],
    ]);

    $slug = $page->getTranslation('slug', 'es');
    $this->getJson("/api/pages/{$slug}")
        ->assertOk()
        ->assertJsonPath('data.blocks.0.data.items.0.label', 'El subtítulo')
        ->assertJsonPath('data.blocks.0.data.items.1.label', 'Solo este texto');
});

it('el CRM exige admin', function () {
    $page = makePage();

    $this->postJson('/api/admin/pages', [])->assertUnauthorized();
    $this->actingAs(motorUser('user'))->postJson('/api/admin/pages', [])->assertForbidden();
    $this->actingAs(motorUser('user'))
        ->postJson("/api/admin/pages/{$page->id}/blocks", ['type' => 'header'])
        ->assertForbidden();
    $this->actingAs(motorUser('user'))->getJson('/api/admin/block-types')->assertForbidden();
});
