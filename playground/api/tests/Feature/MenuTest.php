<?php

use Edc\Core\Menu\Models\MenuItem;
use Illuminate\Support\Facades\Cache;

// Menú configurable de la web (doc 10 ampliado): MenuSync mantiene un item
// por cada página NO home y por cada route_key de motor.menu.routes (el
// playground trae characters, houses y downloads); el admin gestiona grupos,
// visibilidad y orden; el público solo ve lo visible (páginas publicadas,
// grupos con al menos un hijo visible).

beforeEach(function () {
    config(['motor.menu.routes' => ['characters', 'houses', 'downloads']]);
});

it('sincroniza páginas y rutas del juego; la home queda fuera', function () {
    $admin = motorUser('admin');
    $home = makePage(['title' => ['es' => 'Inicio']]);
    $reglas = makePage(['title' => ['es' => 'Reglas']]);
    $this->actingAs($admin)->postJson("/api/admin/pages/{$home->id}/set-home")->assertOk();

    $response = $this->actingAs($admin)->getJson('/api/admin/menu')->assertOk();
    $items = collect($response->json('data'));

    // La página normal sí, la home no.
    expect($items->firstWhere('page.id', $reglas->id))->not->toBeNull()
        ->and($items->firstWhere('page.id', $home->id))->toBeNull();

    // Las tres rutas del juego, cada una una vez.
    $routeKeys = $items->where('type', 'route')->pluck('route_key');
    expect($routeKeys->sort()->values()->all())->toBe(['characters', 'downloads', 'houses']);

    // Página no publicada: sale igualmente en el admin (con su estado).
    $borrador = makePage(['title' => ['es' => 'Borrador'], 'is_published' => false]);
    $response = $this->actingAs($admin)->getJson('/api/admin/menu')->assertOk();
    $item = collect($response->json('data'))->firstWhere('page.id', $borrador->id);
    expect($item)->not->toBeNull()
        ->and($item['page']['is_published'])->toBeFalse();
});

it('elimina los huérfanos: página borrada y clave de ruta retirada', function () {
    $admin = motorUser('admin');
    $page = makePage(['title' => ['es' => 'Efímera']]);
    $this->actingAs($admin)->getJson('/api/admin/menu')->assertOk();
    expect(MenuItem::where('type', 'page')->where('page_id', $page->id)->exists())->toBeTrue();

    $this->actingAs($admin)->deleteJson("/api/admin/pages/{$page->id}")->assertNoContent();
    $this->actingAs($admin)->getJson('/api/admin/menu')->assertOk();
    expect(MenuItem::where('type', 'page')->where('page_id', $page->id)->exists())->toBeFalse();

    // Una clave que desaparece de la config se retira sola.
    config(['motor.menu.routes' => ['characters']]);
    $response = $this->actingAs($admin)->getJson('/api/admin/menu')->assertOk();
    $routeKeys = collect($response->json('data'))->where('type', 'route')->pluck('route_key');
    expect($routeKeys->all())->toBe(['characters']);
});

it('oculta un item: desaparece del público pero sigue en el admin', function () {
    $admin = motorUser('admin');
    $page = makePage(['title' => ['es' => 'Reglas'], 'is_published' => true]);
    $admin_tree = $this->actingAs($admin)->getJson('/api/admin/menu')->assertOk()->json('data');
    $item = collect($admin_tree)->firstWhere('page.id', $page->id);

    $publicBefore = collect($this->getJson('/api/menu')->assertOk()->json('data'));
    expect($publicBefore->firstWhere('page.id', $page->id))->not->toBeNull();

    $this->actingAs($admin)->patchJson("/api/admin/menu/{$item['id']}", ['is_visible' => false])
        ->assertOk()
        ->assertJsonPath('data.is_visible', false);

    $public = collect($this->getJson('/api/menu')->assertOk()->json('data'));
    expect($public->firstWhere('page.id', $page->id))->toBeNull();

    $stillInAdmin = collect($this->actingAs($admin)->getJson('/api/admin/menu')->json('data'))
        ->firstWhere('id', $item['id']);
    expect($stillInAdmin)->not->toBeNull()
        ->and($stillInAdmin['is_visible'])->toBeFalse();
});

