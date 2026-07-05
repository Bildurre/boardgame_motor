<?php

namespace Database\Seeders;

use Bgm\Core\Site\Models\Setting;
use Bgm\Core\Site\SiteSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Configuración demo de la web (doc 10): título, logo/favicon generados,
 * fuentes y el modo "acento ALEATORIO" con los tres colores clásicos de CDL.
 * Idempotente: si ya hay configuración guardada, no toca nada.
 */
class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (Setting::query()->where('key', 'site')->exists()) {
            return;
        }

        app(SiteSettings::class)->update([
            'title' => ['es' => 'Choque de Leyendas', 'eu' => 'Kondairen Talka', 'en' => 'Clash of Legends'],
            'description' => ['es' => 'El juego de cartas imprimible de las grandes casas.'],
            'logo' => $this->logo(),
            'favicon' => $this->favicon(),
            'accent_mode' => 'random',
            'accent_colors' => ['#29ab5f', '#f15959', '#408cfd'], // verde/rojo/azul de CDL
            'font_headings' => 'ebgaramond', // webfont del juego (public/fonts)
            'font_body' => 'lora',
            'font_special' => 'italianno', // solo la usa el bloque cita

            'footer_text' => [
                'es' => 'Choque de Leyendas · un juego de mesa imprimible',
                'eu' => 'Kondairen Talka · inprima daitekeen mahai-jokoa',
                'en' => 'Clash of Legends · a printable board game',
            ],
        ]);
    }

    /** Logo SVG sencillo: hereda el acento sorteado vía currentColor. */
    protected function logo(): string
    {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 132 32" height="32">
  <circle cx="16" cy="16" r="13" fill="none" stroke="currentColor" stroke-width="3"/>
  <path d="M16 7l3 6 6 1-4.5 4 1 6-5.5-3-5.5 3 1-6L7 14l6-1z" fill="currentColor"/>
  <text x="36" y="22" font-family="Georgia, serif" font-size="17" font-weight="bold" fill="currentColor">CdL</text>
</svg>
SVG;

        Storage::disk('public')->put('content/site-logo.svg', $svg);

        return Storage::disk('public')->url('content/site-logo.svg');
    }

    /** Favicon PNG (GD), o null sin la extensión. */
    protected function favicon(): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $image = imagecreatetruecolor(64, 64);
        imagefill($image, 0, 0, imagecolorallocate($image, 26, 29, 36));
        imagefilledellipse($image, 32, 32, 52, 52, imagecolorallocate($image, 108, 92, 231));
        imagefilledellipse($image, 32, 32, 30, 30, imagecolorallocate($image, 255, 255, 255));

        ob_start();
        imagepng($image);
        Storage::disk('public')->put('content/site-favicon.png', (string) ob_get_clean());

        return Storage::disk('public')->url('content/site-favicon.png');
    }
}
