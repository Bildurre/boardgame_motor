<?php

use Edc\Core\Menu\Models\MenuItem;
use Illuminate\Support\Facades\Cache;

// Menú configurable de la web (doc 10 ampliado, rediseño sin grupos): "si
// quiero un grupo, hago una página" — la jerarquía del menú es SIEMPRE la
// del CRM (pages.parent_id, un solo nivel), bidireccional con el gestor.
// MenuSync mantiene un item por cada página NO home y por cada route_key de
// motor.menu.routes (el playground trae characters, houses y downloads); el
// admin gestiona visibilidad y orden (y, arrastrando, la jerarquía) sobre
// una copia local que solo se persiste con PUT /api/admin/menu (el árbol
// entero); el público solo ve lo visible (páginas además publicadas).

beforeEach(function () {
    config(['motor.menu.routes' => ['characters', 'houses', 'downloads']]);
});

/** Aplana un árbol del menú al formato que espera PUT /api/admin/menu. */
function flattenMenu(array $tree, ?int $parentId = null): array
{
    $out = [];
    foreach ($tree as $node) {
        $out[] = ['id' => $node['id'], 'parent_id' => $parentId, 'is_visible' => $node['is_visible']];
        $out = [...$out, ...flattenMenu($node['children'], $node['id'])];
    }

    return $out;
}

/** Cambia (o añade) una entrada del array aplanado, por id. */
function withMenuEntry(array $items, int $id, array $overrides): array
{
    return array_map(fn ($entry) => $entry['id'] === $id ? [...$entry, ...$overrides] : $entry, $items);
}

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

it('oculta un item con el PUT: desaparece del público pero sigue en el admin', function () {
    $admin = motorUser('admin');
    $page = makePage(['title' => ['es' => 'Reglas'], 'is_published' => true]);
    $tree = $this->actingAs($admin)->getJson('/api/admin/menu')->assertOk()->json('data');
    $item = collect($tree)->firstWhere('page.id', $page->id);

    $publicBefore = collect($this->getJson('/api/menu')->assertOk()->json('data'));
    expect($publicBefore->firstWhere('page.id', $page->id))->not->toBeNull();

    $items = withMenuEntry(flattenMenu($tree), $item['id'], ['is_visible' => false]);
    $response = $this->actingAs($admin)->putJson('/api/admin/menu', ['items' => $items])->assertOk();
    $updated = collect($response->json('data'))->firstWhere('id', $item['id']);
    expect($updated['is_visible'])->toBeFalse();

    $public = collect($this->getJson('/api/menu')->assertOk()->json('data'));
    expect($public->firstWhere('page.id', $page->id))->toBeNull();

    $stillInAdmin = collect($this->actingAs($admin)->getJson('/api/admin/menu')->json('data'))
        ->firstWhere('id', $item['id']);
    expect($stillInAdmin)->not->toBeNull()
        ->and($stillInAdmin['is_visible'])->toBeFalse();
});

it('una página sin publicar sale del público pero está en el admin', function () {
    $admin = motorUser('admin');
    $page = makePage(['title' => ['es' => 'Borrador'], 'is_published' => false]);

    $adminTree = $this->actingAs($admin)->getJson('/api/admin/menu')->json('data');
    expect(collect($adminTree)->firstWhere('page.id', $page->id))->not->toBeNull();

    $publicTree = $this->getJson('/api/menu')->json('data');
    expect(collect($publicTree)->firstWhere('page.id', $page->id))->toBeNull();
});

it('PUT reordena la raíz: el orden del array manda', function () {
    $admin = motorUser('admin');
    makePage(['title' => ['es' => 'Uno']]);
    makePage(['title' => ['es' => 'Dos']]);

    $tree = $this->actingAs($admin)->getJson('/api/admin/menu')->json('data');
    $ids = collect($tree)->pluck('id')->all();
    $reversed = array_reverse($ids);

    $items = collect($reversed)->map(fn ($id) => ['id' => $id, 'parent_id' => null, 'is_visible' => true])->all();
    $this->actingAs($admin)->putJson('/api/admin/menu', ['items' => $items])->assertOk();

    $newTree = $this->actingAs($admin)->getJson('/api/admin/menu')->json('data');
    expect(collect($newTree)->pluck('id')->all())->toBe($reversed);
});

