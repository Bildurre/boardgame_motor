<?php

namespace App\Providers;

use App\Blocks\CharactersGridBlock;
use App\Blocks\FeaturedHouseBlock;
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
use Bgm\Core\Support\Facades\Sitemap;
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
        Blocks::register(FeaturedHouseBlock::class);

        // Plantillas de página de ESTE juego (doc 03): la clave viaja en el
        // payload público y la SPA elige el layout en su templateRegistry.
        config(['motor.content.templates' => [
            ...config('motor.content.templates', []),
            'landing' => 'Portada (ancho completo)',
        ]]);

        // Webfonts de ESTE juego (doc 10): los woff2 viven en public/fonts y
        // se sirven por /api/site/fonts/{path} (CORS); la SPA genera los
        // @font-face de `files`. Se suman a las pilas del sistema del motor.
        config(['motor.site.fonts' => [
            ...config('motor.site.fonts', []),
            ...self::webfonts(),
        ]]);

        // Sitemap (doc 10): entidades públicas de ESTE juego. Los segmentos
        // por locale deben casar con el entityRegistry de la app Vue.
        // El apartado de Descargas también es indexable.
        Sitemap::add(fn () => [[
            'slugs' => ['es' => 'descargas', 'eu' => 'deskargak', 'en' => 'downloads'],
        ]]);
        Sitemap::add(fn () => self::sitemapEntries(
            Character::published()->get(),
            ['es' => 'personajes', 'eu' => 'pertsonaiak', 'en' => 'characters'],
        ));
        Sitemap::add(fn () => self::sitemapEntries(
            House::published()->get(),
            ['es' => 'casas', 'eu' => 'etxeak', 'en' => 'houses'],
        ));

        // Catálogo de PDF de ESTE juego (doc 02): qué se puede generar y qué
        // contiene cada uno. Todo se gestiona desde la sección PDF del admin.
        Pdfs::register('characters', CharactersExport::class);      // todos los personajes (card-big)
        Pdfs::register('schemes', SchemesExport::class);            // todas las argucias (card)
        Pdfs::register('house-schemes', HouseSchemesExport::class); // un PDF por casa (sus argucias)
        Pdfs::register('house-tokens', HouseTokensExport::class);   // 9 tokens por casa (token-40)
        Pdfs::register('house-counters', HouseCountersExport::class); // 9 contadores por casa (counter, preview house-counter)
    }

    /** URLs del sitemap para una colección publicada: índice + un detalle por slug. */
    protected static function sitemapEntries($models, array $sections): array
    {
        $entries = [['slugs' => $sections]];
        foreach ($models as $model) {
            $entries[] = [
                'slugs' => collect($model->getTranslations('slug'))
                    ->map(fn (string $slug, string $locale) => ($sections[$locale] ?? $sections['es'])."/{$slug}")
                    ->all(),
                'updated_at' => $model->updated_at?->toDateString(),
            ];
        }

        return $entries;
    }

    /** Catálogo de webfonts (regular + cursiva; variables donde las hay). */
    protected static function webfonts(): array
    {
        $family = fn (string $label, string $stackTail, array $files) => [
            'label' => $label,
            'stack' => "'{$label}', {$stackTail}",
            'files' => array_map(fn ($file) => [
                'src' => $file[0],
                'weight' => $file[1] ?? '400',
                'style' => str_contains(strtolower($file[0]), 'italic') ? 'italic' : 'normal',
            ], $files),
        ];

        return [
            'inter' => $family('Inter', 'system-ui, sans-serif', [
                ['inter/InterVariable.woff2', '100 900'],
                ['inter/InterVariable-Italic.woff2', '100 900'],
            ]),
            'opensans' => $family('Open Sans', 'system-ui, sans-serif', [
                ['opensans/opensans.woff2', '300 800'],
                ['opensans/opensans-italic.woff2', '300 800'],
            ]),
            'montserrat' => $family('Montserrat', 'system-ui, sans-serif', [
                ['montserrat/montserrat.woff2', '100 900'],
                ['montserrat/montserrat-italic.woff2', '100 900'],
            ]),
            'roboto' => $family('Roboto', 'system-ui, sans-serif', [
                ['roboto/roboto-regular-webfont.woff2'],
                ['roboto/roboto-italic-webfont.woff2'],
                ['roboto/roboto-bold-webfont.woff2', '700'],
                ['roboto/roboto-bolditalic-webfont.woff2', '700'],
            ]),
            'ebgaramond' => $family('EB Garamond', 'Georgia, serif', [
                ['ebgaramond/ebgaramond.woff2', '400 800'],
                ['ebgaramond/ebgaramond-italic.woff2', '400 800'],
            ]),
            'lora' => $family('Lora', 'Georgia, serif', [
                ['lora/lora.woff2', '400 700'],
                ['lora/lora-italic.woff2', '400 700'],
            ]),
            'playfairdisplay' => $family('Playfair Display', 'Georgia, serif', [
                ['playfairdisplay/playfairdisplay.woff2', '400 900'],
                ['playfairdisplay/playfairdisplay-italic.woff2', '400 900'],
            ]),
            'imfellenglish' => $family('IM Fell English', 'Georgia, serif', [
                ['imfellenglish/imfellenglish-regular-webfont.woff2'],
                ['imfellenglish/imfellenglish-italic-webfont.woff2'],
            ]),
            'italianno' => $family('Italianno', 'cursive', [
                ['italianno/italianno-regular-webfont.woff2'],
            ]),
            'jetbrainsmono' => $family('JetBrains Mono', 'ui-monospace, monospace', [
                ['jetbrainsmono/JetbrainsMonoVariable.woff2', '100 800'],
                ['jetbrainsmono/JetbrainsMonoVariable-Italic.woff2', '100 800'],
            ]),
        ];
    }
}
