<?php

namespace App\Providers;

use App\Blocks\CharactersGridBlock;
use App\Blocks\HousesSchemesBlock;
use App\Models\Character;
use App\Models\House;
use App\Models\Scheme;
use App\Pdf\CharactersExport;
use App\Pdf\HouseCountersExport;
use App\Pdf\HouseSchemesExport;
use App\Pdf\HouseTokensExport;
use App\Pdf\SchemesExport;
use Bgm\Core\Support\Facades\Blocks;
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
        // La casa tiene DOS previews (mismo componente HouseToken, tamaños
        // distintos): la por defecto es la primera registrada.
        Previews::register('house', House::class);         // token 40 mm
        Previews::register('house-counter', House::class); // contador 25 mm

        // Presets de impresión de ESTE juego (mm; lo no indicado usa los
        // valores por defecto). Cada export elige el suyo con layout().
        Pdfs::layout('card-big', [ // doble Magic: 2 por A4 apaisado
            'orientation' => 'landscape',
            'item_width' => 126,
            'item_height' => 176,
            'margin' => 6,
            'gap' => 4,
        ]);
        Pdfs::layout('token-40', [ // tokens redondos de 2 cm de radio: 24 por A4
            'item_width' => 40,
            'item_height' => 40,
            'margin' => 10,
            'gap' => 4,
            'crop_mark_length' => 2,
        ]);

        // Bloques con-datos de ESTE juego (doc 03): el motor trae los de
        // presentación; estos consultan los modelos del juego. Su componente
        // Vue vive en la app (blockRegistry).
        Blocks::register(CharactersGridBlock::class);
        Blocks::register(HousesSchemesBlock::class);

        // Catálogo de PDF de ESTE juego (doc 02): qué se puede generar y qué
        // contiene cada uno. Todo se gestiona desde la sección PDF del admin.
        Pdfs::register('characters', CharactersExport::class);      // todos los personajes (card-big)
        Pdfs::register('schemes', SchemesExport::class);            // todas las argucias (card)
        Pdfs::register('house-schemes', HouseSchemesExport::class); // un PDF por casa (sus argucias)
        Pdfs::register('house-tokens', HouseTokensExport::class);   // 9 tokens por casa (token-40)
        Pdfs::register('house-counters', HouseCountersExport::class); // 9 contadores por casa (counter, preview house-counter)
    }
}
