<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * Crea (si no existen) los roles base del motor y devuelve un usuario con el
 * rol pedido. Equivale a lo que hace `motor:install` en una instalación real.
 */
function motorUser(string $role = 'user'): User
{
    foreach (config('motor.auth.roles', ['admin', 'editor', 'user']) as $name) {
        Role::findOrCreate($name, 'web');
    }

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}
