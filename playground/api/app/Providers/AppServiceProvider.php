<?php

namespace App\Providers;

use App\Models\Character;
use App\Models\Scheme;
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
    }
}