it('PUT anida una página bajo otra: escribe pages.parent_id y pages.order (bidireccional)', function () {
    $admin = motorUser('admin');
    $madre = makePage(['title' => ['es' => 'Ayuda']]);
    $hija = makePage(['title' => ['es' => 'Reglas']]);
    $otraHija = makePage(['title' => ['es' => 'Preguntas']]);

    $tree = $this->actingAs($admin)->getJson('/api/admin/menu')->json('data');
    $madreItem = collect($tree)->firstWhere('page.id', $madre->id);
    $hijaItem = collect($tree)->firstWhere('page.id', $hija->id);
    $otraHijaItem = collect($tree)->firstWhere('page.id', $otraHija->id);

    $items = flattenMenu($tree);
    $items = withMenuEntry($items, $hijaItem['id'], ['parent_id' => $madreItem['id']]);
    $items = withMenuEntry($items, $otraHijaItem['id'], ['parent_id' => $madreItem['id']]);

    $response = $this->actingAs($admin)->putJson('/api/admin/menu', ['items' => $items])->assertOk();
    $newTree = collect($response->json('data'));
    $madreNode = $newTree->firstWhere('id', $madreItem['id']);
    expect(collect($madreNode['children'])->pluck('page.id')->all())->toBe([$hija->id, $otraHija->id]);

    // El CRM queda escrito: parent_id + orden entre las hijas de la madre.
    expect($hija->refresh()->parent_id)->toBe($madre->id)
        ->and($hija->order)->toBe(0)
        ->and($otraHija->refresh()->parent_id)->toBe($madre->id)
        ->and($otraHija->order)->toBe(1);

    // Se ve igual desde el índice de páginas del CRM (bidireccional).
    $pagesIndex = collect($this->actingAs($admin)->getJson('/api/admin/pages')->json('data'));
    expect($pagesIndex->firstWhere('id', $hija->id)['parent_id'])->toBe($madre->id);
});

it('PUT anida una ruta bajo una página', function () {
    $admin = motorUser('admin');
    $madre = makePage(['title' => ['es' => 'Ayuda']]);

    $tree = $this->actingAs($admin)->getJson('/api/admin/menu')->json('data');
    $madreItem = collect($tree)->firstWhere('page.id', $madre->id);
    $rutaItem = collect($tree)->firstWhere('type', 'route');

    $items = withMenuEntry(flattenMenu($tree), $rutaItem['id'], ['parent_id' => $madreItem['id']]);
    $response = $this->actingAs($admin)->putJson('/api/admin/menu', ['items' => $items])->assertOk();

    $madreNode = collect($response->json('data'))->firstWhere('id', $madreItem['id']);
    expect(collect($madreNode['children'])->pluck('id'))->toContain($rutaItem['id']);
    expect(MenuItem::find($rutaItem['id'])->parent_id)->toBe($madreItem['id']);
});

it('PUT valida: una ruta no puede ser madre', function () {
    $admin = motorUser('admin');
    $page = makePage(['title' => ['es' => 'Reglas']]);

    $tree = $this->actingAs($admin)->getJson('/api/admin/menu')->json('data');
    $pageItem = collect($tree)->firstWhere('page.id', $page->id);
    $rutaItem = collect($tree)->firstWhere('type', 'route');

    $items = withMenuEntry(flattenMenu($tree), $pageItem['id'], ['parent_id' => $rutaItem['id']]);
    $this->actingAs($admin)->putJson('/api/admin/menu', ['items' => $items])->assertUnprocessable();
});

it('PUT valida: no se puede anidar bajo una página que ya es hija (un solo nivel)', function () {
    $admin = motorUser('admin');
    $abuela = makePage(['title' => ['es' => 'Abuela']]);
    $madre = makePage(['title' => ['es' => 'Madre']]);
    $nieta = makePage(['title' => ['es' => 'Nieta']]);

    $tree = $this->actingAs($admin)->getJson('/api/admin/menu')->json('data');
    $abuelaItem = collect($tree)->firstWhere('page.id', $abuela->id);
    $madreItem = collect($tree)->firstWhere('page.id', $madre->id);
    $nietaItem = collect($tree)->firstWhere('page.id', $nieta->id);

    // Anida madre bajo abuela (válido) y, en la MISMA petición, nieta bajo
    // madre: madre ya no es raíz en el destino -> 422.
    $items = flattenMenu($tree);
    $items = withMenuEntry($items, $madreItem['id'], ['parent_id' => $abuelaItem['id']]);
    $items = withMenuEntry($items, $nietaItem['id'], ['parent_id' => $madreItem['id']]);
    $this->actingAs($admin)->putJson('/api/admin/menu', ['items' => $items])->assertUnprocessable();

    // Anida madre bajo abuela de verdad (persiste) y, en una petición
    // APARTE, intenta colgar nieta de madre: sigue siendo 422 (se valida
    // contra el estado actual, aunque la petición no incluya a la abuela).
    $items = flattenMenu($tree);
    $items = withMenuEntry($items, $madreItem['id'], ['parent_id' => $abuelaItem['id']]);
    $this->actingAs($admin)->putJson('/api/admin/menu', ['items' => $items])->assertOk();

    $this->actingAs($admin)->putJson('/api/admin/menu', [
        'items' => [['id' => $nietaItem['id'], 'parent_id' => $madreItem['id'], 'is_visible' => true]],
    ])->assertUnprocessable();
});

