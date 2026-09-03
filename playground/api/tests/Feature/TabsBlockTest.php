<?php

use Edc\Core\Content\BlockTypeRegistry;
use Edc\Core\Content\Models\Page;
use Edc\Core\Pdf\Models\GeneratedPdf;

// Bloque 'tabs' del motor (pestañas): contenedor cuyos hijos (parent_id)
// son el contenido de cada pestaña, en el orden del gestor.

function tabsPage(): Page
{
    $page = new Page;
    $page->setTranslations('title', ['es' => 'Página con pestañas']);
    $page->is_published = true;
    $page->is_printable = true;
    $page->save();

    return $page->refresh();
}

it('está registrado con un repetidor de pestañas (texto, icono y ancla)', function () {
    $registry = app(BlockTypeRegistry::class);

    expect($registry->has('tabs'))->toBeTrue();

    $schema = $registry->get('tabs')->toArray();
    expect($schema['category'])->toBe('presentation');

    $tabs = collect($schema['fields'])->firstWhere('key', 'tabs');
    expect($tabs['type'])->toBe('repeater')
        ->and($tabs['min'])->toBe(1)
        ->and(collect($tabs['fields'])->pluck('type', 'key')->all())
        ->toBe(['label' => 'text', 'anchor' => 'text', 'icon' => 'icon']);

    $label = collect($tabs['fields'])->firstWhere('key', 'label');
    expect($label['translatable'])->toBeTrue()->and($label['required'])->toBeTrue();
});

it('exige al menos una pestaña con texto en el idioma por defecto', function () {
    $admin = motorUser('admin');
    $page = tabsPage();

    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'tabs', 'settings' => ['tabs' => []],
    ])->assertUnprocessable();

    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'tabs', 'settings' => ['tabs' => [['label' => ['en' => 'Only english']]]],
    ])->assertUnprocessable();

    $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'tabs',
        'settings' => ['tabs' => [['label' => ['es' => 'Una'], 'icon' => 'swords', 'anchor' => 'una']]],
    ])->assertCreated();
});

it('el render público lleva el padre de cada bloque y las pestañas localizadas', function () {
    $admin = motorUser('admin');
    $page = tabsPage();

    $tabs = $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'tabs',
        'settings' => [
            'title' => ['es' => 'Mazos'],
            'tabs' => [
                ['label' => ['es' => 'Preconstruidos', 'en' => 'Preconstructed'], 'anchor' => 'precon'],
                ['label' => ['es' => 'Comunidad'], 'icon' => 'users'],
            ],
        ],
    ])->assertCreated()->json('data.id');

    $first = $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'text', 'settings' => ['title' => ['es' => 'Uno'], 'body' => ['es' => '<p>a</p>']], 'parent_id' => $tabs,
    ])->assertCreated()->json('data.id');
    $second = $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'text', 'settings' => ['title' => ['es' => 'Dos'], 'body' => ['es' => '<p>b</p>']], 'parent_id' => $tabs,
    ])->assertCreated()->json('data.id');

    $slug = $page->getTranslation('slug', 'es');
    $blocks = $this->getJson("/api/pages/{$slug}?locale=en")->assertOk()->json('data.blocks');

    expect(collect($blocks)->pluck('parent_id', 'id')->all())->toBe([$tabs => null, $first => $tabs, $second => $tabs]);

    $rendered = collect($blocks)->firstWhere('id', $tabs);
    expect($rendered['component'])->toBe('tabs')
        ->and($rendered['settings']['tabs'])->toBe([
            ['label' => 'Preconstructed', 'anchor' => 'precon', 'icon' => null],
            // Sin traducción al inglés: cae al idioma por defecto.
            ['label' => 'Comunidad', 'anchor' => null, 'icon' => 'users'],
        ]);
});

it('el PDF imprime los hijos en secuencia, cada uno bajo el nombre de su pestaña', function () {
    $admin = motorUser('admin');
    $page = tabsPage();

    $tabs = $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
        'type' => 'tabs',
        'settings' => ['title' => ['es' => 'Mazos'], 'tabs' => [['label' => ['es' => 'Preconstruidos']], ['label' => ['es' => 'Comunidad']]]],
    ])->assertCreated()->json('data.id');
    foreach (['Uno', 'Dos'] as $title) {
        $this->actingAs($admin)->postJson("/api/admin/pages/{$page->id}/blocks", [
            'type' => 'text', 'settings' => ['title' => ['es' => $title], 'body' => ['es' => '<p>x</p>']], 'parent_id' => $tabs,
        ])->assertCreated();
    }

    $pdf = new GeneratedPdf;
    $pdf->locale = 'es';
    $pdf->setRelation('source', $page->refresh());
    $html = view('motor::pdf.page', ['pdf' => $pdf])->render();

    // Título del contenedor (h2), nombre de pestaña (h3) y título del hijo
    // un nivel más abajo (h4), en el orden de las pestañas.
    $flat = preg_replace('/\s+/', ' ', $html);
    expect($flat)->toMatch('#<h2[^>]*>Mazos</h2>.*<h3>Preconstruidos</h3>.*<h4[^>]*>Uno</h4>.*<h3>Comunidad</h3>.*<h4[^>]*>Dos</h4>#s');
});
