<?php

namespace Database\Seeders;

use Bgm\Core\Content\Models\Block;
use Bgm\Core\Content\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Páginas demo del CRM (doc 03): la home por bloques, una página con el
 * bloque con-datos de casas y un reglamento imprimible con índice.
 * Idempotente: si ya hay páginas, no toca nada.
 */
class PagesSeeder extends Seeder
{
    public function run(): void
    {
        if (Page::count() > 0) {
            return;
        }

        // --- Home (plantilla landing: ancho completo, con imagen de fondo) ---
        $home = $this->page(['es' => 'Bienvenida', 'eu' => 'Ongi etorri', 'en' => 'Welcome']);
        $home->forceFill([
            'is_home' => true,
            'template' => 'landing',
            'background_image' => $this->demoBackground(),
        ])->save();
        $this->blocks($home, [
            // Anchura por bloque (campo común `width`): full / wide / narrow.
            ['header', ['title' => ['es' => 'Choque de Leyendas', 'eu' => 'Kondairen Talka', 'en' => 'Clash of Legends'],
                'subtitle' => ['es' => 'El juego de cartas de las grandes casas', 'en' => 'The card game of the great houses'],
                'align' => 'center', 'width' => 'full']],
            ['text', ['body' => ['es' => '<p>Un juego de <strong>intriga y poder</strong> donde cada casa lucha por el trono. Imprime tus cartas y juega.</p>'],
                'align' => 'center', 'width' => 'narrow']],
            ['characters-grid', ['title' => ['es' => 'Los personajes', 'en' => 'The characters'], 'limit' => 6, 'order' => 'name',
                'width' => 'full']],
            // Con color de fondo: se aplica como tinte semitransparente.
            ['quote', ['quote' => ['es' => '<p>Cuando juegas al juego de tronos, solo puedes ganar o morir.</p>'],
                'author' => ['es' => 'Cersei Lannister'], 'background' => '#6c5ce7']],
            ['cta', ['title' => ['es' => '¿Listo para jugar?'], 'button_text' => ['es' => 'Crea tu cuenta'],
                'button_url' => ['es' => '/registro'], 'align' => 'center', 'width' => 'narrow']],
        ]);

        // --- Las casas (bloque con-datos del juego) ---
        // Ojo con el título EU: su slug no debe chocar con el segmento del
        // listado público de casas ('etxeak', entityRegistry de la app).
        $casas = $this->page(
            ['es' => 'Las casas', 'eu' => 'Etxeak jokoan', 'en' => 'The houses'],
            meta: ['es' => 'Las grandes casas y sus argucias.']
        );
        $this->blocks($casas, [
            ['header', ['title' => ['es' => 'Las grandes casas', 'en' => 'The great houses']]],
            ['houses-schemes', ['intro' => ['es' => '<p>Cada casa juega con sus propias <em>argucias</em>.</p>'], 'show_empty' => false]],
        ]);

        // --- Reglamento (imprimible, con índice: PDF de página incluido) ---
        $reglas = $this->page(['es' => 'Reglamento', 'eu' => 'Araudia', 'en' => 'Rulebook']);
        $reglas->forceFill([
            'is_printable' => true,
            'background_image' => $this->demoBackground(),
        ])->save();
        $this->blocks($reglas, [
            ['header', ['title' => ['es' => 'Reglamento', 'en' => 'Rulebook']]],
            ['index', ['title' => ['es' => 'Contenido'], 'numbered' => true]],
            // Con imagen flotada: el texto la rodea (cleartext, clear-left).
            ['text', array_filter([
                'title' => ['es' => 'Preparación'],
                'body' => ['es' => '<p>Baraja el mazo y reparte <strong>5 cartas</strong> a cada jugador. El más joven empieza.</p>'],
                'image' => $this->demoImage(),
                'image_position' => 'clear-left',
            ])],
            ['text', ['title' => ['es' => 'Turno de juego'], 'body' => ['es' => '<p>En tu turno: roba una carta, juega hasta <strong>una argucia</strong> y ataca con tus personajes.</p>']]],
            ['text-card', ['label' => ['es' => 'Regla de oro'], 'body' => ['es' => '<p>Cuando el texto de una carta contradiga estas reglas, <strong>manda la carta</strong>.</p>'],
                'width' => 'narrow']],
        ]);
    }

    /** Genera un PNG decorativo de fondo de página (GD), o null sin GD. */
    protected function demoBackground(): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        if (! Storage::disk('public')->exists('content/demo-background.png')) {
            $image = imagecreatetruecolor(1600, 900);
            imagefill($image, 0, 0, imagecolorallocate($image, 26, 29, 36));
            // Círculos concéntricos suaves: la capa pública lo atenúa por tema.
            foreach ([[300, 250, 500], [1250, 650, 700], [900, 150, 350]] as [$x, $y, $d]) {
                imagefilledellipse($image, $x, $y, $d, $d, imagecolorallocate($image, 108, 92, 231));
                imagefilledellipse($image, $x, $y, (int) ($d * 0.6), (int) ($d * 0.6), imagecolorallocate($image, 26, 29, 36));
            }

            ob_start();
            imagepng($image);
            Storage::disk('public')->put('content/demo-background.png', (string) ob_get_clean());
        }

        return Storage::disk('public')->url('content/demo-background.png');
    }

    /** Genera un PNG de muestra (GD) y devuelve la imagen traducible, o null sin GD. */
    protected function demoImage(): ?array
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $image = imagecreatetruecolor(480, 360);
        imagefill($image, 0, 0, imagecolorallocate($image, 108, 92, 231));
        imagefilledellipse($image, 240, 180, 200, 200, imagecolorallocate($image, 255, 255, 255));
        imagestring($image, 5, 190, 172, 'BGM demo', imagecolorallocate($image, 60, 50, 140));

        ob_start();
        imagepng($image);
        Storage::disk('public')->put('content/demo-block.png', (string) ob_get_clean());

        // Imagen multilingüe: misma para todos los idiomas vía fallback al default.
        return ['es' => Storage::disk('public')->url('content/demo-block.png')];
    }

    protected function page(array $title, array $meta = []): Page
    {
        $page = new Page;
        $page->setTranslations('title', $title);
        if ($meta !== []) {
            $page->setTranslations('meta_description', $meta);
        }
        $page->is_published = true;
        $page->save();

        return $page;
    }

    /** @param array<int, array{0: string, 1: array}> $definitions */
    protected function blocks(Page $page, array $definitions): void
    {
        foreach ($definitions as $order => [$type, $settings]) {
            Block::create([
                'page_id' => $page->id,
                'type' => $type,
                'order' => $order,
                'settings' => $settings,
            ]);
        }
    }
}