it('bidireccional: cambiar la madre de una página por el CRM se refleja en el menú', function () {
    $admin = motorUser('admin');
    $madre = makePage(['title' => ['es' => 'Ayuda'], 'is_published' => true]);
    $hija = makePage(['title' => ['es' => 'Reglas'], 'is_published' => true]);

    // Aún sin anidar: la hija sale en la raíz del menú.
    $tree = collect($this->actingAs($admin)->getJson('/api/admin/menu')->json('data'));
    expect($tree->firstWhere('page.id', $hija->id))->not->toBeNull();

    // El CRM la anida bajo la madre (endpoint normal de páginas).
    $this->actingAs($admin)->putJson("/api/admin/pages/{$hija->id}", ['parent_id' => $madre->id])->assertOk();

    $tree = collect($this->actingAs($admin)->getJson('/api/admin/menu')->json('data'));
    expect($tree->firstWhere('page.id', $hija->id))->toBeNull(); // ya no en la raíz
    $madreItem = $tree->firstWhere('page.id', $madre->id);
    expect(collect($madreItem['children'])->pluck('page.id'))->toContain($hija->id);

    // Y sale igual en el público (madre visible/publicada = desplegable).
    $publicTree = collect($this->getJson('/api/menu')->json('data'));
    $publicMadre = $publicTree->firstWhere('page.id', $madre->id);
    expect($publicMadre)->not->toBeNull()
        ->and(collect($publicMadre['children'])->pluck('page.id'))->toContain($hija->id);

    // Vuelve a sacarla a la raíz por el CRM: el menú también lo refleja.
    $this->actingAs($admin)->putJson("/api/admin/pages/{$hija->id}", ['parent_id' => null])->assertOk();
    $tree = collect($this->actingAs($admin)->getJson('/api/admin/menu')->json('data'));
    expect($tree->firstWhere('page.id', $hija->id))->not->toBeNull();
});

it('el CRM impide anidar una página con hijas bajo otra, y encadenar niveles', function () {
    $admin = motorUser('admin');
    $madre = makePage(['title' => ['es' => 'Madre']]);
    $hija = makePage(['title' => ['es' => 'Hija']]);
    $this->actingAs($admin)->putJson("/api/admin/pages/{$hija->id}", ['parent_id' => $madre->id])->assertOk();

    // Encadenar: una página no puede colgar de una que ya es hija.
    $otra = makePage(['title' => ['es' => 'Otra']]);
    $this->actingAs($admin)->putJson("/api/admin/pages/{$otra->id}", ['parent_id' => $hija->id])
        ->assertUnprocessable();

    // Una página CON hijas no puede pasar a ser hija de otra.
    $tercera = makePage(['title' => ['es' => 'Tercera']]);
    $this->actingAs($admin)->putJson("/api/admin/pages/{$madre->id}", ['parent_id' => $tercera->id])
        ->assertUnprocessable();
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

    // Una escritura del menú (ocultar con el PUT) también invalida al momento.
    $tree = $this->actingAs($admin)->getJson('/api/admin/menu')->json('data');
    $item = collect($tree)->firstWhere('page.id', $page->id);
    $items = withMenuEntry(flattenMenu($tree), $item['id'], ['is_visible' => false]);
    $this->actingAs($admin)->putJson('/api/admin/menu', ['items' => $items])->assertOk();

    $afterHide = collect($this->getJson('/api/menu')->json('data'));
    expect($afterHide->firstWhere('page.id', $page->id))->toBeNull();
});

it('el menú del admin exige can:manage-web', function () {
    $this->getJson('/api/admin/menu')->assertUnauthorized();
    $this->putJson('/api/admin/menu', ['items' => []])->assertUnauthorized();

    $this->actingAs(motorUser('user'))->getJson('/api/admin/menu')->assertForbidden();
    $this->actingAs(motorUser('user'))->putJson('/api/admin/menu', ['items' => []])->assertForbidden();

    // Un editor (manage-game) tampoco: el menú es "la web".
    $this->actingAs(motorUser('editor'))->getJson('/api/admin/menu')->assertForbidden();
    $this->actingAs(motorUser('editor'))->putJson('/api/admin/menu', ['items' => []])->assertForbidden();
});
