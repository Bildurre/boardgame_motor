<?php

namespace Database\Seeders;

use App\Models\User;
use Bgm\Core\Auth\MotorAuth;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Deja el playground listo para jugar: roles + un usuario de prueba por
     * rol, contenido del juego (casas/argucias/personajes) y páginas del CRM
     * (home por bloques, casas y reglamento imprimible).
     * Idempotente: se puede re-ejecutar sin duplicar.
     */
    public function run(): void
    {
        // Roles + permisos del motor con su reparto (y limpieza de la caché
        // de Spatie, que puede quedar rancia tras un migrate:fresh).
        MotorAuth::syncRolesAndPermissions();

        $usuarios = [
            ['admin', 'Admin', 'admin@bgm.test'],
            ['editor', 'Editor', 'editor@bgm.test'],
            ['user', 'Usuario', 'user@bgm.test'],
        ];

        foreach ($usuarios as [$role, $name, $email]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make('password')],
            );
            // Verificados de fábrica: son cuentas de demo, sin correo real.
            if (! $user->hasVerifiedEmail()) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
            $user->syncRoles([$role]);
        }

        $this->call([GameSeeder::class, IconsSeeder::class, PagesSeeder::class, SiteSettingsSeeder::class]);
    }
}
