<?php

namespace App\Providers;

use App\Models\Character;
use App\Models\Scheme;
use App\Pdf\CharacterCardExport;
use App\Pdf\CharactersExport;
use App\Pdf\HouseSchemesExport;
use Bgm\Core\Support\Facades\Pdfs;
use Bgm\Core\Support\Facades\Previews;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Entidades renderizables a PNG (doc 01). La clave es el segmento de
        // /_render/:entity y debe casar con el renderRegistry de la app Vue.
        Previews::register('character', Character::class);
        Previews::register('scheme', Scheme::class);

        // Exports de PDF (doc 02): la clave es el `type` de /admin/pdfs.
        Pdfs::register('house-schemes', HouseSchemesExport::class);   // colección por casa
        Pdfs::register('characters', CharactersExport::class);        // colección global
        Pdfs::register('character-card', CharacterCardExport::class); // carta individual
    }
}
