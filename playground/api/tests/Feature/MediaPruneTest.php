<?php

use App\Models\Character;
use App\Models\House;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// motor:media:prune (doc 07): borra las carpetas {modelo}/{id}/{mediaId} del
// disco público sin registro en `media`; no toca lo que no sigue el patrón.

function mediaRow(string $modelType, int $modelId, int $id): void
{
    DB::table('media')->insert([
        'id' => $id,
        'model_type' => $modelType,
        'model_id' => $modelId,
        'uuid' => (string) Str::uuid(),
        'collection_name' => 'art',
        'name' => 'foto',
        'file_name' => 'foto.png',
        'mime_type' => 'image/png',
        'disk' => 'public',
        'conversions_disk' => 'public',
        'size' => 3,
        'manipulations' => '[]',
        'custom_properties' => '[]',
        'generated_conversions' => '[]',
        'responsive_images' => '[]',
        'order_column' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('borra las carpetas de media huérfanas y respeta el resto', function () {
    Storage::fake('public');
    $disk = Storage::disk('public');

    mediaRow(Character::class, 1, 5);
    mediaRow(House::class, 2, 6);

    $disk->put('character/1/5/foto.png', 'viva');            // registro vivo
    $disk->put('character/1/5/conversions/thumb.png', 'viva'); // y sus conversiones
    $disk->put('character/1/7/vieja.png', 'huérfana');       // media 7 no existe
    $disk->put('character/3/5/ajena.png', 'huérfana');       // media 5 es de character 1, no 3
    $disk->put('house/2/6/escudo.png', 'viva');
    $disk->put('house/2/5/cruzada.png', 'huérfana');         // media 5 es de Character, no House
    $disk->put('content/3/4/subida.txt', 'crm');             // no es carpeta de media
    $disk->put('previews/character-1-es.png', 'preview');
    $disk->put('pdfs/1/mazo.pdf', 'pdf');

    // --dry-run solo lista.
    Artisan::call('motor:media:prune', ['--dry-run' => true]);
    expect(Artisan::output())->toContain('character/1/7')->toContain('3 carpetas de media huérfanas (no se ha borrado nada');
    $disk->assertExists('character/1/7/vieja.png');

    Artisan::call('motor:media:prune');
    expect(Artisan::output())->toContain('3 carpetas de media huérfanas borradas');

    $disk->assertMissing('character/1/7/vieja.png');
    $disk->assertMissing('character/3/5/ajena.png');
    $disk->assertMissing('house/2/5/cruzada.png');
    // La carpeta del registro huérfano (character/3) se ha ido al quedar vacía.
    expect($disk->directories('character'))->toBe(['character/1']);

    $disk->assertExists('character/1/5/foto.png');
    $disk->assertExists('character/1/5/conversions/thumb.png');
    $disk->assertExists('house/2/6/escudo.png');
    $disk->assertExists('content/3/4/subida.txt');
    $disk->assertExists('previews/character-1-es.png');
    $disk->assertExists('pdfs/1/mazo.pdf');
});