it('grupos: crear, asignar hijos, borrar y hijos a la raíz', function () {
    $admin = motorUser('admin');
    $page = makePage(['title' => ['es' => 'Reglas'], 'is_published' => true]);

    $group = $this->actingAs($admin)->postJson('/api/admin/menu/groups', [
        'label' => ['es' => 'Ayuda', 'en' => 'Help'],
    ])->assertCreated()->json('data');
    expect($group['type'])->toBe('group')
        ->and($group['label']['es'])->toBe('Ayuda');

    // Un grupo no puede colgar de otro grupo.
    $other = $this->actingAs($admin)->postJson('/api/admin/menu/groups', ['label' => ['es' => 'Otro']])
        ->assertCreated()->json('data');
    $this->actingAs($admin)->patchJson("/api/admin/menu/{$other['id']}", ['parent_id' => $group['id']])
        ->assertUnprocessable();

    // Asigna la página al grupo.
    $tree = $this->actingAs($admin)->getJson('/api/admin/menu')->json('data');
    $item = collect($tree)->firstWhere('page.id', $page->id);
    $this->actingAs($admin)->patchJson("/api/admin/menu/{$item['id']}", ['parent_id' => $group['id']])
        ->assertOk();

    $tree = $this->actingAs($admin)->getJson('/api/admin/menu')->json('data');
    $groupNode = collect($tree)->firstWhere('id', $group['id']);
    expect(collect($groupNode['children'])->pluck('id'))->toContain($item['id']);

    // Grupo con la página visible y publicada: sale en público con su hijo.
    $publicTree = collect($this->getJson('/api/menu')->json('data'));
    $publicGroup = $publicTree->firstWhere('id', $group['id']);
    expect($publicGroup)->not->toBeNull()
        ->and(collect($publicGroup['children'])->pluck('page.id'))->toContain($page->id);

    // Ocultando la única hija, el grupo se queda sin hijos visibles: fuera.
    $this->actingAs($admin)->patchJson("/api/admin/menu/{$item['id']}", ['is_visible' => false])
        ->assertOk();
    $publicTree = collect($this->getJson('/api/menu')->json('data'));
    expect($publicTree->firstWhere('id', $group['id']))->toBeNull();

    // Vuelve a visible y borra el grupo: la página pasa a la raíz.
    $this->actingAs($admin)->patchJson("/api/admin/menu/{$item['id']}", ['is_visible' => true])->assertOk();
    $this->actingAs($admin)->deleteJson("/api/admin/menu/{$group['id']}")->assertNoContent();

    $tree = $this->actingAs($admin)->getJson('/api/admin/menu')->json('data');
    expect(collect($tree)->firstWhere('id', $group['id']))->toBeNull();
    $restored = collect($tree)->firstWhere('page.id', $page->id);
    expect($restored)->not->toBeNull(); // vuelve a la raíz (nivel superior del árbol)

    // Solo se pueden borrar grupos.
    $this->actingAs($admin)->deleteJson("/api/admin/menu/{$restored['id']}")->assertUnprocessable();
});

it('reordena los items con la lista de ids (cada uno conserva su padre)', function () {
    $admin = motorUser('admin');
    makePage(['title' => ['es' => 'Uno']]);
    makePage(['title' => ['es' => 'Dos']]);

    $tree = $this->actingAs($admin)->getJson('/api/admin/menu')->json('data');
    $ids = collect($tree)->pluck('id')->all();
    $reversed = array_reverse($ids);

    $this->actingAs($admin)->postJson('/api/admin/menu/reorder', ['ids' => $reversed])->assertOk();

    $newTree = $this->actingAs($admin)->getJson('/api/admin/menu')->json('data');
    expect(collect($newTree)->pluck('id')->all())->toBe($reversed);
});

it('una página sin publicar sale del público pero está en el admin', function () {
    $admin = motorUser('admin');
    $page = makePage(['title' => ['es' => 'Borrador'], 'is_published' => false]);

    $adminTree = $this->actingAs($admin)->getJson('/api/admin/menu')->json('data');
    expect(collect($adminTree)->firstWhere('page.id', $page->id))->not->toBeNull();

    $publicTree = $this->getJson('/api/menu')->json('data');
    expect(collect($publicTree)->firstWhere('page.id', $page->id))->toBeNull();
});

it('invalida la caché del menú al escribir en él y al publicar una página', function () {
    $admin = motorUser('admin');
    $page = makePage(['title' => ['es' => 'Novedades'], 'is_published' => false]);

    // Puebla la caché pública sin la página (aún sin publicar).
    $before = collect($this->getJson('/api/menu')->json('data'));
    expect($before->firstWhere('page.id', $page->id))->toBeNull();
    expect(Cache::has('motor.menu.nav'))->toBeTrue();

    // Publicarla invalida la caché (mismo punto que motor.pages.nav).
    $this->actingAs($admin)->putJson("/api/admin/pages/{$page->id}", ['is_published' => true])->assertOk();
    $after = collect($this->getJson('/api/menu')->json('data'));
    expect($after->firstWhere('page.id', $page->id))->not->toBeNull();

    // Una escritura del menú (ocultar) también invalida al momento.
    $item = $after->firstWhere('page.id', $page->id);
    $this->actingAs($admin)->patchJson("/api/admin/menu/{$item['id']}", ['is_visible' => false])
        ->assertOk();
    $afterHide = collect($this->getJson('/api/menu')->json('data'));
    expect($afterHide->firstWhere('page.id', $page->id))->toBeNull();
});

it('el menú del admin exige can:manage-web', function () {
    $this->getJson('/api/admin/menu')->assertUnauthorized();
    $this->actingAs(motorUser('user'))->getJson('/api/admin/menu')->assertForbidden();
    // Un editor (manage-game) tampoco: el menú es "la web".
    $this->actingAs(motorUser('editor'))->getJson('/api/admin/menu')->assertForbidden();
});
