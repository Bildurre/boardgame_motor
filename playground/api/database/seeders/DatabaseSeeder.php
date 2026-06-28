<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Crea un usuario de prueba por cada rol del motor.
     * Idempotente: se puede re-ejecutar sin duplicar.
     */
    public function run(): void
    {
        // Asegura los roles (por si no se corrió motor:install).
        foreach (['admin', 'editor', 'user'] as $role) {
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
    }
}
