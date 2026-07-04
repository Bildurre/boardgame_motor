<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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
        // La caché de Spatie puede quedar rancia tras un migrate:fresh (con
        // CACHE_STORE=file no vive en la BBDD): sin esto, los roles "no se
        // ponen" aunque existan.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Asegura los roles del motor (por si no se corrió motor:install).
        foreach (config('motor.auth.roles', ['admin', 'editor', 'user']) as $role) {
            Role::findOrCreate($role, 'web');
        }

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
            $user->syncRoles([$role]);
        }

        $this->call([GameSeeder::class, PagesSeeder::class, SiteSettingsSeeder::class]);
    }
}
