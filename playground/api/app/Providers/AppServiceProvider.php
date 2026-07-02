<?php

namespace App\Providers;

use App\Models\Character;
use App\Models\Scheme;
use App\Pdf\CharactersExport;
use App\Pdf\HouseSchemesExport;
use App\Pdf\SchemesExport;
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

        // Catálogo de PDF de ESTE juego (doc 02): qué se puede generar y qué
        // contiene cada uno. Todo se gestiona desde la sección PDF del admin.
        Pdfs::register('characters', CharactersExport::class);      // todos los personajes
        Pdfs::register('schemes', SchemesExport::class);            // todas las argucias
        Pdfs::register('house-schemes', HouseSchemesExport::class); // un PDF por casa (sus argucias)
    }
}
